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

use League\Uri\Uri;
use Nijens\OpenapiBundle\Json\Loader\LoaderInterface;
use stdClass;

/**
 * Dereferences a JSON schema by replacing $ref JSON pointers with a {@see Reference} value-object.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class Dereferencer implements DereferencerInterface
{
    /**
     * @var JsonPointerInterface
     */
    private $jsonPointer;

    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * Constructs a new {@see Dereferencer} instance.
     */
    public function __construct(JsonPointerInterface $jsonPointer, LoaderInterface $loader)
    {
        $this->jsonPointer = $jsonPointer;
        $this->loader = $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function dereference(stdClass $jsonSchema): stdClass
    {
        $jsonPointer = $this->jsonPointer->withJson($jsonSchema);

        $references = $this->searchReferences($jsonSchema);
        foreach ($references as $referenceLocationPointer => $referencePointer) {
            $reference = new Reference($referencePointer, $jsonSchema);
            if ($this->isExternalReference($referencePointer)) {
                $externalFile = (string) Uri::createFromString($referencePointer)->withFragment(null);
                $externalReferencePointer = '#'.Uri::createFromString($referencePointer)->getFragment();

                $externalJsonSchema = $this->dereference($this->loader->load($externalFile));

                $reference = new Reference($externalReferencePointer, $externalJsonSchema);
            }

            $schemaReference = &$jsonPointer->getByReference($referenceLocationPointer);
            $schemaReference = $reference;
        }

        return $jsonSchema;
    }

    /**
     * Returns the $ref JSON pointers within the provided JSON schema.
     */
    private function searchReferences(stdClass $jsonSchema, string $jsonPointer = '#'): array
    {
        $references = [];
        foreach ($jsonSchema as $key => $value) {
            if (is_object($value)) {
                $references = array_merge(
                    $references,
                    $this->searchReferences(
                        $value,
                        $this->jsonPointer->appendSegmentsToPointer($jsonPointer, $key)
                    )
                );

                continue;
            }

            if (is_array($value)) {
                foreach ($value as $arrayKey => $arrayValue) {
                    if (is_object($arrayValue) === false) {
                        continue;
                    }

                    $references = array_merge(
                        $references,
                        $this->searchReferences(
                            $arrayValue,
                            $this->jsonPointer->appendSegmentsToPointer($jsonPointer, $key, (string) $arrayKey)
                        )
                    );
                }

                continue;
            }

            if ($this->isReference($key, $value)) {
                $references[$jsonPointer] = $value;
            }
        }

        return $references;
    }

    /**
     * Returns true when the provided JSON key and value is a JSON reference.
     *
     * @param mixed $key
     * @param mixed $value
     */
    private function isReference($key, $value): bool
    {
        return $key === '$ref' && is_string($value);
    }

    /**
     * Returns true when the provided JSON key and value is an internal JSON reference.
     */
    private function isInternalReference(string $jsonPointer): bool
    {
        return substr($jsonPointer, 0, 1) === '#';
    }

    /**
     * Returns true when the provided JSON key and value is an external JSON reference.
     */
    private function isExternalReference(string $jsonPointer): bool
    {
        return $this->isInternalReference($jsonPointer) === false;
    }
}
