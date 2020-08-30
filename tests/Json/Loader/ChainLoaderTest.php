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

namespace Nijens\OpenapiBundle\Tests\Json\Loader;

use Nijens\OpenapiBundle\Json\Exception\LoaderLoadException;
use Nijens\OpenapiBundle\Json\Loader\ChainLoader;
use Nijens\OpenapiBundle\Json\Loader\LoaderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests the {@see ChainLoader}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class ChainLoaderTest extends TestCase
{
    /**
     * @var ChainLoader
     */
    private $loader;

    /**
     * @var MockObject|LoaderInterface
     */
    private $loaderMock;

    /**
     * Creates a new {@see ChainLoader} instance for testing.
     */
    protected function setUp(): void
    {
        $this->loaderMock = $this->createMock(LoaderInterface::class);

        $this->loader = new ChainLoader([$this->loaderMock]);
    }

    /**
     * Tests if {@see ChainLoader::supports} returns the expected boolean result for the provided file.
     *
     * @dataProvider provideSupports
     */
    public function testSupports(string $file, bool $expectedResult): void
    {
        $this->loaderMock->expects($this->once())
            ->method('supports')
            ->with($file)
            ->willReturnMap($this->provideSupports());

        self::assertSame($expectedResult, $this->loader->supports($file));
    }

    /**
     * Return the test cases for {@see ChainLoaderTest::testSupports}.
     */
    public function provideSupports(): array
    {
        return [
            [__DIR__.'/../../Resources/json-loader.json', true],
            [__DIR__.'/../../Resources/yaml-loader.yaml', false],
            [__DIR__.'/../../Resources/yaml-loader.yml', false],
        ];
    }

    /**
     * Tests if {@see ChainLoader::load} returns the expected decoded YAML.
     */
    public function testLoad(): void
    {
        $file = 'json-loader.json';
        $json = (object) ['loaded' => true];

        $this->loaderMock->expects($this->once())
            ->method('supports')
            ->with($file)
            ->willReturn(true);
        $this->loaderMock->expects($this->once())
            ->method('load')
            ->with($file)
            ->willReturn($json);

        self::assertSame($json, $this->loader->load($file));
    }

    /**
     * Tests if {@see ChainLoader::load} throws a {@see LoaderLoadException} when
     * no loader is available to load the JSON schema file.
     */
    public function testLoadThrowsLoaderLoadExceptionWhenNoLoaderAvailable(): void
    {
        $file = 'yaml-loader.yaml';

        $this->loaderMock->expects($this->once())
            ->method('supports')
            ->with($file)
            ->willReturn(false);
        $this->loaderMock->expects($this->never())
            ->method('load');

        $this->expectException(LoaderLoadException::class);
        $this->expectExceptionMessage('No loader available to load "yaml-loader.yaml".');

        $this->loader->load($file);
    }
}
