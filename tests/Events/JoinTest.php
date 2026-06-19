<?php

namespace BotMan\Drivers\Line\Tests\Events;

use BotMan\Drivers\Line\Events\Join;
use PHPUnit\Framework\TestCase;

class JoinTest extends TestCase
{
    public function test_getName_returns_join()
    {
        $event = new Join([
            'type' => 'join',
            'replyToken' => 'token',
            'source' => ['userId' => 'user'],
        ]);
        $this->assertEquals('join', $event->getName());
    }
}
