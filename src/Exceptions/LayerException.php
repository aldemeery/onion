<?php

declare(strict_types=1);

namespace Aldemeery\Onion\Exceptions;

use Aldemeery\Onion\Attributes\Layer;
use Aldemeery\Onion\Interfaces\Invokable;
use Closure;
use Exception;
use ReflectionFunction;
use ReflectionObject;
use Throwable;

class LayerException extends Exception
{
    /** @var mixed */
    private $passable;

    private Closure|Invokable $layer;

    /** @var array<string, mixed> */
    private array $layerMetadata = [];

    public function __construct(
        string $message,
        Closure|Invokable $layer,
        mixed $passable,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);

        $this->layer = $layer;
        $this->passable = $passable;
        $this->setLayerMetadata($layer);
    }

    /** @return mixed */
    public function getPassable()
    {
        return $this->passable;
    }

    public function getLayer(): Closure|Invokable
    {
        return $this->layer;
    }

    public function getLayerMetadata(?string $key = null): mixed
    {
        return null !== $key ? $this->layerMetadata[$key] ?? null : $this->layerMetadata;
    }

    private function setLayerMetadata(Closure|Invokable $layer): void
    {
        $reflection = $layer instanceof Invokable ? new ReflectionObject($layer) : new ReflectionFunction($layer);

        $attributes = $reflection->getAttributes(Layer::class);

        $this->layerMetadata = array_reduce(
            $attributes,
            fn (array $metadata, $attribute) => array_merge($metadata, $attribute->newInstance()->getMetadata()),
            [],
        );
    }
}
