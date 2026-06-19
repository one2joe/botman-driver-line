<?php

namespace BotMan\Drivers\Line;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Interfaces\DriverEventInterface;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Users\User;
use BotMan\Drivers\Line\Events\Follow;
use BotMan\Drivers\Line\Events\Join;
use BotMan\Drivers\Line\Events\Leave;
use BotMan\Drivers\Line\Events\Postback;
use BotMan\Drivers\Line\Events\Unfollow;
use BotMan\Drivers\Line\Extensions\Templates\Buttons;
use BotMan\Drivers\Line\Extensions\Templates\Carousel;
use BotMan\Drivers\Line\Extensions\Templates\Confirm;
use BotMan\Drivers\Line\Extensions\Templates\ImageCarousel;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LineDriver extends HttpDriver
{
    protected string $apiBaseUrl = 'https://api.line.me/v2/bot';

    protected array $replyBuffer = [];

    private $logger = null;

    private ?DriverEventInterface $driverEvent = null;

    const DRIVER_NAME = 'Line';

    const MESSAGE_STICKER = '__sticker__';
    const MESSAGE_FILE = '__file__';

    public function matchesRequest(): bool
    {
        if (!$this->validateSignature()) {
            return false;
        }
        $events = $this->event->get('events', []);
        return count($events) > 0;
    }

    protected function validateSignature(): bool
    {
        $signature = $this->request->headers->get('X-LINE-SIGNATURE');
        if ($signature === null) {
            return false;
        }
        $secret = $this->config['line']['channel_secret'] ?? '';
        if ($secret === '') {
            return false;
        }
        $body = $this->request->getContent();
        $expected = base64_encode(hash_hmac('sha256', $body, $secret, true));
        return hash_equals($expected, $signature);
    }

    public function buildPayload(Request $request): void
    {
        $this->request = $request;
        $raw = json_decode($request->getContent(), true) ?? [];
        $this->event = new Collection($raw);
        $this->content = $request;
    }

    public function getMessages(): array
    {
        $events = $this->event->get('events', []);
        $messages = [];

        foreach ($events as $event) {
            $eventType = $event['type'] ?? '';
            $userId = $event['source']['userId'] ?? '';
            $replyToken = $event['replyToken'] ?? '';

            if ($eventType === 'message') {
                $messageType = $event['message']['type'] ?? '';
                $message = match ($messageType) {
                    'text' => new IncomingMessage($event['message']['text'], $userId, $userId),
                    'image' => (new IncomingMessage(Image::PATTERN, $userId, $userId))
                        ->addExtras('messageId', $event['message']['id'] ?? '')
                        ->addExtras('originalContentUrl', $event['message']['originalContentUrl'] ?? '')
                        ->addExtras('previewImageUrl', $event['message']['previewImageUrl'] ?? ''),
                    'video' => (new IncomingMessage(Video::PATTERN, $userId, $userId))
                        ->addExtras('messageId', $event['message']['id'] ?? '')
                        ->addExtras('originalContentUrl', $event['message']['originalContentUrl'] ?? ''),
                    'audio' => (new IncomingMessage(Audio::PATTERN, $userId, $userId))
                        ->addExtras('messageId', $event['message']['id'] ?? '')
                        ->addExtras('duration', $event['message']['duration'] ?? ''),
                    'location' => (new IncomingMessage(Location::PATTERN, $userId, $userId))
                        ->addExtras('title', $event['message']['title'] ?? '')
                        ->addExtras('address', $event['message']['address'] ?? '')
                        ->addExtras('latitude', $event['message']['latitude'] ?? '')
                        ->addExtras('longitude', $event['message']['longitude'] ?? ''),
                    'sticker' => (new IncomingMessage(self::MESSAGE_STICKER, $userId, $userId))
                        ->addExtras('packageId', $event['message']['packageId'] ?? '')
                        ->addExtras('stickerId', $event['message']['stickerId'] ?? ''),
                    'file' => (new IncomingMessage(File::PATTERN, $userId, $userId))
                        ->addExtras('messageId', $event['message']['id'] ?? '')
                        ->addExtras('fileName', $event['message']['fileName'] ?? '')
                        ->addExtras('fileSize', $event['message']['fileSize'] ?? ''),
                    default => null,
                };

                if ($message !== null) {
                    $message->addExtras('replyToken', $replyToken);
                    $messages[] = $message;
                }
            } elseif ($eventType === 'postback') {
                $data = $event['postback']['data'] ?? '';
                $displayText = $event['postback']['params']['displayText'] ?? '';
                $message = new IncomingMessage($data, $userId, $userId);
                $message->addExtras('replyToken', $replyToken);
                $message->addExtras('displayText', $displayText);
                $message->addExtras('params', $event['postback']['params'] ?? []);
                $messages[] = $message;
            }
        }

        return $messages;
    }

    public function getConversationAnswer(IncomingMessage $message): Answer
    {
        return Answer::create($message->getText());
    }

    public function getUser(IncomingMessage $matchingMessage): User
    {
        return new User($matchingMessage->getSender());
    }

    public function hasMatchingEvent(): bool
    {
        $events = $this->event->get('events', []);
        foreach ($events as $event) {
            $eventType = $event['type'] ?? '';
            if ($eventType === 'follow') {
                $this->driverEvent = new Follow($event);
                return true;
            }
            if ($eventType === 'unfollow') {
                $this->driverEvent = new Unfollow($event);
                return true;
            }
            if ($eventType === 'join') {
                $this->driverEvent = new Join($event);
                return true;
            }
            if ($eventType === 'leave') {
                $this->driverEvent = new Leave($event);
                return true;
            }
        }
        return false;
    }

    public function getDriverEvent(): ?DriverEventInterface
    {
        return $this->driverEvent;
    }

    public function buildServicePayload($message, $matchingMessage, $additionalParameters = []): array
    {
        if (is_array($message)) {
            return $message;
        }

        if ($message instanceof Buttons
            || $message instanceof Carousel
            || $message instanceof Confirm
            || $message instanceof ImageCarousel) {
            return $message->toArray();
        }

        if ($message instanceof Question) {
            return $this->buildQuestionPayload($message);
        }

        if ($message instanceof OutgoingMessage) {
            return $this->buildOutgoingMessagePayload($message);
        }

        return [['type' => 'text', 'text' => (string) $message]];
    }

    protected function buildQuestionPayload(Question $question): array
    {
        $buttons = $question->getButtons();
        $quickReplyItems = [];

        foreach ($buttons as $button) {
            $buttonArr = $button instanceof Button ? $button->toArray() : $button;
            $quickReplyItems[] = [
                'type' => 'action',
                'action' => [
                    'type' => 'message',
                    'label' => $buttonArr['text'] ?? '',
                    'text' => $buttonArr['value'] ?? '',
                ],
            ];
        }

        $payload = [
            'type' => 'text',
            'text' => $question->getText(),
        ];

        if (!empty($quickReplyItems)) {
            $payload['quickReply'] = [
                'items' => $quickReplyItems,
            ];
        }

        return [$payload];
    }

    protected function buildOutgoingMessagePayload(OutgoingMessage $message): array
    {
        $attachment = $message->getAttachment();
        if ($attachment !== null) {
            if ($attachment instanceof Image) {
                $url = $attachment->getUrl();
                return [[
                    'type' => 'image',
                    'originalContentUrl' => $url,
                    'previewImageUrl' => $url,
                ]];
            }
            if ($attachment instanceof Video) {
                return [[
                    'type' => 'video',
                    'originalContentUrl' => $attachment->getUrl(),
                    'previewImageUrl' => $attachment->getUrl(),
                ]];
            }
            if ($attachment instanceof Audio) {
                return [[
                    'type' => 'audio',
                    'originalContentUrl' => $attachment->getUrl(),
                    'duration' => 60000,
                ]];
            }
            if ($attachment instanceof Location) {
                return [[
                    'type' => 'location',
                    'title' => '',
                    'address' => '',
                    'latitude' => $attachment->getLatitude(),
                    'longitude' => $attachment->getLongitude(),
                ]];
            }
            if ($attachment instanceof File) {
                return [[
                    'type' => 'text',
                    'text' => 'File received',
                ]];
            }
        }

        return [['type' => 'text', 'text' => $message->getText()]];
    }

    public function sendPayload($payload): Response
    {
        $events = $this->event->get('events', []);
        $replyToken = $events[0]['replyToken'] ?? '';
        if ($replyToken === '') {
            return new Response('OK', 200);
        }

        $messages = $payload;
        if (!is_array($messages)) {
            $messages = [['type' => 'text', 'text' => (string) $messages]];
        }

        $this->replyBuffer[] = [
            'replyToken' => $replyToken,
            'messages' => $messages,
        ];

        return new Response('OK', 200);
    }

    public function messagesHandled(): void
    {
        if (empty($this->replyBuffer)) {
            return;
        }

        $grouped = [];
        foreach ($this->replyBuffer as $item) {
            $token = $item['replyToken'];
            if (!isset($grouped[$token])) {
                $grouped[$token] = [];
            }
            array_push($grouped[$token], ...$item['messages']);
        }

        foreach ($grouped as $token => $messages) {
            if (count($messages) > 5) {
                $dropped = count($messages) - 5;
                error_log("LINE Driver: Dropped {$dropped} messages (max 5 per replyToken)");
                $messages = array_slice($messages, 0, 5);
            }

            $this->log('REPLY', count($messages) . ' messages');

            $this->sendRequest('message/reply', [
                'replyToken' => $token,
                'messages' => $messages,
            ], null);
        }

        $this->replyBuffer = [];
    }

    public function sendRequest($endpoint, array $parameters, $matchingMessage): Response
    {
        $url = $this->apiBaseUrl . '/' . ltrim($endpoint, '/');
        $accessToken = $this->config['line']['channel_access_token'] ?? '';

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ];

        $this->log('API_REQ', "POST {$endpoint}");

        $response = $this->http->post($url, [], $parameters, $headers, true);

        $this->log('API_RESP', "POST {$endpoint} → {$response->getStatusCode()}");

        if ($response->getStatusCode() !== 200) {
            error_log("LINE API Error [{$response->getStatusCode()}]: " . ($response->getContent() ?: 'No response'));
        }

        return $response;
    }

    public function getMessageContent(string $messageId): ?string
    {
        $accessToken = $this->config['line']['channel_access_token'] ?? '';
        $url = $this->apiBaseUrl . '/message/' . urlencode($messageId) . '/content';

        $headers = [
            'Authorization: Bearer ' . $accessToken,
        ];

        $response = $this->http->get($url, [], $headers);

        if ($response->getStatusCode() !== 200) {
            error_log("LINE API Error downloading content [{$response->getStatusCode()}]");
            return null;
        }

        return $response->getContent();
    }

    public function isConfigured(): bool
    {
        $secret = $this->config['line']['channel_secret'] ?? '';
        $token = $this->config['line']['channel_access_token'] ?? '';
        return $secret !== '' && $token !== '';
    }

    public function setLogger(callable $logger): void
    {
        $this->logger = $logger;
    }

    protected function log(string $tag, string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            ($this->logger)($tag, $message, $context);
        }
    }

    public static function getDriverName(): string
    {
        return self::DRIVER_NAME;
    }

    public static function getConfigPaths(): array
    {
        return [
            'line.channel_secret',
            'line.channel_access_token',
        ];
    }

    protected function getApiBaseUrl(): string
    {
        return $this->config['line']['api_base_url'] ?? $this->apiBaseUrl;
    }
}
