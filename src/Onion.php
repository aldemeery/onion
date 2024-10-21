<?php

declare(strict_types=1);

namespace Aldemeery\Onion;

use Aldemeery\Onion\Exceptions\LayerException;
use Aldemeery\Onion\Interfaces\Invokable;
use Closure;
use Throwable;

final class Onion implements Invokable
{
    private Closure|Invokable $onion;

    private Closure $exceptionHandler;

    /** @param Closure|Invokable|list<Closure|Invokable> $layers */
    public function __construct(
        array|Closure|Invokable $layers = []
    ) {
        $this->onion = $this->stack([]);

        $this->add($layers);
        $this->setExceptionHandler($this->defaultExceptionHandler());
    }

    public function __invoke(mixed $passable = null): mixed
    {
        return $this->peel($passable);
    }

    /** @param Closure|Invokable|list<Closure|Invokable> $layers */
    public function add(array|Closure|Invokable $layers): self
    {
        $this->onion = $this->stack($layers, $this->onion);

        return $this;
    }

    /** @param Closure|Invokable|list<Closure|Invokable> $layers */
    public function addIf(bool $condition, array|Closure|Invokable $layers): self
    {
        if ($condition) {
            $this->add($layers);
        }

        return $this;
    }

    /** @param Closure|Invokable|list<Closure|Invokable> $layers */
    public function addUnless(bool $condition, array|Closure|Invokable $layers): self
    {
        return $this->addIf(!$condition, $layers);
    }

    public function peel(mixed $passable = null): mixed
    {
        return ($this->onion)($passable);
    }

    public function setExceptionHandler(Closure $handler): self
    {
        $this->exceptionHandler = $handler;

        return $this;
    }

    public function withoutExceptionHandling(): self
    {
        return $this->setExceptionHandler(fn (Throwable $e): never => throw $e);
    }

    /** @param Closure|Invokable|list<Closure|Invokable> $layers */
    private function stack(array|Closure|Invokable $layers, Closure|Invokable|null $initial = null): Closure|Invokable
    {
        return array_reduce(
            is_array($layers) ? $layers : [$layers],
            fn (
                Closure|Invokable $next,
                Closure|Invokable $current,
            ): Closure => $this->withExceptionHandling($current, $next),
            $initial ?? fn (mixed $passable): mixed => $passable,
        );
    }

    private function withExceptionHandling(Closure|Invokable $current, Closure|Invokable $next): Closure
    {
        return function (mixed $passable) use ($current, $next): mixed {
            try {
                return $current($next($passable));
            } catch (Throwable $e) {
                return ($this->exceptionHandler)($e, $current, $passable);
            }
        };
    }

    private function defaultExceptionHandler(): Closure
    {
        return function (Throwable $e, Closure|Invokable $layer, mixed $passable): never {
            if ($e instanceof LayerException) {
                throw $e; // Allow the exception to bubble up
            }

            throw new LayerException($e->getMessage(), $layer, $passable, $e->getCode(), $e);
        };
    }
}
