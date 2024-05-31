<?php

declare(strict_types=1);

namespace Aldemeery\Onion;

use Closure;

/**
 * @template T
 */
class Onion
{
    private Closure $onion;

    /** @param callable(T): T|list<callable(T): T> $layers */
    public function __construct(
        array|callable $layers = []
    ) {
        $this->onion = fn ($passable) => $passable;

        $this->add($layers);
    }

    /**
     * @param T $passable
     *
     * @return T
     */
    public function __invoke($passable)
    {
        return $this->peel($passable);
    }

    /**
     * @param callable(T): T|list<callable(T): T> $layers
     *
     * @return self<T>
     */
    public function add(array|callable $layers): self
    {
        $this->onion = array_reduce(
            is_array($layers) ? $layers : [$layers],
            fn (callable $next, callable $current): callable => fn ($passable) => $current($next($passable)),
            $this->onion,
        );

        return $this;
    }

    /**
     * @param callable(T): T|list<callable(T): T> $layers
     *
     * @return self<T>
     */
    public function addIf(bool $condition, array|callable $layers): self
    {
        return true === $condition ? $this->add($layers) : $this;
    }

    /**
     * @param callable(T): T|list<callable(T): T> $layers
     *
     * @return self<T>
     */
    public function addUnless(bool $condition, array|callable $layers): self
    {
        return $this->addIf(!$condition, $layers);
    }

    /**
     * @param T $passable
     *
     * @return T
     */
    public function peel($passable)
    {
        return ($this->onion)($passable);
    }
}
