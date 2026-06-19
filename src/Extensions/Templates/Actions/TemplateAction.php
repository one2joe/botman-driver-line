<?php

namespace BotMan\Drivers\Line\Extensions\Templates\Actions;

class TemplateAction
{
    private array $action = [];

    public static function create(): self
    {
        return new self;
    }

    public function message(string $label, string $text): self
    {
        $this->action = [
            'type' => 'message',
            'label' => $label,
            'text' => $text,
        ];
        return $this;
    }

    public function postback(string $label, string $data, ?string $displayText = null): self
    {
        $this->action = [
            'type' => 'postback',
            'label' => $label,
            'data' => $data,
        ];
        if ($displayText !== null) {
            $this->action['displayText'] = $displayText;
        }
        return $this;
    }

    public function uri(string $label, string $uri): self
    {
        $this->action = [
            'type' => 'uri',
            'label' => $label,
            'uri' => $uri,
        ];
        return $this;
    }

    public function datetimePicker(string $label, string $data, string $mode, ?string $initial = null, ?string $max = null, ?string $min = null): self
    {
        $this->action = [
            'type' => 'datetimepicker',
            'label' => $label,
            'data' => $data,
            'mode' => $mode,
        ];
        if ($initial !== null) {
            $this->action['initial'] = $initial;
        }
        if ($max !== null) {
            $this->action['max'] = $max;
        }
        if ($min !== null) {
            $this->action['min'] = $min;
        }
        return $this;
    }

    public function camera(string $label): self
    {
        $this->action = [
            'type' => 'camera',
            'label' => $label,
        ];
        return $this;
    }

    public function cameraRoll(string $label): self
    {
        $this->action = [
            'type' => 'cameraRoll',
            'label' => $label,
        ];
        return $this;
    }

    public function location(string $label): self
    {
        $this->action = [
            'type' => 'location',
            'label' => $label,
        ];
        return $this;
    }

    public function clipboard(string $label, string $text): self
    {
        $this->action = [
            'type' => 'clipboard',
            'label' => $label,
            'clipboardText' => $text,
        ];
        return $this;
    }

    public function toArray(): array
    {
        return $this->action;
    }
}
