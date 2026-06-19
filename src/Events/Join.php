<?php

namespace BotMan\Drivers\Line\Events;

class Join extends AbstractEvent
{
    public function getName(): string
    {
        return 'join';
    }
}
