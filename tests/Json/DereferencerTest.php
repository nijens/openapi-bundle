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

use Nijens\OpenapiBundle\Json\Dereferencer;
use Nijens\OpenapiBundle\Json\JsonPointer;
use Nijens\OpenapiBundle\Json\Loader\LoaderInterface;
use Nijens\OpenapiBundle\Json\Reference;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests the {@see Dereferencer}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class DereferencerTest extends TestCase
{
    /**
     * @var Dereferencer
     */
    private $dereferencer;

    /**
     * @var MockObject|LoaderInterface
     */
    private $loaderMock;

    /**
     * Creates a new {@see Dereferencer} instance for testing.
     */
    protected function setUp(): void
    {
        $this->loaderMock = $this->createMock(LoaderInterface::class);

        $this->dereferencer = new Dereferencer(new JsonPointer(), $this->loaderMock);
    }

    /**
     * Tests if {@see Dereferencer::dereference} replaces the $ref with a {@see Reference} instance.
     */
    public function testDereference(): void
    {
        $jsonSchema = (object) [
            'foo' => (object) ['$ref' => '#/bar'],
            'bar' => 'baz',
            'qux' => [(object) ['$ref' => '#/bar']],
        ];

        $expectedDereferencedJsonSchema = clone $jsonSchema;
        $expectedDereferencedJsonSchema->foo = new Reference('#/bar', $expectedDereferencedJsonSchema);
        $expectedDereferencedJsonSchema->qux[0] = new Reference('#/bar', $expectedDereferencedJsonSchema);

        self::assertEquals($expectedDereferencedJsonSchema, $this->dereferencer->dereference($jsonSchema));
    }

    /**
     * Tests if {@see Dereferencer::dereference} replaces the external $ref with a {@see Reference} instance
     * and loads the external file.
     */
    public function testDereferenceLoadsExternalSchemas(): void
    {
        $jsonSchema = (object) [
            'foo' => (object) ['$ref' => 'external-file.json#/bar'],
        ];
        $externalJsonSchema = (object) [
            'bar' => 'baz',
        ];

        $this->loaderMock->expects($this->once())
            ->method('load')
            ->with('external-file.json')
            ->willReturn($externalJsonSchema);

        $expectedDereferencedJsonSchema = clone $jsonSchema;
        $expectedDereferencedJsonSchema->foo = new Reference('#/bar', $externalJsonSchema);

        self::assertEquals($expectedDereferencedJsonSchema, $this->dereferencer->dereference($jsonSchema));
    }
}
