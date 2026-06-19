<?php

namespace BotMan\Drivers\Line\Extensions\Templates;

use BotMan\Drivers\Line\Extensions\Templates\Actions\TemplateAction;

class Confirm
{
    private string $altText;
    private string $text;
    private array $actions = [];

    public static function create(string $text, string $altText = ''): self
    {
        $instance = new self;
        $instance->text = $text;
        $instance->altText = $altText ?: $text;
        return $instance;
    }

    public function addAction(TemplateAction $action): self
    {
        $this->actions[] = $action;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'type' => 'template',
            'altText' => $this->altText,
            'template' => [
                'type' => 'confirm',
                'text' => $this->text,
                'actions' => array_map(fn($a) => $a->toArray(), $this->actions),
            ],
        ];
    }
}
