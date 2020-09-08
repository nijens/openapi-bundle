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

use Nijens\OpenapiBundle\Json\DereferencerInterface;
use Nijens\OpenapiBundle\Json\Loader\LoaderInterface;
use Nijens\OpenapiBundle\Json\SchemaLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Config\Resource\FileResource;

/**
 * Tests the {@see SchemaLoader}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class SchemaLoaderTest extends TestCase
{
    /**
     * @var SchemaLoader
     */
    private $schemaLoader;

    /**
     * @var MockObject|LoaderInterface
     */
    private $loaderMock;

    /**
     * @var MockObject|DereferencerInterface
     */
    private $dereferencerMock;

    /**
     * Creates a new {@see SchemaLoader} instance for testing.
     */
    protected function setUp(): void
    {
        $this->loaderMock = $this->createMock(LoaderInterface::class);
        $this->dereferencerMock = $this->createMock(DereferencerInterface::class);

        $this->schemaLoader = new SchemaLoader(
            $this->loaderMock,
            $this->dereferencerMock
        );
    }

    /**
     * Tests if {@see SchemaLoader::load} locates and dereferences an JSON schema file.
     */
    public function testLoad(): void
    {
        $dereferenceJson = new stdClass();
        $dereferenceJson->openapi = '3.0.0';

        $this->loaderMock->expects($this->once())
            ->method('load')
            ->with('config/openapi.json')
            ->willReturn($dereferenceJson);

        $this->dereferencerMock->expects($this->once())
            ->method('dereference')
            ->with($dereferenceJson)
            ->willReturn($dereferenceJson);

        $schema = $this->schemaLoader->load('config/openapi.json');

        $this->assertEquals($dereferenceJson, $schema);
    }

    /**
     * Tests if {@see SchemaLoader::getFileResource} returns a {@see FileResource} for a loaded JSON schema file.
     *
     * @depends testLoad
     */
    public function testGetFileResource(): void
    {
        $dereferenceJson = new stdClass();
        $dereferenceJson->openapi = '3.0.0';

        $this->loaderMock->expects($this->any())
            ->method('load')
            ->willReturn($dereferenceJson);

        $this->dereferencerMock->expects($this->any())
            ->method('dereference')
            ->willReturn($dereferenceJson);

        $this->schemaLoader->load(__DIR__.'/../Resources/specifications/route-loader-minimal.json');

        $fileResource = $this->schemaLoader->getFileResource(__DIR__.'/../Resources/specifications/route-loader-minimal.json');

        $this->assertInstanceOf(FileResource::class, $fileResource);
    }

    /**
     * Tests if {@see SchemaLoader::getFileResource} returns null for a file not loaded by the {@see SchemaLoader}.
     *
     * @depends testLoad
     */
    public function testGetFileResourceReturnsNull(): void
    {
        $fileResource = $this->schemaLoader->getFileResource('route-loader-minimal.json');

        $this->assertNull($fileResource);
    }
}
