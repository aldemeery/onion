<?php

declare(strict_types=1);

namespace Aldemeery\Onion;

/**
 * @template T
 *
 * @param callable(T): T|list<callable(T): T> $layers
 *
 * @return Onion<T>
 */
function onion(array|callable $layers = []): Onion
{
    return new Onion($layers);
}
