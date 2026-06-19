<?php

namespace BotMan\Drivers\Line\Tests\Events;

use BotMan\Drivers\Line\Events\Unfollow;
use PHPUnit\Framework\TestCase;

class UnfollowTest extends TestCase
{
    public function test_getName_returns_unfollow()
    {
        $event = new Unfollow([
            'type' => 'unfollow',
            'replyToken' => 'token',
            'source' => ['userId' => 'user'],
        ]);
        $this->assertEquals('unfollow', $event->getName());
    }
}
