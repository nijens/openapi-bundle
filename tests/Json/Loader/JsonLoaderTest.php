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
use Nijens\OpenapiBundle\Json\Loader\JsonLoader;
use PHPUnit\Framework\TestCase;

/**
 * Tests the {@see JsonLoader}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class JsonLoaderTest extends TestCase
{
    /**
     * @var JsonLoader
     */
    private $loader;

    /**
     * Creates a new {@see JsonLoader} instance for testing.
     */
    protected function setUp(): void
    {
        $this->loader = new JsonLoader();
    }

    /**
     * Tests if {@see JsonLoader::supports} returns the expected boolean result for the provided file.
     *
     * @dataProvider provideSupports
     */
    public function testSupports(string $file, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->loader->supports($file));
    }

    /**
     * Return the test cases for {@see JsonLoaderTest::testSupports}.
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
     * Tests if {@see JsonLoader::load} returns the expected decoded JSON.
     */
    public function testLoad(): void
    {
        self::assertEquals(
            (object) ['loaded' => true],
            $this->loader->load(__DIR__.'/../../Resources/json-loader.json')
        );
    }

    /**
     * Tests if {@see JsonLoader::load} throws a {@see LoaderLoadException} when
     * the JSON schema file is not found.
     */
    public function testLoadThrowsLoaderLoadExceptionWhenSchemaNotFound(): void
    {
        $this->expectException(LoaderLoadException::class);
        $this->expectExceptionMessage(
            'The JSON schema "does-not-exist.json" could not be found.'
        );

        $this->loader->load('does-not-exist.json');
    }

    /**
     * Tests if {@see JsonLoader::load} throws a {@see LoaderLoadException} when
     * the JSON schema file contains invalid JSON.
     */
    public function testLoadThrowsLoaderLoadExceptionWhenJsonInvalid(): void
    {
        $this->expectException(LoaderLoadException::class);
        $this->expectExceptionMessageMatches('/The JSON schema ".*" contains invalid JSON: Syntax error/');

        $this->loader->load(__DIR__.'/../../Resources/json-loader-invalid.json');
    }
}
