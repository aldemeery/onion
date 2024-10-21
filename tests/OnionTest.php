<?php

declare(strict_types=1);

namespace Tests\Aldemeery\Onion;

use Aldemeery\Onion;
use Aldemeery\Onion\Attributes\Layer;
use Aldemeery\Onion\Exceptions\LayerException;
use DivisionByZeroError;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

#[CoversClass(Onion\Onion::class)]
#[CoversFunction('Aldemeery\Onion\onion')]
class OnionTest extends TestCase
{
    public function test_initialization_with_layers(): void
    {
        static::assertSame(
            'ABC',
            Onion\onion([
                fn (string $string): string => $string . 'A',
                fn (string $string): string => $string . 'B',
                fn (string $string): string => $string . 'C',
            ])->peel(''),
        );
    }

    public function test_initialization_without_layers(): void
    {
        static::assertSame(
            '',
            Onion\onion()->peel(''),
        );
    }

    public function test_adding_layers(): void
    {
        static::assertEquals(
            'ABC',
            Onion\onion([
                fn (string $string): string => $string . 'A',
                fn (string $string): string => $string . 'B',
            ])->add(
                fn (string $string): string => $string . 'C',
            )->peel(''),
        );
    }

    public function test_adding_layers_if_a_condition_is_true(): void
    {
        static::assertEquals(
            'ABC',
            Onion\onion([
                fn (string $string): string => $string . 'A',
                fn (string $string): string => $string . 'B',
            ])->addIf(
                true,
                fn (string $string): string => $string . 'C',
            )->addIf(
                false,
                fn (string $string): string => $string . 'D',
            )->peel(''),
        );
    }

    public function test_adding_layers_unless_a_condition_is_true(): void
    {
        static::assertEquals(
            'ABC',
            Onion\onion([
                fn (string $string): string => $string . 'A',
                fn (string $string): string => $string . 'B',
            ])->addUnless(
                false,
                fn (string $string): string => $string . 'C',
            )->addUnless(
                true,
                fn (string $string): string => $string . 'D',
            )->peel(''),
        );
    }

    public function test_onion_is_invokable(): void
    {
        static::assertEquals(
            'ABC',
            Onion\onion([
                fn (string $string): string => $string . 'A',
                fn (string $string): string => $string . 'B',
                fn (string $string): string => $string . 'C',
            ])(''),
        );
    }

    public function test_exceptions_are_converted_to_layer_exceptions(): void
    {
        static::expectException(LayerException::class);
        static::expectExceptionMessage('Something went wrong');

        Onion\onion([
            fn (string $string): string => $string . 'A',
            fn (string $string): never => throw new Exception('Something went wrong'),
            fn (string $string): string => $string . 'C',
        ])->peel('');
    }

    public function test_layer_exceptions_bubble_up(): void
    {
        try {
            Onion\onion([
                fn (string $string): string => $string . 'A',
                fn (string $string): never => throw new Exception('Something went wrong'),
                fn (string $string): never => throw new Exception('Something went wrong'),
            ])->peel('');
        } catch (Throwable $e) {
            static::assertInstanceOf(LayerException::class, $e);
            static::assertInstanceOf(Exception::class, $e->getPrevious());
            static::assertNull($e->getPrevious()->getPrevious());
        }
    }

    public function test_onions_can_be_peeled_with_null_as_a_default_passable(): void
    {
        static::assertSame(
            'ABC',
            Onion\onion([
                fn (null $value): string => (string) $value,
                fn (string $value): string => $value . 'A',
                fn (string $value): string => $value . 'B',
                fn (string $value): string => $value . 'C',
            ])->peel(),
        );
    }

    public function test_onions_could_return_nothing(): void
    {
        static::assertNull(
            Onion\onion([
                function (): void {},
            ])->peel(),
        );
    }

    public function test_closure_layers_can_have_metadata(): void
    {
        try {
            Onion\onion([
                fn (string $string): string => $string . 'A',
                #[Layer(['one' => 'One', 'two' => 'Two'])]
                #[Layer(['three' => 'Three']), Layer(['four' => 'Four'])]
                fn (string $string): never => throw new Exception('Something went wrong'),
            ])->peel('');
        } catch (LayerException $e) {
            static::assertSame(
                ['one' => 'One', 'two' => 'Two', 'three' => 'Three', 'four' => 'Four'],
                $e->getLayerMetadata(),
            );
        }
    }

    public function test_invokable_layers_can_have_metadata(): void
    {
        try {
            Onion\onion([
                fn (string $string): string => $string . 'A',
                new InvokableBadLayer(),
            ])->peel('');
        } catch (LayerException $e) {
            static::assertSame(
                ['one' => 'One', 'two' => 'Two', 'three' => 'Three', 'four' => 'Four'],
                $e->getLayerMetadata(),
            );
        }
    }

    public function test_onions_can_be_used_as_layers(): void
    {
        static::assertSame(
            'ABCD',
            Onion\onion([
                fn (string $string): string => $string . 'A',
                Onion\onion([
                    fn (string $string): string => $string . 'B',
                    fn (string $string): string => $string . 'C',
                ]),
                fn (string $string): string => $string . 'D',
            ])->peel(''),
        );
    }

    public function test_disabling_exception_handling(): void
    {
        static::expectException(DivisionByZeroError::class);
        static::expectExceptionMessage('Division by zero');

        Onion\onion([
            fn (int $value): float => $value / 0, // @phpstan-ignore binaryOp.invalid
        ])->withoutExceptionHandling()->peel(1);
    }

    public function test_exceptions_are_converted_to_layer_exceptions_by_default(): void
    {
        try {
            Onion\onion([
                fn (): never => throw new LayerException('Something went wrong', fn () => null, null),
            ])->peel(1);
        } catch (Throwable $e) {
            static::assertInstanceOf(LayerException::class, $e);
            static::assertSame('Something went wrong', $e->getMessage());
            static::assertNull($e->getPrevious());
        }
    }

    public function test_custom_exception_handlers(): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage('Different exception message');

        Onion\onion([
            fn (): never => throw new Exception('Something went wrong'),
        ])->setExecptionHandler(
            fn (Throwable $e): never => throw new RuntimeException('Different exception message', 0, $e),
        )->peel();
    }
}
