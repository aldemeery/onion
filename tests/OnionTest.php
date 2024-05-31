<?php

declare(strict_types=1);

namespace Aldemeery\Onion\Tests;

use function Aldemeery\Onion\onion;

use Aldemeery\Onion\Onion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

#[CoversClass(Onion::class)]
#[CoversFunction('Aldemeery\Onion\onion')]
class OnionTest extends TestCase
{
    public function test_initialization_with_layers(): void
    {
        static::assertSame(
            'ABC',
            onion([
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
            onion()->peel(''),
        );
    }

    public function test_adding_layers(): void
    {
        static::assertEquals(
            'ABC',
            onion([
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
            onion([
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
            onion([
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
            onion([
                fn (string $string): string => $string . 'A',
                fn (string $string): string => $string . 'B',
                fn (string $string): string => $string . 'C',
            ])(''),
        );
    }
}
