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
                            'owner' => [
                                '$ref' => '#/components/schemas/Human',
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

    public function testCanCreateContextForObjectSchemaWithoutProperties(): void
    {
        $schema = $this->convertToObject([
            'components' => [
                'schemas' => [
                    'Pet' => [
                        'type' => 'object',
                    ],
                ],
            ],
        ]);

        $this->schemaLoader->setSchema($schema);

        $this->assertSame(
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                AbstractNormalizer::ATTRIBUTES => [],
            ],
            $this->serializationContextBuilder->getContextForSchemaObject('Pet', '')
        );
    }

    /**
     * @dataProvider provideObjectSchemaWithAdditionalProperties
     */
    public function testCanCreateContextForObjectSchemaWithAdditionalProperties(
        stdClass $schema,
        array $expectedAttributes,
    ): void {
        $this->schemaLoader->setSchema($schema);

        $this->assertSame(
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                AbstractNormalizer::ATTRIBUTES => $expectedAttributes,
            ],
            $this->serializationContextBuilder->getContextForSchemaObject('Pet', '')
        );
    }

    public function provideObjectSchemaWithAdditionalProperties(): iterable
    {
        yield [
            $this->convertToObject([
                'components' => [
                    'schemas' => [
                        'Pet' => [
                            'type' => 'object',
                            'properties' => [
                                'translations' => [
                                    'type' => 'object',
                                    'additionalProperties' => [
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
                    ],
                ],
            ]),
            [
                'translations' => [
                    'name',
                ],
            ],
        ];

        yield [
            $this->convertToObject([
                'components' => [
                    'schemas' => [
                        'Pet' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                        ],
                    ],
                ],
            ]),
            [],
        ];
    }

    public function testCannotAddPropertyOfObjectTypeWithoutPropertiesToContext(): void
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
                            'owner' => [
                                '$ref' => '#/components/schemas/Human',
                            ],
                        ],
                    ],
                    'Human' => [
                        'type' => 'object',
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
                    'owner' => [],
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

    public function testCanCreateContextForArraySchemaInObjectSchema(): void
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
                            'owners' => [
                                'type' => 'array',
                                'items' => [
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
                    'owners' => [
                        'firstName',
                        'lastName',
                    ],
                ],
            ],
            $this->serializationContextBuilder->getContextForSchemaObject('Pet', '')
        );
    }

    public function testCanCreateContextForCombinedObjectSchema(): void
    {
        $schema = $this->convertToObject([
            'components' => [
                'schemas' => [
                    'Pet' => [
                        'allOf' => [
                            [
                                'type' => 'object',
                                'properties' => [
                                    'id' => [
                                        'type' => 'integer',
                                        'format' => 'int64',
                                    ],
                                ],
                            ],
                            [
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
            ],
        ]);

        $this->schemaLoader->setSchema($schema);

        $this->assertSame(
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                AbstractNormalizer::ATTRIBUTES => [
                    'id',
                    'name',
                ],
            ],
            $this->serializationContextBuilder->getContextForSchemaObject('Pet', '')
        );
    }

    public function testCanCreateContextForReferencedCombinedObjectSchema(): void
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
                            'owner' => [
                                '$ref' => '#/components/schemas/Human',
                            ],
                        ],
                    ],
                    'Robot' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                    'Human' => [
                        'allOf' => [
                            [
                                '$ref' => '#/components/schemas/Robot',
                            ],
                            [
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
            ],
        ]);
        $schema->components->schemas->Pet->properties->owner = new Reference('#/components/schemas/Human', $schema);
        $schema->components->schemas->Human->allOf[0] = new Reference('#/components/schemas/Robot', $schema);

        $this->schemaLoader->setSchema($schema);

        $this->assertSame(
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                AbstractNormalizer::ATTRIBUTES => [
                    'name',
                    'owner' => [
                        'id',
                        'name',
                    ],
                ],
            ],
            $this->serializationContextBuilder->getContextForSchemaObject('Pet', '')
        );
    }

    public function testCanCreateContextForReferencedCombinedObjectSchemaWithoutType(): void
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
                            'owner' => [
                                '$ref' => '#/components/schemas/Human',
                            ],
                        ],
                    ],
                    'Robot' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                    'Human' => [
                        'allOf' => [
                            [
                                'description' => 'We are human after all.',
                            ],
                            [
                                '$ref' => '#/components/schemas/Robot',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $schema->components->schemas->Pet->properties->owner = new Reference('#/components/schemas/Human', $schema);
        $schema->components->schemas->Human->allOf[1] = new Reference('#/components/schemas/Robot', $schema);

        $this->schemaLoader->setSchema($schema);

        $this->assertSame(
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                AbstractNormalizer::ATTRIBUTES => [
                    'name',
                    'owner' => [
                        'id',
                    ],
                ],
            ],
            $this->serializationContextBuilder->getContextForSchemaObject('Pet', '')
        );
    }

    public function testCanCreateContextForReferencedCombinedObjectSchemaWithAnyOfAndOneOfTreatedAsAllOf(): void
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
                            'owner' => [
                                'anyOf' => [
                                    [
                                        '$ref' => '#/components/schemas/Robot',
                                    ],
                                    [
                                        '$ref' => '#/components/schemas/Human',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'Robot' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                    'Human' => [
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
        $schema->components->schemas->Pet->properties->owner->anyOf[0] = new Reference('#/components/schemas/Robot', $schema);
        $schema->components->schemas->Pet->properties->owner->anyOf[1] = new Reference('#/components/schemas/Human', $schema);

        $this->schemaLoader->setSchema($schema);

        $this->assertSame(
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                AbstractNormalizer::ATTRIBUTES => [
                    'name',
                    'owner' => [
                        'id',
                        'name',
                    ],
                ],
            ],
            $this->serializationContextBuilder->getContextForSchemaObject('Pet', '', true)
        );
    }

    public function testCanCreateContextForReferencedCombinedObjectSchemaWithAnyOfAndOneOfNotTreatedAsAllOf(): void
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
                            'owner' => [
                                'anyOf' => [
                                    [
                                        '$ref' => '#/components/schemas/Robot',
                                    ],
                                    [
                                        '$ref' => '#/components/schemas/Human',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'Robot' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                        ],
                    ],
                    'Human' => [
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
        $schema->components->schemas->Pet->properties->owner->anyOf[0] = new Reference('#/components/schemas/Robot', $schema);
        $schema->components->schemas->Pet->properties->owner->anyOf[1] = new Reference('#/components/schemas/Human', $schema);

        $this->schemaLoader->setSchema($schema);

        $this->assertSame(
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                AbstractNormalizer::ATTRIBUTES => [
                    'name',
                    'owner',
                ],
            ],
            $this->serializationContextBuilder->getContextForSchemaObject('Pet', '')
        );
    }

    public function testCanCreateContextForUnimplementedJsonSchemaKeywordsWithoutErrors(): void
    {
        $schema = $this->convertToObject([
            'components' => [
                'schemas' => [
                    'Pet' => [
                        'oneOf' => [
                            [
                                'type' => 'object',
                                'properties' => [
                                    'id' => [
                                        'type' => 'integer',
                                        'format' => 'int64',
                                    ],
                                ],
                            ],
                            [
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
            ],
        ]);

        $this->schemaLoader->setSchema($schema);

        $this->assertSame(
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                AbstractNormalizer::ATTRIBUTES => [],
            ],
            $this->serializationContextBuilder->getContextForSchemaObject('Pet', '')
        );
    }

    private function convertToObject(array $schema): stdClass
    {
        return json_decode(json_encode($schema));
    }
}
