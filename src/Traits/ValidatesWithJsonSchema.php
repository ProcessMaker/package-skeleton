<?php

declare(strict_types=1);

namespace ProcessMaker\Package\PackageSkeleton\Traits;

use InvalidArgumentException;
use ProcessMaker\Package\PackageSkeleton\Projects\Shared\Domain\Model;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\Schema;

/**
 * JSON Schema validation for hexagonal domain models (not Eloquent).
 */
trait ValidatesWithJsonSchema
{
    protected ?object $jsonSchema = null;

    protected ?\Swaggest\JsonSchema\RemoteRef\Preloaded $refProvider = null;

  /** @var array<string, mixed> */
    protected array $attributes = [];

    /** @var array<string, mixed> */
    protected array $original = [];

    protected function loadJsonSchema(): ?object
    {
        if ($this->jsonSchema !== null) {
            return $this->jsonSchema;
        }

        $path = static::schemaPath();

        if ($path === '' || !file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $this->jsonSchema = json_decode($content);
        $schemaDir = dirname($path);
        $this->refProvider = new \Swaggest\JsonSchema\RemoteRef\Preloaded();

        $schemaFiles = glob($schemaDir . '/*.schema.json') ?: [];
        foreach ($schemaFiles as $file) {
            $schemaContent = file_get_contents($file);
            if ($schemaContent === false) {
                continue;
            }
            $schemaData = json_decode($schemaContent);
            if (isset($schemaData->{'$id'})) {
                $this->refProvider->setSchemaData($schemaData->{'$id'}, $schemaData);
            }
        }

        return $this->jsonSchema;
    }

    public function validateWithSchema(): bool
    {
        $schema = $this->loadJsonSchema();

        if ($schema === null) {
            return true;
        }

        try {
            $context = new \Swaggest\JsonSchema\Context();
            $context->setRemoteRefProvider($this->refProvider);

            $validator = Schema::import($schema, $context);
            $dataToValidate = $this->arrayToObject($this->toArray());
            $validator->in($dataToValidate);

            return true;
        } catch (InvalidValue $e) {
            throw new InvalidArgumentException(
                'Validation failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    private function arrayToObject($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        if ($data !== [] && array_keys($data) !== range(0, count($data) - 1)) {
            return (object) array_map([$this, 'arrayToObject'], $data);
        }

        return array_map([$this, 'arrayToObject'], $data);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function hydrate(array $attributes): self
    {
        $this->fill($attributes);
        $this->original = $this->attributes;

        return $this;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->set((string) $key, $value);
        }
        if ($this->attributes !== []) {
            $this->validateWithSchema();
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->attributes as $key => $value) {
            $result[$key] = $this->get($key);

            if ($result[$key] instanceof Model) {
                $result[$key] = $result[$key]->toArray();
            } elseif (is_array($result[$key])) {
                $result[$key] = array_map(function ($item) {
                    if ($item instanceof Model) {
                        return $item->toArray();
                    }

                    return $item;
                }, $result[$key]);
            }
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        $getter = $this->camelCase('get_' . $key);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        return $this->attributes[$key] ?? null;
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value): self
    {
        $setter = $this->camelCase('set_' . $key);
        if (method_exists($this, $setter)) {
            return $this->$setter($value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    private function camelCase(string $string): string
    {
        return (string) preg_replace_callback('/_([a-z])/', function ($matches) {
            return strtoupper($matches[1]);
        }, $string);
    }

    /**
     * @return array<string, mixed>
     */
    public function getChangedAttributes(): array
    {
        $changed = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $changed[$key] = $value;
            }
        }

        return $changed;
    }
}
