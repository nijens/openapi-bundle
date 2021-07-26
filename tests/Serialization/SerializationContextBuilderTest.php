<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Tests\Serialization;

use Nijens\OpenapiBundle\Json\Reference;
use Nijens\OpenapiBundle\Json\SchemaLoaderInterface;
use Nijens\OpenapiBundle\Serialization\SerializationContextBuilder;
use Nijens\OpenapiBundle\Tests\Json\SchemaLoaderMock;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Tests the {@see SerializationContextBuilder}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class SerializationContextBuilderTest extends TestCase
{
    /**
     * @var SerializationContextBuilder
     */
    private $serializationContextBuilder;

    /**
     * @var SchemaLoaderInterface|SchemaLoaderMock
     */
    private $schemaLoader;

    protected function setUp(): void
    {
        $this->schemaLoader = new SchemaLoaderMock();

        $this->serializationContextBuilder = new SerializationContextBuilder($this->schemaLoader);
    }

    public function testCanCreateContextForObjectSchema(): void
    {
        $schema = $this->convertToObject([
            'components' => [
                'schemas' => [
                    'Pet' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->schemaLoader->setSchema($schema);

        $this->assertSame(
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                AbstractNormalizer::ATTRIBUTES => [
                    'name',
                ],
            ],
            $this->serializationContextBuilder->getContextForSchemaObject('Pet', '')
        );
    }

    public function testCanCreateContextForObjectSchemaWithReference(): void
    {
        $schema = $this->convertToObject([
            'components' => [
                'schemas' => [
                    'Pet' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'Human' => [
                        'type' => 'object',
                        'properties' => [
                            'firstName' => [
                                'type' => 'string',
                            ],
                            'lastName' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $schema->components->schemas->Pet->properties->owner = new Reference('#/components/schemas/Human', $schema);

        $this->schemaLoader->setSchema($schema);

        $this->assertSame(
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                AbstractNormalizer::ATTRIBUTES => [
                    'name',
                    'owner' => [
                        'firstName',
                        'lastName',
                    ],
                ],
            ],
            $this->serializationContextBuilder->getContextForSchemaObject('Pet', '')
        );
    }

    public function testCanCreateContextForArraySchema(): void
    {
        $schema = $this->convertToObject([
            'components' => [
                'schemas' => [
                    'PetList' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->schemaLoader->setSchema($schema);

        $this->assertSame(
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                AbstractNormalizer::ATTRIBUTES => [
                    'name',
                ],
            ],
            $this->serializationContextBuilder->getContextForSchemaObject('PetList', '')
        );
    }

    private function convertToObject(array $schema): stdClass
    {
        return json_decode(json_encode($schema));
    }
}
