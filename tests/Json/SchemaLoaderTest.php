<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Tests\Json;

use League\JsonReference\DereferencerInterface;
use Nijens\OpenapiBundle\Json\SchemaLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Resource\FileResource;

/**
 * SchemaLoaderTest.
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
     * @var MockObject
     */
    private $fileLocatorMock;

    /**
     * @var MockObject
     */
    private $dereferencerMock;

    /**
     * Creates a new SchemaLoader instance for testing.
     */
    protected function setUp()
    {
        $this->fileLocatorMock = $this->getMockBuilder(FileLocatorInterface::class)
            ->getMock();

        $this->dereferencerMock = $this->getMockBuilder(DereferencerInterface::class)
            ->getMock();

        $this->schemaLoader = new SchemaLoader($this->fileLocatorMock, $this->dereferencerMock);
    }

    /**
     * Tests if constructing a new SchemaLoader instance sets the instance properties.
     */
    public function testConstruct()
    {
        $this->assertAttributeSame($this->fileLocatorMock, 'fileLocator', $this->schemaLoader);
        $this->assertAttributeSame($this->dereferencerMock, 'dereferencer', $this->schemaLoader);
    }

    /**
     * Tests if SchemaLoader::load locates and dereferences an JSON schema file.
     *
     * @depends testConstruct
     */
    public function testLoad()
    {
        $dereferenceJson = new stdClass();
        $dereferenceJson->openapi = '3.0.0';

        $this->fileLocatorMock->expects($this->once())
            ->method('locate')
            ->with('openapi.json')
            ->willReturn('config/openapi.json');

        $this->dereferencerMock->expects($this->once())
            ->method('dereference')
            ->with('file://config/openapi.json')
            ->willReturn($dereferenceJson);

        $schema = $this->schemaLoader->load('openapi.json');

        $this->assertEquals($dereferenceJson, $schema);
    }

    /**
     * Tests if SchemaLoader::getFileResource returns a FileResource for a loaded JSON schema file.
     *
     * @depends testLoad
     */
    public function testGetFileResource()
    {
        $dereferenceJson = new stdClass();
        $dereferenceJson->openapi = '3.0.0';

        $this->fileLocatorMock->expects($this->exactly(2))
            ->method('locate')
            ->with('route-loader-minimal.json')
            ->willReturn(__DIR__.'/../Resources/specifications/route-loader-minimal.json');

        $this->dereferencerMock->expects($this->once())
            ->method('dereference')
            ->with('file://'.__DIR__.'/../Resources/specifications/route-loader-minimal.json')
            ->willReturn($dereferenceJson);

        $this->schemaLoader->load('route-loader-minimal.json');

        $fileResource = $this->schemaLoader->getFileResource('route-loader-minimal.json');

        $this->assertInstanceOf(FileResource::class, $fileResource);
    }

    /**
     * Tests if SchemaLoader::getFileResource returns for a file not loaded by the SchemaLoader.
     *
     * @depends testLoad
     */
    public function testGetFileResourceReturnsNull()
    {
        $this->fileLocatorMock->expects($this->once())
            ->method('locate')
            ->with('route-loader-minimal.json')
            ->willReturn(__DIR__.'/../Resources/specifications/route-loader-minimal.json');

        $fileResource = $this->schemaLoader->getFileResource('route-loader-minimal.json');

        $this->assertNull($fileResource);
    }
}
