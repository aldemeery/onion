<?php

declare(strict_types=1);

namespace Tests\Aldemeery\Onion\Exceptions;

use Aldemeery\Onion\Attributes\Layer;
use Aldemeery\Onion\Exceptions\LayerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Aldemeery\Onion\InvokableBadLayer;

#[CoversClass(LayerException::class)]
class LayerExceptionTest extends TestCase
{
    public function test_instantiating_a_layer_exception(): void
    {
        $message = 'message';
        $layer = fn (): true => true;
        $passable = 'passable';
        $code = 1;
        $previous = null;

        $exception = new LayerException($message, $layer, $passable, $code, $previous);

        static::assertSame($passable, $exception->getPassable());
        static::assertSame($layer, $exception->getLayer());
        static::assertSame($message, $exception->getMessage());
        static::assertSame($code, $exception->getCode());
        static::assertSame($previous, $exception->getPrevious());
    }

    public function test_instantiating_a_layer_exception_with_defaults(): void
    {
        $message = 'message';
        $layer = fn (): true => true;
        $passable = 'passable';

        $exception = new LayerException($message, $layer, $passable);

        static::assertSame($passable, $exception->getPassable());
        static::assertSame($layer, $exception->getLayer());
        static::assertSame($message, $exception->getMessage());
        static::assertSame(0, $exception->getCode());
        static::assertNull($exception->getPrevious());
    }

    public function test_getting_closure_layer_metadata(): void
    {
        $exception = new LayerException(
            'message',
            #[Layer(['one' => 'One', 'two' => 'Two'])]
            #[Layer(['three' => 'Three']), Layer(['four' => 'Four'])]
            fn (): true => true,
            'passable',
        );

        static::assertSame(
            [
                'one' => 'One',
                'two' => 'Two',
                'three' => 'Three',
                'four' => 'Four',
            ],
            $exception->getLayerMetadata(),
        );
    }

    public function test_getting_invokable_layer_metadata(): void
    {
        $exception = new LayerException(
            'message',
            new InvokableBadLayer(),
            'passable',
        );

        static::assertSame(
            [
                'one' => 'One',
                'two' => 'Two',
                'three' => 'Three',
                'four' => 'Four',
            ],
            $exception->getLayerMetadata(),
        );
    }
}
