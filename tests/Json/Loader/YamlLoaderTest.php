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
use Nijens\OpenapiBundle\Json\Loader\YamlLoader;
use PHPUnit\Framework\TestCase;

/**
 * Tests the {@see YamlLoader}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class YamlLoaderTest extends TestCase
{
    /**
     * @var YamlLoader
     */
    private $loader;

    /**
     * Creates a new {@see YamlLoader} instance for testing.
     */
    protected function setUp(): void
    {
        $this->loader = new YamlLoader();
    }

    /**
     * Tests if {@see YamlLoader::supports} returns the expected boolean result for the provided file.
     *
     * @dataProvider provideSupports
     */
    public function testSupports(string $file, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->loader->supports($file));
    }

    /**
     * Return the test cases for {@see YamlLoaderTest::testSupports}.
     */
    public function provideSupports(): array
    {
        return [
            [__DIR__.'/../../Resources/yaml-loader.yaml', true],
            [__DIR__.'/../../Resources/yaml-loader.yml', true],
            [__DIR__.'/../../Resources/json-loader.json', false],
        ];
    }

    /**
     * Tests if {@see YamlLoader::load} returns the expected decoded YAML.
     */
    public function testLoad(): void
    {
        self::assertEquals(
            (object) ['loaded' => true],
            $this->loader->load(__DIR__.'/../../Resources/yaml-loader.yaml')
        );
    }

    /**
     * Tests if {@see YamlLoader::load} throws a {@see LoaderLoadException} when
     * the JSON schema file contains invalid YAML.
     */
    public function testLoadThrowsLoaderLoadExceptionWhenYamlParserThrowsParseException(): void
    {
        $this->expectException(LoaderLoadException::class);
        $this->expectExceptionMessage('File "does-not-exist.yaml" does not exist.');

        $this->loader->load('does-not-exist.yaml');
    }
}
