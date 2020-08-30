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

namespace Nijens\OpenapiBundle\Tests\Routing;

use Nijens\OpenapiBundle\Json\Dereferencer;
use Nijens\OpenapiBundle\Json\Exception\LoaderLoadException;
use Nijens\OpenapiBundle\Json\JsonPointer;
use Nijens\OpenapiBundle\Json\Loader\ChainLoader;
use Nijens\OpenapiBundle\Json\Loader\JsonLoader;
use Nijens\OpenapiBundle\Json\Loader\YamlLoader;
use Nijens\OpenapiBundle\Json\SchemaLoader;
use Nijens\OpenapiBundle\Routing\RouteLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests the {@see RouteLoader}.
 */
class RouteLoaderTest extends TestCase
{
    /**
     * @var RouteLoader
     */
    private $routeLoader;

    /**
     * Creates a new {@see RouteLoader} for testing.
     */
    protected function setUp(): void
    {
        $fileLocator = new FileLocator([
            __DIR__.'/../Resources/specifications/',
        ]);
        $loader = new ChainLoader([new JsonLoader(), new YamlLoader()]);
        $dereferencer = new Dereferencer(new JsonPointer(), $loader);

        $schemaLoader = new SchemaLoader($fileLocator, $loader, $dereferencer);

        $this->routeLoader = new RouteLoader($schemaLoader);
    }

    /**
     * Tests if {@see RouteLoader::supports} only supports the openapi resource type.
     */
    public function testSupports(): void
    {
        $this->assertTrue($this->routeLoader->supports('route-loader-minimal.json', 'openapi'));
    }

    /**
     * Tests if {@see RouteLoader::load} loads the JSON path items and operations as routes.
     */
    public function testLoadMinimalFromJson(): void
    {
        $routes = $this->routeLoader->load('route-loader-minimal.json', 'openapi');
        $route = $routes->get('pets_get');

        $this->assertInstanceOf(Route::class, $route);

        $this->assertSame('/pets', $route->getPath());
        $this->assertSame([Request::METHOD_GET], $route->getMethods());
        $this->assertSame('route-loader-minimal.json', $route->getDefaults()['_nijens_openapi']['openapi_resource']);
    }

    /**
     * Tests if {@see RouteLoader::load} loads the YAML path items and operations as routes.
     */
    public function testLoadMinimalFromYaml(): void
    {
        $routes = $this->routeLoader->load('route-loader-minimal.yaml', 'openapi');
        $route = $routes->get('pets_get');

        $this->assertInstanceOf(Route::class, $route);

        $this->assertSame('/pets', $route->getPath());
        $this->assertSame([Request::METHOD_GET], $route->getMethods());
        $this->assertSame('route-loader-minimal.yaml', $route->getDefaults()['_nijens_openapi']['openapi_resource']);
    }

    /**
     * Tests if {@see RouteLoader::load} loads the YML path items and operations as routes.
     */
    public function testLoadMinimalFromYml(): void
    {
        $routes = $this->routeLoader->load('route-loader-minimal.yml', 'openapi');
        $route = $routes->get('pets_get');

        $this->assertInstanceOf(Route::class, $route);

        $this->assertSame('/pets', $route->getPath());
        $this->assertSame([Request::METHOD_GET], $route->getMethods());
        $this->assertSame('route-loader-minimal.yml', $route->getDefaults()['_nijens_openapi']['openapi_resource']);
    }

    /**
     * Tests if {@see RouteLoader::load} throws an exception when resource does not have a valid YAML or JSON extension.
     */
    public function testLoadFromUnsupportedExtension(): void
    {
        $this->expectException(LoaderLoadException::class);
        $this->routeLoader->load('route-loader-minimal.txt', 'openapi');
    }

    /**
     * Tests if {@see RouteLoader::load} adds a 'openapi_json_request_validation_pointer' option
     * when the request body of an operation can be validated.
     *
     * @depends testLoadMinimalFromJson
     */
    public function testLoadWithValidationPointer(): void
    {
        $routes = $this->routeLoader->load('route-loader-validation-pointer.json', 'openapi');
        $route = $routes->get('pets_put');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(
            '/paths/~1pets/put/requestBody/content/application~1json/schema',
            $route->getDefaults()['_nijens_openapi']['openapi_json_request_validation_pointer']
        );
    }

    /**
     * Tests if {@see RouteLoader::load} adds a '_controller' default
     * when the 'x-symfony-controller' property of an operation is set.
     *
     * @depends testLoadMinimalFromJson
     */
    public function testLoadWithSymfonyControllerConfigured(): void
    {
        $routes = $this->routeLoader->load('route-loader-symfony-controller.json', 'openapi');
        $route = $routes->get('pets_uuid_put');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('Nijens\OpenapiBundle\Controller\FooController::bar', $route->getDefault('_controller'));
    }
}
