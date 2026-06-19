<?php

namespace BotMan\Drivers\Line\Events;

class Postback extends AbstractEvent
{
    public function getName(): string
    {
        return 'postback';
    }
}
