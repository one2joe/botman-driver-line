<?php

namespace BotMan\Drivers\Line\Extensions\Templates;

class Carousel
{
    private string $altText;
    private array $columns = [];
    private ?string $imageAspectRatio = null;
    private ?string $imageSize = null;

    public static function create(string $altText = ''): self
    {
        $instance = new self;
        $instance->altText = $altText;
        return $instance;
    }

    public function addColumn(CarouselColumn $column): self
    {
        $this->columns[] = $column;
        if (!$this->altText) {
            $this->altText = $column->toArray()['text'] ?? 'Carousel';
        }
        return $this;
    }

    public function imageAspectRatio(string $ratio): self
    {
        $this->imageAspectRatio = $ratio;
        return $this;
    }

    public function imageSize(string $size): self
    {
        $this->imageSize = $size;
        return $this;
    }

    public function toArray(): array
    {
        $template = [
            'type' => 'carousel',
            'columns' => array_map(fn($c) => $c->toArray(), $this->columns),
        ];

        if ($this->imageAspectRatio !== null) {
            $template['imageAspectRatio'] = $this->imageAspectRatio;
        }
        if ($this->imageSize !== null) {
            $template['imageSize'] = $this->imageSize;
        }

        $altText = $this->altText ?: 'Carousel';

        return [
            'type' => 'template',
            'altText' => $altText,
            'template' => $template,
        ];
    }
}
