<?php

namespace BotMan\Drivers\Line\Tests\Events;

use BotMan\Drivers\Line\Events\Leave;
use PHPUnit\Framework\TestCase;

class LeaveTest extends TestCase
{
    public function test_getName_returns_leave()
    {
        $event = new Leave([
            'type' => 'leave',
            'replyToken' => 'token',
            'source' => ['userId' => 'user'],
        ]);
        $this->assertEquals('leave', $event->getName());
    }
}
