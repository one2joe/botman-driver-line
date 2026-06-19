<?php

namespace BotMan\Drivers\Line\Events;

class Unfollow extends AbstractEvent
{
    public function getName(): string
    {
        return 'unfollow';
    }
}
