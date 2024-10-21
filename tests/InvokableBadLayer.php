<?php

declare(strict_types=1);

namespace Tests\Aldemeery\Onion;

use Aldemeery\Onion\Attributes\Layer;
use Aldemeery\Onion\Interfaces\Invokable;
use Exception;

#[Layer(['one' => 'One', 'two' => 'Two'])]
#[Layer(['three' => 'Three']), Layer(['four' => 'Four'])]
class InvokableBadLayer implements Invokable
{
    public function __invoke(mixed $passable = null): never
    {
        throw new Exception('Something went wrong');
    }
}
