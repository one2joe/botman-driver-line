<?php

namespace BotMan\Drivers\Line\Extensions\Templates;

use BotMan\Drivers\Line\Extensions\Templates\Actions\TemplateAction;

class CarouselColumn
{
    private ?string $title = null;
    private string $text;
    private ?string $thumbnailImageUrl = null;
    private ?string $imageBackgroundColor = null;
    private array $actions = [];

    public static function create(string $text): self
    {
        $instance = new self;
        $instance->text = $text;
        return $instance;
    }

    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function thumbnailImageUrl(string $url): self
    {
        $this->thumbnailImageUrl = $url;
        return $this;
    }

    public function imageBackgroundColor(string $color): self
    {
        $this->imageBackgroundColor = $color;
        return $this;
    }

    public function addAction(TemplateAction $action): self
    {
        $this->actions[] = $action;
        return $this;
    }

    public function toArray(): array
    {
        $column = [
            'text' => $this->text,
            'actions' => array_map(fn($a) => $a->toArray(), $this->actions),
        ];

        if ($this->title !== null) {
            $column['title'] = $this->title;
        }
        if ($this->thumbnailImageUrl !== null) {
            $column['thumbnailImageUrl'] = $this->thumbnailImageUrl;
        }
        if ($this->imageBackgroundColor !== null) {
            $column['imageBackgroundColor'] = $this->imageBackgroundColor;
        }

        return $column;
    }
}
