<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Tests\Routing;

use League\JsonReference\Dereferencer;
use League\JsonReference\ReferenceSerializer\InlineReferenceSerializer;
use Nijens\OpenapiBundle\Json\SchemaLoader;
use Nijens\OpenapiBundle\Routing\RouteLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Exception\FileLoaderLoadException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * RouteLoaderTest.
 */
class RouteLoaderTest extends TestCase
{
    /**
     * @var RouteLoader
     */
    private $routeLoader;

    /**
     * @var SchemaLoader
     */
    private $schemaLoader;

    /**
     * Creates a new RouteLoader for testing.
     */
    protected function setUp()
    {
        $fileLocator = new FileLocator([
            __DIR__.'/../Resources/specifications/',
        ]);

        $dereferencer = new Dereferencer(null, new InlineReferenceSerializer());

        $this->schemaLoader = new SchemaLoader($fileLocator, $dereferencer);

        $this->routeLoader = new RouteLoader($this->schemaLoader);
    }

    /**
     * Tests if constructing a new RouteLoader sets the instance properties.
     */
    public function testConstruct()
    {
        $this->assertAttributeSame($this->schemaLoader, 'schemaLoader', $this->routeLoader);
    }

    /**
     * Tests if RouteLoader::supports only supports the openapi resource type.
     */
    public function testSupports()
    {
        $this->assertTrue($this->routeLoader->supports('route-loader-minimal.json', 'openapi'));
    }

    /**
     * Tests if RouteLoader::load loads the JSON path items and operations as routes.
     */
    public function testLoadMinimalFromJson()
    {
        $routes = $this->routeLoader->load('route-loader-minimal.json', 'openapi');
        $route = $routes->get('pets_get');

        $this->assertInstanceOf(Route::class, $route);

        $this->assertSame('/pets', $route->getPath());
        $this->assertSame([Request::METHOD_GET], $route->getMethods());
        $this->assertSame('route-loader-minimal.json', $route->getDefaults()['_nijens_openapi']['openapi_resource']);
    }

    /**
     * Tests if RouteLoader::load loads the YAML path items and operations as routes.
     */
    public function testLoadMinimalFromYaml()
    {
        $routes = $this->routeLoader->load('route-loader-minimal.yaml', 'openapi');
        $route = $routes->get('pets_get');

        $this->assertInstanceOf(Route::class, $route);

        $this->assertSame('/pets', $route->getPath());
        $this->assertSame([Request::METHOD_GET], $route->getMethods());
        $this->assertSame('route-loader-minimal.yaml', $route->getDefaults()['_nijens_openapi']['openapi_resource']);
    }

    /**
     * Tests if RouteLoader::load loads the YML path items and operations as routes.
     */
    public function testLoadMinimalFromYml()
    {
        $routes = $this->routeLoader->load('route-loader-minimal.yml', 'openapi');
        $route = $routes->get('pets_get');

        $this->assertInstanceOf(Route::class, $route);

        $this->assertSame('/pets', $route->getPath());
        $this->assertSame([Request::METHOD_GET], $route->getMethods());
        $this->assertSame('route-loader-minimal.yml', $route->getDefaults()['_nijens_openapi']['openapi_resource']);
    }

    /**
     * Tests if RouteLoader::load throws an exception when resource does not have a valid YAML or JSON extension.
     */
    public function testLoadFromUnsupportedExtension()
    {
        $this->expectException(FileLoaderLoadException::class);
        $this->routeLoader->load('route-loader-minimal.txt', 'openapi');
    }

    /**
     * Tests if RouteLoader::load adds a 'openapi_json_request_validation_pointer' option
     * when the request body of an operation can be validated.
     *
     * @depends testLoadMinimalFromJson
     */
    public function testLoadWithValidationPointer()
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
     * Tests if RouteLoader::load adds a '_controller' default
     * when the 'x-symfony-controller' property of an operation is set.
     *
     * @depends testLoadMinimalFromJson
     */
    public function testLoadWithSymfonyControllerConfigured()
    {
        $routes = $this->routeLoader->load('route-loader-symfony-controller.json', 'openapi');
        $route = $routes->get('pets_uuid_put');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('Nijens\OpenapiBundle\Controller\FooController::bar', $route->getDefault('_controller'));
    }
}
