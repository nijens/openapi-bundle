<?php

declare(strict_types=1);

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Json;

use JsonSerializable;
use Nijens\OpenapiBundle\Json\Exception\InvalidArgumentException;
use stdClass;

/**
 * JSON reference container.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class Reference implements JsonSerializable
{
    /**
     * @var string
     */
    private $pointer;

    /**
     * @var stdClass
     */
    private $jsonSchema;

    /**
     * @var JsonPointer
     */
    private $jsonPointer;

    /**
     * Constructs a new {@see Reference} instance.
     */
    public function __construct(string $pointer, stdClass $jsonSchema)
    {
        $this->pointer = $pointer;
        $this->jsonSchema = $jsonSchema;
    }

    /**
     * Returns the JSON pointer of this reference.
     */
    public function getPointer(): string
    {
        return $this->pointer;
    }

    /**
     * Returns the (external) JSON schema being referenced.
     */
    public function getJsonSchema(): stdClass
    {
        return $this->jsonSchema;
    }

    public function has(string $property): bool
    {
        $schema = $this->resolve();

        return isset($schema->{$property});
    }

    /**
     * @return array|string|int|float|bool|stdClass|null
     */
    public function get(string $property)
    {
        if ($this->has($property) === false) {
            throw new InvalidArgumentException(sprintf('Unknown property "%s".', $property));
        }

        $schema = $this->resolve();

        return $schema->{$property};
    }

    public function resolve(): stdClass
    {
        $jsonPointer = $this->createJsonPointer();

        return $jsonPointer->get($this->getPointer());
    }

    public function jsonSerialize(): array
    {
        return [
            '$ref' => $this->getPointer(),
        ];
    }

    public function __isset(string $property): bool
    {
        return $this->has($property);
    }

    /**
     * @return array|string|int|float|bool|stdClass|null
     */
    public function __get(string $property)
    {
        return $this->get($property);
    }

    private function createJsonPointer(): JsonPointer
    {
        if ($this->jsonPointer instanceof JsonPointer === false) {
            $this->jsonPointer = new JsonPointer($this->getJsonSchema());
        }

        return $this->jsonPointer;
    }
}
