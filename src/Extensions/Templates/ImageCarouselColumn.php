<?php

namespace BotMan\Drivers\Line\Extensions\Templates;

use BotMan\Drivers\Line\Extensions\Templates\Actions\TemplateAction;

class ImageCarouselColumn
{
    private string $imageUrl;
    private ?TemplateAction $action = null;

    public static function create(string $imageUrl): self
    {
        $instance = new self;
        $instance->imageUrl = $imageUrl;
        return $instance;
    }

    public function action(TemplateAction $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function toArray(): array
    {
        $column = [
            'imageUrl' => $this->imageUrl,
        ];

        if ($this->action !== null) {
            $column['action'] = $this->action->toArray();
        }

        return $column;
    }
}
