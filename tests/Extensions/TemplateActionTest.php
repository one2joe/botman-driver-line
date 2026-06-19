<?php

namespace BotMan\Drivers\Line\Tests\Extensions;

use BotMan\Drivers\Line\Extensions\Templates\Actions\TemplateAction;
use PHPUnit\Framework\TestCase;

class TemplateActionTest extends TestCase
{
    public function test_message_action()
    {
        $result = TemplateAction::create()->message('Say', 'Hello')->toArray();
        $this->assertEquals('message', $result['type']);
        $this->assertEquals('Say', $result['label']);
        $this->assertEquals('Hello', $result['text']);
    }

    public function test_postback_action()
    {
        $result = TemplateAction::create()->postback('Send', 'data=1', 'Sent!')->toArray();
        $this->assertEquals('postback', $result['type']);
        $this->assertEquals('data=1', $result['data']);
        $this->assertEquals('Sent!', $result['displayText']);
    }

    public function test_uri_action()
    {
        $result = TemplateAction::create()->uri('Open', 'https://example.com')->toArray();
        $this->assertEquals('uri', $result['type']);
        $this->assertEquals('https://example.com', $result['uri']);
    }

    public function test_datetimePicker_action()
    {
        $result = TemplateAction::create()->datetimePicker('Pick', 'd=1', 'date', '2024-01-01', '2025-01-01', '2023-01-01')->toArray();
        $this->assertEquals('datetimepicker', $result['type']);
        $this->assertEquals('date', $result['mode']);
        $this->assertEquals('2024-01-01', $result['initial']);
    }

    public function test_camera_action()
    {
        $result = TemplateAction::create()->camera('Take')->toArray();
        $this->assertEquals('camera', $result['type']);
    }

    public function test_cameraRoll_action()
    {
        $result = TemplateAction::create()->cameraRoll('Gallery')->toArray();
        $this->assertEquals('cameraRoll', $result['type']);
    }

    public function test_location_action()
    {
        $result = TemplateAction::create()->location('Where')->toArray();
        $this->assertEquals('location', $result['type']);
    }

    public function test_clipboard_action()
    {
        $result = TemplateAction::create()->clipboard('Copy', 'text to copy')->toArray();
        $this->assertEquals('clipboard', $result['type']);
        $this->assertEquals('text to copy', $result['clipboardText']);
    }
}
