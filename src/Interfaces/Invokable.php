<?php

declare(strict_types=1);

namespace Aldemeery\Onion\Interfaces;

interface Invokable
{
    public function __invoke(mixed $passable = null): mixed;
}
