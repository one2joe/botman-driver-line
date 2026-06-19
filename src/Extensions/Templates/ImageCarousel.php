<?php

namespace BotMan\Drivers\Line\Extensions\Templates;

class ImageCarousel
{
    private string $altText;
    private array $columns = [];

    public static function create(string $altText = ''): self
    {
        $instance = new self;
        $instance->altText = $altText;
        return $instance;
    }

    public function addColumn(ImageCarouselColumn $column): self
    {
        $this->columns[] = $column;
        return $this;
    }

    public function toArray(): array
    {
        $altText = $this->altText ?: 'ImageCarousel';

        return [
            'type' => 'template',
            'altText' => $altText,
            'template' => [
                'type' => 'image_carousel',
                'columns' => array_map(fn($c) => $c->toArray(), $this->columns),
            ],
        ];
    }
}
