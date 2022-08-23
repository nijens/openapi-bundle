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

namespace Nijens\OpenapiBundle\Tests\Json;

use Nijens\OpenapiBundle\Json\Exception\InvalidArgumentException;
use Nijens\OpenapiBundle\Json\Reference;
use PHPUnit\Framework\TestCase;

class ReferenceTest extends TestCase
{
    /**
     * @var Reference
     */
    private $reference;

    private $jsonSchema;

    protected function setUp(): void
    {
        $this->jsonSchema = (object) [
            'foo' => (object) [
                'type' => 'object',
                'properties' => (object) [
                    'bar' => (object) [
                        'type' => 'string',
                    ],
                ],
            ],
            'components' => (object) [
                'baz' => (object) [
                    'type' => 'object',
                    'properties' => (object) [
                        'qux' => (object) [
                            'type' > 'string',
                        ],
                    ],
                ],
            ],
        ];

        $this->reference = new Reference('#/foo', $this->jsonSchema);
    }

    public function testCanGetPointer(): void
    {
        static::assertSame('#/foo', $this->reference->getPointer());
    }

    public function testCanGetJsonSchema(): void
    {
        static::assertSame($this->jsonSchema, $this->reference->getJsonSchema());
    }

    /**
     * @dataProvider provideResolveTestCases
     */
    public function testCanResolvePointerInJsonSchema(string $pointer, $expectedResult): void
    {
        $reference = new Reference($pointer, $this->jsonSchema);

        static::assertEquals($expectedResult, $reference->resolve());
    }

    public function testCanCheckExistenceOfProperty(): void
    {
        static::assertTrue($this->reference->has('type'));
        static::assertFalse($this->reference->has('required'));
    }

    public function testCanCheckExistenceOfPropertyThroughIssetMagicMethodProxy(): void
    {
        static::assertTrue(isset($this->reference->type));
        static::assertFalse(isset($this->reference->required));
    }

    public function testCanGetProperty(): void
    {
        static::assertSame('object', $this->reference->get('type'));
    }

    public function testCanGetPropertyThroughGetMagicMethodProxy(): void
    {
        static::assertSame('object', $this->reference->type);
    }

    public function testCannotGetNonExistingProperty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown property "required".');

        $this->reference->get('required');
    }

    public function testCannotGetNonExistingPropertyThroughGetMagicMethodProxy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown property "required".');

        $this->reference->required;
    }

    public function testCanJsonSerializeToJsonReferenceObject(): void
    {
        static::assertJsonStringEqualsJsonString('{"$ref": "#/foo"}', json_encode($this->reference));
    }

    public function provideResolveTestCases(): iterable
    {
        yield [
            '#/foo',
            (object) [
                'type' => 'object',
                'properties' => (object) [
                    'bar' => (object) [
                        'type' => 'string',
                    ],
                ],
            ],
        ];

        yield [
            '#/components/baz',
            (object) [
                'type' => 'object',
                'properties' => (object) [
                    'qux' => (object) [
                        'type' > 'string',
                    ],
                ],
            ],
        ];
    }
}
