<?php

declare(strict_types=1);

namespace Tests\Aldemeery\Onion\Attributes;

use Aldemeery\Onion\Attributes\Layer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Layer::class)]
class LayerTest extends TestCase
{
    public function test_initialization(): void
    {
        $metadata = ['key' => 'value'];
        $layer = new Layer($metadata);

        static::assertSame($metadata, $layer->getMetadata());
    }
}
