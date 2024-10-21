<?php

declare(strict_types=1);

namespace Aldemeery\Onion;

use Aldemeery\Onion\Interfaces\Invokable;
use Closure;

/** @param Closure|Invokable|list<Closure|Invokable> $layers */
function onion(array|Closure|Invokable $layers = []): Onion
{
    return new Onion($layers);
}
