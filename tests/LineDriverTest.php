<?php

namespace BotMan\Drivers\Line\Tests;

use BotMan\BotMan\Http\Curl;
use BotMan\Drivers\Line\LineDriver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class LineDriverTest extends TestCase
{
    protected function createRequest(string $body, string $secret = 'test-secret-12345678901234567890123456789012'): Request
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body
        );
        $signature = base64_encode(hash_hmac('sha256', $body, $secret, true));
        $request->headers->set('X-LINE-SIGNATURE', $signature);
        return $request;
    }

    protected function createDriver(Request $request, array $configOverrides = []): LineDriver
    {
        $config = array_merge([
            'line' => [
                'channel_secret' => 'test-secret-12345678901234567890123456789012',
                'channel_access_token' => 'test-access-token',
            ],
        ], $configOverrides);

        $http = $this->createMock(Curl::class);
        return new LineDriver($request, $config, $http);
    }

    public function test_matches_request_with_valid_signature_and_events()
    {
        $body = json_encode([
            'events' => [
                [
                    'type' => 'message',
                    'replyToken' => 'test-token',
                    'source' => ['userId' => 'test-user'],
                    'message' => ['type' => 'text', 'text' => 'Hello'],
                ],
            ],
        ]);

        $request = $this->createRequest($body);
        $driver = $this->createDriver($request);
        $driver->buildPayload($request);
        $this->assertTrue($driver->matchesRequest());
    }

    public function test_matches_request_returns_false_with_bad_signature()
    {
        $body = json_encode([
            'events' => [
                ['type' => 'message'],
            ],
        ]);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body
        );
        $request->headers->set('X-LINE-SIGNATURE', 'bad-signature');

        $driver = $this->createDriver($request);
        $driver->buildPayload($request);
        $this->assertFalse($driver->matchesRequest());
    }

    public function test_matches_request_returns_false_with_empty_events()
    {
        $body = json_encode(['events' => []]);

        $request = $this->createRequest($body);
        $driver = $this->createDriver($request);
        $driver->buildPayload($request);
        $this->assertFalse($driver->matchesRequest());
    }

    public function test_getMessages_returns_text_message()
    {
        $body = json_encode([
            'events' => [
                [
                    'type' => 'message',
                    'replyToken' => 'test-token',
                    'source' => ['userId' => 'test-user'],
                    'message' => ['type' => 'text', 'text' => 'สวัสดี'],
                ],
            ],
        ]);

        $request = $this->createRequest($body);
        $driver = $this->createDriver($request);
        $driver->buildPayload($request);
        $messages = $driver->getMessages();

        $this->assertCount(1, $messages);
        $this->assertEquals('สวัสดี', $messages[0]->getText());
        $this->assertEquals('test-user', $messages[0]->getSender());
        $this->assertEquals('test-token', $messages[0]->getExtras('replyToken'));
    }

    public function test_getMessages_returns_image_message()
    {
        $body = json_encode([
            'events' => [
                [
                    'type' => 'message',
                    'replyToken' => 'test-token',
                    'source' => ['userId' => 'test-user'],
                    'message' => [
                        'type' => 'image',
                        'id' => 'img-123',
                        'originalContentUrl' => 'https://example.com/img.jpg',
                        'previewImageUrl' => 'https://example.com/img_preview.jpg',
                    ],
                ],
            ],
        ]);

        $request = $this->createRequest($body);
        $driver = $this->createDriver($request);
        $driver->buildPayload($request);
        $messages = $driver->getMessages();

        $this->assertCount(1, $messages);
        $this->assertEquals('%%%_IMAGE_%%%', $messages[0]->getText());
        $this->assertEquals('img-123', $messages[0]->getExtras('messageId'));
    }

    public function test_getMessages_returns_sticker_with_extras()
    {
        $body = json_encode([
            'events' => [
                [
                    'type' => 'message',
                    'replyToken' => 'test-token',
                    'source' => ['userId' => 'test-user'],
                    'message' => [
                        'type' => 'sticker',
                        'packageId' => '1',
                        'stickerId' => '123',
                    ],
                ],
            ],
        ]);

        $request = $this->createRequest($body);
        $driver = $this->createDriver($request);
        $driver->buildPayload($request);
        $messages = $driver->getMessages();

        $this->assertCount(1, $messages);
        $this->assertEquals('__sticker__', $messages[0]->getText());
        $this->assertEquals('1', $messages[0]->getExtras('packageId'));
        $this->assertEquals('123', $messages[0]->getExtras('stickerId'));
    }

    public function test_getMessages_returns_postback_as_text()
    {
        $body = json_encode([
            'events' => [
                [
                    'type' => 'postback',
                    'replyToken' => 'test-token',
                    'source' => ['userId' => 'test-user'],
                    'postback' => ['data' => '/help'],
                ],
            ],
        ]);

        $request = $this->createRequest($body);
        $driver = $this->createDriver($request);
        $driver->buildPayload($request);
        $messages = $driver->getMessages();

        $this->assertCount(1, $messages);
        $this->assertEquals('/help', $messages[0]->getText());
    }

    public function test_getConversationAnswer_returns_answer_with_text()
    {
        $body = json_encode([
            'events' => [
                [
                    'type' => 'message',
                    'replyToken' => 'test-token',
                    'source' => ['userId' => 'test-user'],
                    'message' => ['type' => 'text', 'text' => 'Hello'],
                ],
            ],
        ]);

        $request = $this->createRequest($body);
        $driver = $this->createDriver($request);
        $driver->buildPayload($request);
        $messages = $driver->getMessages();

        $answer = $driver->getConversationAnswer($messages[0]);
        $this->assertEquals('Hello', $answer->getText());
    }

    public function test_getUser_returns_user_with_sender()
    {
        $body = json_encode([
            'events' => [
                [
                    'type' => 'message',
                    'replyToken' => 'test-token',
                    'source' => ['userId' => 'test-user'],
                    'message' => ['type' => 'text', 'text' => 'Hello'],
                ],
            ],
        ]);

        $request = $this->createRequest($body);
        $driver = $this->createDriver($request);
        $driver->buildPayload($request);
        $messages = $driver->getMessages();

        $user = $driver->getUser($messages[0]);
        $this->assertEquals('test-user', $user->getId());
    }

    public function test_isConfigured_returns_true_with_valid_config()
    {
        $body = json_encode(['events' => []]);
        $driver = $this->createDriver($this->createRequest($body));
        $this->assertTrue($driver->isConfigured());
    }

    public function test_isConfigured_returns_false_with_empty_config()
    {
        $body = json_encode(['events' => []]);
        $request = $this->createRequest($body);
        $driver = $this->createDriver($request, ['line' => ['channel_secret' => '', 'channel_access_token' => '']]);
        $this->assertFalse($driver->isConfigured());
    }

    public function test_hasMatchingEvent_returns_true_for_follow()
    {
        $body = json_encode([
            'events' => [
                [
                    'type' => 'follow',
                    'replyToken' => 'test-token',
                    'source' => ['userId' => 'test-user'],
                ],
            ],
        ]);

        $request = $this->createRequest($body);
        $driver = $this->createDriver($request);
        $driver->buildPayload($request);

        $this->assertTrue($driver->hasMatchingEvent());
        $event = $driver->getDriverEvent();
        $this->assertNotNull($event);
        $this->assertEquals('follow', $event->getName());
    }

    public function test_hasMatchingEvent_returns_false_for_postback()
    {
        $body = json_encode([
            'events' => [
                [
                    'type' => 'postback',
                    'replyToken' => 'test-token',
                    'source' => ['userId' => 'test-user'],
                    'postback' => ['data' => '/menu'],
                ],
            ],
        ]);

        $request = $this->createRequest($body);
        $driver = $this->createDriver($request);
        $driver->buildPayload($request);

        $this->assertFalse($driver->hasMatchingEvent());
    }

    public function test_sendPayload_buffers_and_messagesHandled_sends()
    {
        $body = json_encode([
            'events' => [
                [
                    'type' => 'message',
                    'replyToken' => 'test-token',
                    'source' => ['userId' => 'test-user'],
                    'message' => ['type' => 'text', 'text' => 'Hello'],
                ],
            ],
        ]);

        $http = $this->createMock(Curl::class);
        $http->expects($this->once())
            ->method('post')
            ->with(
                $this->stringContains('message/reply'),
                $this->anything(),
                $this->anything(),
                $this->anything()
            )
            ->willReturn(new \Symfony\Component\HttpFoundation\Response('OK', 200));

        $request = $this->createRequest($body);
        $driver = new LineDriver($request, [
            'line' => [
                'channel_secret' => 'test-secret-12345678901234567890123456789012',
                'channel_access_token' => 'test-access-token',
            ],
        ], $http);

        $driver->buildPayload($request);
        $driver->sendPayload([['type' => 'text', 'text' => 'Hi']]);
        $driver->messagesHandled();
    }

    public function test_messagesHandled_limits_to_five_messages()
    {
        $body = json_encode([
            'events' => [
                [
                    'type' => 'message',
                    'replyToken' => 'test-token',
                    'source' => ['userId' => 'test-user'],
                    'message' => ['type' => 'text', 'text' => 'Hello'],
                ],
            ],
        ]);

        $http = $this->createMock(Curl::class);
        $http->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($payload) {
                    return count($payload['messages']) === 5;
                }),
                $this->anything()
            )
            ->willReturn(new \Symfony\Component\HttpFoundation\Response('OK', 200));

        $request = $this->createRequest($body);
        $driver = new LineDriver($request, [
            'line' => [
                'channel_secret' => 'test-secret-12345678901234567890123456789012',
                'channel_access_token' => 'test-access-token',
            ],
        ], $http);

        $driver->buildPayload($request);

        for ($i = 0; $i < 7; $i++) {
            $driver->sendPayload([['type' => 'text', 'text' => "Msg {$i}"]]);
        }

        $driver->messagesHandled();
    }

    public function test_sendRequest_makes_http_call()
    {
        $body = json_encode(['events' => []]);
        $http = $this->createMock(Curl::class);
        $http->expects($this->once())
            ->method('post')
            ->willReturn(new \Symfony\Component\HttpFoundation\Response('OK', 200));

        $request = $this->createRequest($body);
        $driver = new LineDriver($request, [
            'line' => [
                'channel_secret' => 'test-secret-12345678901234567890123456789012',
                'channel_access_token' => 'test-access-token',
            ],
        ], $http);

        $response = $driver->sendRequest('message/reply', ['replyToken' => 't', 'messages' => []], null);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
