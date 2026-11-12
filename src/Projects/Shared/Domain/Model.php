<?php

declare(strict_types=1);

namespace ProcessMaker\Package\PackageSkeleton\Projects\Shared\Domain;

use JsonSerializable;
use ProcessMaker\Package\PackageSkeleton\Traits\ValidatesWithJsonSchema;

/**
 * Base domain model for hexagonal project domains.
 *
 * Hydrates from arrays (process $data), validates against JSON Schema when configured,
 * and serializes back to arrays. Not an Eloquent model.
 */
abstract class Model implements JsonSerializable
{
    use ValidatesWithJsonSchema;

    /**
     * Absolute path to the entity JSON Schema file, or empty string to skip validation.
     */
    abstract public static function schemaPath(): string;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->hydrate($attributes);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }
}
