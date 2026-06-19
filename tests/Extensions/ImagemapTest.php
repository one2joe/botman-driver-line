<?php

namespace BotMan\Drivers\Line\Tests\Extensions;

use BotMan\Drivers\Line\Extensions\Imagemap\Imagemap;
use BotMan\Drivers\Line\Extensions\Imagemap\Area;
use PHPUnit\Framework\TestCase;

class ImagemapTest extends TestCase
{
    public function test_imagemap_structure()
    {
        $result = Imagemap::create('https://example.com/img')
            ->addAction('message', 'Tap', new Area(0, 0, 520, 1040), 'hello')
            ->addAction('uri', 'Link', new Area(520, 0, 520, 1040), null, 'https://example.com')
            ->toArray();

        $this->assertEquals('imagemap', $result['type']);
        $this->assertEquals('https://example.com/img', $result['baseUrl']);
        $this->assertEquals(1040, $result['baseSize']['width']);
        $this->assertCount(2, $result['actions']);
        $this->assertEquals('hello', $result['actions'][0]['text']);
        $this->assertEquals('https://example.com', $result['actions'][1]['linkUri']);
    }

    public function test_area_structure()
    {
        $area = new Area(10, 20, 100, 200);
        $result = $area->toArray();
        $this->assertEquals(['x' => 10, 'y' => 20, 'width' => 100, 'height' => 200], $result);
    }
}
