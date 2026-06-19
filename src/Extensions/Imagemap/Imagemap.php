<?php

namespace BotMan\Drivers\Line\Extensions\Imagemap;

class Imagemap
{
    private string $baseUrl;
    private string $altText;
    private int $baseWidth = 1040;
    private string $baseHeight = '1040';
    private array $actions = [];

    public static function create(string $baseUrl, string $altText = ''): self
    {
        $instance = new self;
        $instance->baseUrl = $baseUrl;
        $instance->altText = $altText;
        return $instance;
    }

    public function baseSize(int $width, string $height): self
    {
        $this->baseWidth = $width;
        $this->baseHeight = $height;
        return $this;
    }

    public function addAction(string $type, string $label, Area $area, ?string $data = null, ?string $uri = null): self
    {
        $action = [
            'type' => $type,
            'label' => $label,
            'area' => $area->toArray(),
        ];

        if ($type === 'message' && $data !== null) {
            $action['text'] = $data;
        } elseif ($type === 'uri' && $uri !== null) {
            $action['linkUri'] = $uri;
        }

        $this->actions[] = $action;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'type' => 'imagemap',
            'baseUrl' => $this->baseUrl,
            'altText' => $this->altText ?: 'Imagemap',
            'baseSize' => [
                'width' => $this->baseWidth,
                'height' => $this->baseHeight,
            ],
            'actions' => $this->actions,
        ];
    }
}
