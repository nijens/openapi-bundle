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
use Nijens\OpenapiBundle\Routing\RouteLoader;
use PHPUnit\Framework\TestCase;
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
     * @var FileLocator
     */
    private $fileLocator;

    /**
     * @var Dereferencer
     */
    private $dereferencer;

    /**
     * Creates a new RouteLoader for testing.
     */
    protected function setUp()
    {
        $this->fileLocator = new FileLocator(array(
            __DIR__.'/../Resources/specifications/',
        ));

        $this->dereferencer = new Dereferencer();

        $this->routeLoader = new RouteLoader($this->fileLocator, $this->dereferencer);
    }

    /**
     * Tests if constructing a new RouteLoader sets the instance properties.
     */
    public function testConstruct()
    {
        $this->assertAttributeSame($this->fileLocator, 'fileLocator', $this->routeLoader);
        $this->assertAttributeSame($this->dereferencer, 'dereferencer', $this->routeLoader);
    }

    /**
     * Tests if RouteLoader::supports only supports the openapi resource type.
     */
    public function testSupports()
    {
        $this->assertTrue($this->routeLoader->supports('route-loader-minimal.json', 'openapi'));
    }

    /**
     * Tests if RouteLoader::load loads the path items and operations as routes.
     */
    public function testLoadMinimal()
    {
        $routes = $this->routeLoader->load('route-loader-minimal.json', 'openapi');
        $route = $routes->get('pets_get');

        $this->assertInstanceOf(Route::class, $route);

        $this->assertSame('/pets', $route->getPath());
        $this->assertSame(array(Request::METHOD_GET), $route->getMethods());
        $this->assertFileEquals(
            __DIR__.'/../Resources/specifications/route-loader-minimal.json',
            $route->getOption('openapi_resource')
        );
    }

    /**
     * Tests if RouteLoader::load adds a 'openapi_json_request_validation_pointer' option
     * when the request body of an operation can be validated.
     *
     * @depends testLoadMinimal
     */
    public function testLoadWithValidationPointer()
    {
        $routes = $this->routeLoader->load('route-loader-validation-pointer.json', 'openapi');
        $route = $routes->get('pets_put');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(
            '/paths/~1pets/put/requestBody/content/application~1json/schema',
            $route->getOption('openapi_json_request_validation_pointer')
        );
    }

    /**
     * Tests if RouteLoader::load adds a '_controller' default
     * when the 'x-symfony-controller' property of an operation is set.
     *
     * @depends testLoadMinimal
     */
    public function testLoadWithSymfonyControllerConfigured()
    {
        $routes = $this->routeLoader->load('route-loader-symfony-controller.json', 'openapi');
        $route = $routes->get('pets_uuid_put');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('Nijens\OpenapiBundle\Controller\FooController::bar', $route->getDefault('_controller'));
    }
}
