<?php

namespace BotMan\Drivers\Line\Tests\Events;

use BotMan\Drivers\Line\Events\Follow;
use PHPUnit\Framework\TestCase;

class FollowTest extends TestCase
{
    public function test_getName_returns_follow()
    {
        $event = new Follow([
            'type' => 'follow',
            'replyToken' => 'token',
            'source' => ['userId' => 'user'],
        ]);
        $this->assertEquals('follow', $event->getName());
    }

    public function test_getPayload_returns_full_event()
    {
        $payload = ['type' => 'follow', 'replyToken' => 't', 'source' => ['userId' => 'u']];
        $event = new Follow($payload);
        $this->assertEquals($payload, $event->getPayload());
    }
}
