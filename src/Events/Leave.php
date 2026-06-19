<?php

namespace BotMan\Drivers\Line\Events;

class Leave extends AbstractEvent
{
    public function getName(): string
    {
        return 'leave';
    }
}
