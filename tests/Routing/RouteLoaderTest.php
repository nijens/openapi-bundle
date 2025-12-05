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
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Routing\RouteLoader;
use Nijens\OpenapiBundle\Tests\Functional\App\Controller\CreatePetController;
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
        $fileLocator = $this->createFileLocator();
        $schemaLoader = $this->createSchemaLoader();

        $this->routeLoader = new RouteLoader($fileLocator, $schemaLoader);
    }

    /**
     * Tests if {@see RouteLoader::supports} only supports the openapi resource type.
     */
    public function testSupports(): void
    {
        static::assertTrue($this->routeLoader->supports('route-loader-minimal.json', 'openapi'));
    }

    /**
     * Tests if {@see RouteLoader::load} loads the JSON path items and operations as routes.
     */
    public function testLoadMinimalFromJson(): void
    {
        $routes = $this->routeLoader->load('route-loader-minimal.json', 'openapi');
        $route = $routes->get('pets_get');

        static::assertInstanceOf(Route::class, $route);

        static::assertSame('/pets', $route->getPath());
        static::assertSame([Request::METHOD_GET], $route->getMethods());
        static::assertSame(
            __DIR__.'/../Resources/specifications/route-loader-minimal.json',
            $route->getDefault(RouteContext::REQUEST_ATTRIBUTE)[RouteContext::RESOURCE]
        );
    }

    /**
     * Tests if {@see RouteLoader::load} loads the YAML path items and operations as routes.
     */
    public function testLoadMinimalFromYaml(): void
    {
        $routes = $this->routeLoader->load('route-loader-minimal.yaml', 'openapi');
        $route = $routes->get('pets_get');

        $this->assertInstanceOf(Route::class, $route);

        static::assertSame('/pets', $route->getPath());
        static::assertSame([Request::METHOD_GET], $route->getMethods());
        static::assertSame(
            __DIR__.'/../Resources/specifications/route-loader-minimal.yaml',
            $route->getDefault(RouteContext::REQUEST_ATTRIBUTE)[RouteContext::RESOURCE]
        );
    }

    /**
     * Tests if {@see RouteLoader::load} loads the YML path items and operations as routes.
     */
    public function testLoadMinimalFromYml(): void
    {
        $routes = $this->routeLoader->load('route-loader-minimal.yml', 'openapi');
        $route = $routes->get('pets_get');

        $this->assertInstanceOf(Route::class, $route);

        static::assertSame('/pets', $route->getPath());
        static::assertSame([Request::METHOD_GET], $route->getMethods());
        static::assertSame(
            __DIR__.'/../Resources/specifications/route-loader-minimal.yml',
            $route->getDefault(RouteContext::REQUEST_ATTRIBUTE)[RouteContext::RESOURCE]
        );
    }

    /**
     * Tests if {@see RouteLoader::load} throws an exception when resource does not have a valid YAML or JSON extension.
     */
    public function testLoadFromUnsupportedExtension(): void
    {
        $this->expectException(LoaderLoadException::class);

        $this->routeLoader->load('route-loader-minimal.txt', 'openapi');
    }

    public function testCanLoadRoutesWithRouteContextForRequestParameterValidation(): void
    {
        $routes = $this->routeLoader->load('route-loader-request-validation.yaml', 'openapi');
        $route = $routes->get('pets_get');

        static::assertInstanceOf(Route::class, $route);
        static::assertEquals(
            [
                RouteContext::RESOURCE => __DIR__.'/../Resources/specifications/route-loader-request-validation.yaml',
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => [],
                RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS => [
                    'foo' => json_encode([
                        'name' => 'foo',
                        'in' => 'query',
                        'schema' => [
                            'type' => 'string',
                        ],
                    ]),
                ],
                RouteContext::REQUEST_VALIDATE_HEADER_PARAMETERS => [
                    'bar' => json_encode([
                        'name' => 'bar',
                        'in' => 'header',
                        'schema' => [
                            'type' => 'string',
                        ],
                    ]),
                ],
            ],
            $route->getDefault(RouteContext::REQUEST_ATTRIBUTE)
        );
    }

    public function testCanLoadRoutesWithRouteContextForRequestBodyValidation(): void
    {
        $routes = $this->routeLoader->load('route-loader-request-validation.yaml', 'openapi');
        $route = $routes->get('pets_put');

        static::assertInstanceOf(Route::class, $route);
        static::assertSame(
            [
                RouteContext::RESOURCE => __DIR__.'/../Resources/specifications/route-loader-request-validation.yaml',
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => ['application/json'],
                RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS => [],
                RouteContext::REQUEST_VALIDATE_HEADER_PARAMETERS => [],
                RouteContext::REQUEST_BODY_SCHEMA => 'O:8:"stdClass":2:{s:4:"type";s:6:"object";s:10:"properties";O:8:"stdClass":2:{s:2:"id";O:8:"stdClass":4:{s:4:"type";s:7:"integer";s:6:"format";s:5:"int32";s:8:"readOnly";b:1;s:7:"example";i:1;}s:4:"name";O:8:"stdClass":2:{s:4:"type";s:6:"string";s:7:"example";s:3:"Dog";}}}',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
            ],
            $route->getDefault(RouteContext::REQUEST_ATTRIBUTE)
        );
    }

    public function testCanUseOperationIdAsRouteName(): void
    {
        $fileLocator = $this->createFileLocator();
        $schemaLoader = $this->createSchemaLoader();

        $this->routeLoader = new RouteLoader($fileLocator, $schemaLoader, true);

        $routes = $this->routeLoader->load('route-loader-symfony-controller.json', 'openapi');
        $route = $routes->get('createPet');

        static::assertInstanceOf(Route::class, $route);
    }

    public function testCanLoadRouteWithControllerFromOpenapiBundleSpecificationExtension(): void
    {
        $routes = $this->routeLoader->load('route-loader-load-openapi-bundle-extension.yaml', 'openapi');
        $route = $routes->get('pets_post');

        static::assertInstanceOf(Route::class, $route);
        static::assertSame(CreatePetController::class, $route->getDefault('_controller'));
    }

    public function testCanLoadRouteWithAdditionalRouteAttributesFromOpenapiBundleSpecificationExtension(): void
    {
        $routes = $this->routeLoader->load(
            'route-loader-load-openapi-bundle-extension-additional-attributes.yaml',
            'openapi'
        );
        $route = $routes->get('pets_post');

        static::assertInstanceOf(Route::class, $route);
        static::assertSame('Pet', $route->getDefault('responseSerializationSchemaObject'));
        static::assertSame('bar', $route->getDefault('foo'));
    }

    private function createFileLocator(): FileLocator
    {
        return new FileLocator([
            __DIR__.'/../Resources/specifications',
        ]);
    }

    private function createSchemaLoader(): SchemaLoader
    {
        $loader = new ChainLoader([new JsonLoader(), new YamlLoader()]);
        $dereferencer = new Dereferencer(new JsonPointer(), $loader);

        return new SchemaLoader($loader, $dereferencer);
    }
}
