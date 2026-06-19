<?php

namespace BotMan\Drivers\Line\Extensions\Templates;

use BotMan\Drivers\Line\Extensions\Templates\Actions\TemplateAction;

class Buttons
{
    private string $altText;
    private ?string $title = null;
    private string $text;
    private ?string $thumbnailImageUrl = null;
    private ?int $imageSize = null;
    private ?string $imageAspectRatio = null;
    private ?string $imageBackgroundColor = null;
    private array $actions = [];

    public static function create(string $text, string $altText = ''): self
    {
        $instance = new self;
        $instance->text = $text;
        $instance->altText = $altText ?: $text;
        return $instance;
    }

    public function title(string $title): self
    {
        $this->title = $title;
        if (!$this->altText) {
            $this->altText = $title;
        }
        return $this;
    }

    public function thumbnailImageUrl(string $url): self
    {
        $this->thumbnailImageUrl = $url;
        return $this;
    }

    public function imageSize(string $size): self
    {
        $this->imageSize = $size;
        return $this;
    }

    public function imageAspectRatio(string $ratio): self
    {
        $this->imageAspectRatio = $ratio;
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
        $template = [
            'type' => 'buttons',
            'text' => $this->text,
            'actions' => array_map(fn($a) => $a->toArray(), $this->actions),
        ];

        if ($this->title !== null) {
            $template['title'] = $this->title;
        }
        if ($this->thumbnailImageUrl !== null) {
            $template['thumbnailImageUrl'] = $this->thumbnailImageUrl;
        }
        if ($this->imageSize !== null) {
            $template['imageSize'] = $this->imageSize;
        }
        if ($this->imageAspectRatio !== null) {
            $template['imageAspectRatio'] = $this->imageAspectRatio;
        }
        if ($this->imageBackgroundColor !== null) {
            $template['imageBackgroundColor'] = $this->imageBackgroundColor;
        }

        $altText = $this->altText ?: $this->title ?: $this->text;

        return [
            'type' => 'template',
            'altText' => $altText,
            'template' => $template,
        ];
    }
}
