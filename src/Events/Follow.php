<?php

namespace BotMan\Drivers\Line\Events;

class Follow extends AbstractEvent
{
    public function getName(): string
    {
        return 'follow';
    }
}
