<?php

namespace BotMan\Drivers\Line\Tests\Events;

use BotMan\Drivers\Line\Events\Postback;
use PHPUnit\Framework\TestCase;

class PostbackTest extends TestCase
{
    public function test_getName_returns_postback()
    {
        $event = new Postback([
            'type' => 'postback',
            'replyToken' => 'token',
            'source' => ['userId' => 'user'],
            'postback' => ['data' => 'test'],
        ]);
        $this->assertEquals('postback', $event->getName());
    }
}
