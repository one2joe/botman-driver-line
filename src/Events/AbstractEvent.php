<?php

namespace BotMan\Drivers\Line\Events;

use BotMan\BotMan\Interfaces\DriverEventInterface;

abstract class AbstractEvent implements DriverEventInterface
{
    protected array $payload;
    protected string $userId;
    protected string $replyToken;
    protected string $type;

    public function __construct($payload)
    {
        $this->payload = $payload;
        $this->userId = $payload['source']['userId'] ?? '';
        $this->replyToken = $payload['replyToken'] ?? '';
        $this->type = $payload['type'] ?? '';
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getReplyToken(): string
    {
        return $this->replyToken;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    abstract public function getName(): string;
}
