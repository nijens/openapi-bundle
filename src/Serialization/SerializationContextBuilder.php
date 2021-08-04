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

namespace Nijens\OpenapiBundle\Serialization;

use Nijens\OpenapiBundle\Json\JsonPointer;
use Nijens\OpenapiBundle\Json\Reference;
use Nijens\OpenapiBundle\Json\SchemaLoaderInterface;
use stdClass;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Creates a serialization context for {@see SerializerInterface::serialize} based on the provided schema object name.
 *
 * @experimental
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class SerializationContextBuilder implements SerializationContextBuilderInterface
{
    /**
     * @var SchemaLoaderInterface
     */
    private $schemaLoader;

    public function __construct(SchemaLoaderInterface $schemaLoader)
    {
        $this->schemaLoader = $schemaLoader;
    }

    public function getContextForSchemaObject(string $schemaObjectName, string $openApiSpecificationFile): array
    {
        $jsonPointer = new JsonPointer($this->schemaLoader->load($openApiSpecificationFile));
        $schemaObject = $jsonPointer->get(sprintf('/components/schemas/%s', $schemaObjectName));

        return [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            AbstractNormalizer::ATTRIBUTES => $this->getAttributeContextFromSchemaObject($schemaObject),
        ];
    }

    /**
     * @param stdClass|Reference $schemaObject
     */
    private function getAttributeContextFromSchemaObject($schemaObject): array
    {
        $schemaObject = $this->dereference($schemaObject);

        if (isset($schemaObject->allOf)) {
            return $this->getAttributeContextFromCombinedSchemaObject($schemaObject);
        }

        if (isset($schemaObject->type) === false) {
            return [];
        }

        switch ($schemaObject->type) {
            case 'object':
                return $this->getAttributeContextFromSchemaObjectProperties($schemaObject);
            case 'array':
                return $this->getAttributeContextFromSchemaObject($schemaObject->items);
        }

        return [];
    }

    private function getAttributeContextFromCombinedSchemaObject(stdClass $schemaObject): array
    {
        $context = [];
        foreach ($schemaObject->allOf as $allOfSchemaObject) {
            $context = array_merge($context, $this->getAttributeContextFromSchemaObject($allOfSchemaObject));
        }

        return $context;
    }

    private function getAttributeContextFromSchemaObjectProperties(stdClass $schemaObject): array
    {
        $objectContext = [];
        $properties = $schemaObject->properties ?? [];
        foreach ($properties as $propertyKey => $property) {
            $propertyContext = $this->getAttributeContextFromSchemaObject($property);

            if ($this->isType($property, 'object') || count($propertyContext) > 0) {
                $objectContext[$propertyKey] = $propertyContext;

                continue;
            }

            $objectContext[] = $propertyKey;
        }

        if (isset($schemaObject->additionalProperties) && $schemaObject->additionalProperties !== false) {
            $objectContext = array_merge(
                $objectContext,
                $this->getAttributeContextFromSchemaObject($schemaObject->additionalProperties)
            );
        }

        return $objectContext;
    }

    /**
     * @param stdClass|Reference $schemaObject
     */
    private function dereference($schemaObject): stdClass
    {
        if ($schemaObject instanceof Reference === false) {
            return $schemaObject;
        }

        $jsonPointer = new JsonPointer($schemaObject->getJsonSchema());

        return $jsonPointer->get($schemaObject->getPointer());
    }

    /**
     * @param stdClass|Reference $schemaObject
     */
    private function isType($schemaObject, string $type): bool
    {
        $schemaObject = $this->dereference($schemaObject);

        return $schemaObject->type === $type;
    }
}
