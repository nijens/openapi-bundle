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

    private function getAttributeContextFromSchemaObject($schemaObject): array
    {
        if ($schemaObject instanceof Reference) {
            $jsonPointer = new JsonPointer($schemaObject->getJsonSchema());
            $schemaObject = $jsonPointer->get($schemaObject->getPointer());
        }

        switch ($schemaObject->type) {
            case 'object':
                $objectContext = [];
                foreach ($schemaObject->properties as $propertyKey => $property) {
                    $propertyContext = $this->getAttributeContextFromSchemaObject($property);
                    if (empty($propertyContext)) {
                        $objectContext[] = $propertyKey;
                        continue;
                    }

                    $objectContext[$propertyKey] = $propertyContext;
                }

                return $objectContext;
            case 'array':
                return $this->getAttributeContextFromSchemaObject($schemaObject->items);
        }

        return [];
    }
}