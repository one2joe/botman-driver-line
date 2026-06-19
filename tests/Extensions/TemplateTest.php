<?php

namespace BotMan\Drivers\Line\Tests\Extensions;

use BotMan\Drivers\Line\Extensions\Templates\Buttons;
use BotMan\Drivers\Line\Extensions\Templates\Carousel;
use BotMan\Drivers\Line\Extensions\Templates\CarouselColumn;
use BotMan\Drivers\Line\Extensions\Templates\Confirm;
use BotMan\Drivers\Line\Extensions\Templates\ImageCarousel;
use BotMan\Drivers\Line\Extensions\Templates\ImageCarouselColumn;
use BotMan\Drivers\Line\Extensions\Templates\Actions\TemplateAction;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    public function test_buttons_structure()
    {
        $result = Buttons::create('Test text')
            ->title('Title')
            ->addAction(TemplateAction::create()->message('OK', 'ok'))
            ->toArray();

        $this->assertEquals('template', $result['type']);
        $this->assertArrayHasKey('altText', $result);
        $this->assertEquals('buttons', $result['template']['type']);
        $this->assertEquals('Test text', $result['template']['text']);
        $this->assertEquals('Title', $result['template']['title']);
        $this->assertCount(1, $result['template']['actions']);
    }

    public function test_buttons_alttext_fallback()
    {
        $result = Buttons::create('Hello')->toArray();
        $this->assertEquals('Hello', $result['altText']);
    }

    public function test_carousel_structure()
    {
        $result = Carousel::create('Carousel')
            ->addColumn(
                CarouselColumn::create('Col 1')
                    ->title('Title 1')
                    ->addAction(TemplateAction::create()->message('Go', 'go'))
            )
            ->addColumn(
                CarouselColumn::create('Col 2')
                    ->addAction(TemplateAction::create()->message('Back', 'back'))
            )
            ->toArray();

        $this->assertEquals('template', $result['type']);
        $this->assertEquals('carousel', $result['template']['type']);
        $this->assertCount(2, $result['template']['columns']);
        $this->assertEquals('Col 1', $result['template']['columns'][0]['text']);
        $this->assertEquals('Title 1', $result['template']['columns'][0]['title']);
    }

    public function test_confirm_structure()
    {
        $result = Confirm::create('Are you sure?')
            ->addAction(TemplateAction::create()->message('Yes', 'yes'))
            ->addAction(TemplateAction::create()->message('No', 'no'))
            ->toArray();

        $this->assertEquals('template', $result['type']);
        $this->assertEquals('confirm', $result['template']['type']);
        $this->assertCount(2, $result['template']['actions']);
    }

    public function test_image_carousel_structure()
    {
        $result = ImageCarousel::create('Images')
            ->addColumn(
                ImageCarouselColumn::create('https://example.com/img1.jpg')
                    ->action(TemplateAction::create()->message('Show', 'show'))
            )
            ->addColumn(
                ImageCarouselColumn::create('https://example.com/img2.jpg')
            )
            ->toArray();

        $this->assertEquals('template', $result['type']);
        $this->assertEquals('image_carousel', $result['template']['type']);
        $this->assertCount(2, $result['template']['columns']);
        $this->assertEquals('https://example.com/img1.jpg', $result['template']['columns'][0]['imageUrl']);
        $this->assertArrayHasKey('action', $result['template']['columns'][0]);
        $this->assertArrayNotHasKey('action', $result['template']['columns'][1]);
    }
}
