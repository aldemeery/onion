<?php

declare(strict_types=1);

namespace Aldemeery\Onion\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION | Attribute::IS_REPEATABLE)]
class Layer
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        private array $metadata,
    ) {
        // Silence is golden...
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
