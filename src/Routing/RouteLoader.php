<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Routing;

use Nijens\OpenapiBundle\Controller\CatchAllController;
use Nijens\OpenapiBundle\Json\JsonPointer;
use Nijens\OpenapiBundle\Json\SchemaLoaderInterface;
use stdClass;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads the paths from an OpenAPI specification as routes.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class RouteLoader extends Loader
{
    /**
     * @var string
     */
    const TYPE = 'openapi';

    /**
     * @var SchemaLoaderInterface
     */
    private $schemaLoader;

    /**
     * Constructs a new RouteLoader instance.
     *
     * @param SchemaLoaderInterface $schemaLoader
     */
    public function __construct(SchemaLoaderInterface $schemaLoader)
    {
        $this->schemaLoader = $schemaLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return self::TYPE === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null): RouteCollection
    {
        $schema = $this->schemaLoader->load($resource);

        $jsonPointer = new JsonPointer($schema);

        $routeCollection = new RouteCollection();
        $routeCollection->addResource($this->schemaLoader->getFileResource($resource));

        $paths = get_object_vars($jsonPointer->get('/paths'));
        foreach ($paths as $path => $pathItem) {
            $this->parsePathItem($jsonPointer, $resource, $routeCollection, $path, $pathItem);
        }

        $this->addDefaultRoutes($routeCollection, $resource);

        return $routeCollection;
    }

    /**
     * Parses a path item of the OpenAPI specification for a route.
     *
     * @param JsonPointer     $jsonPointer
     * @param string          $resource
     * @param RouteCollection $collection
     * @param string          $path
     * @param stdClass        $pathItem
     */
    private function parsePathItem(
        JsonPointer $jsonPointer,
        string $resource,
        RouteCollection $collection,
        string $path,
        stdClass $pathItem
    ): void {
        $operations = get_object_vars($pathItem);
        foreach ($operations as $requestMethod => $operation) {
            if ($this->isValidRequestMethod($requestMethod) === false) {
                return;
            }

            $this->parseOperation($jsonPointer, $resource, $collection, $path, $requestMethod, $operation);
        }
    }

    /**
     * Parses an operation of the OpenAPI specification for a route.
     *
     * @param JsonPointer     $jsonPointer
     * @param string          $resource
     * @param RouteCollection $collection
     * @param string          $path
     * @param string          $requestMethod
     * @param stdClass        $operation
     */
    private function parseOperation(
        JsonPointer $jsonPointer,
        string $resource,
        RouteCollection $collection,
        string $path,
        string $requestMethod,
        stdClass $operation
    ): void {
        $defaults = array();
        $openApiConfiguration = array(
            'openapi_resource' => $resource,
        );

        if (isset($operation->{'x-symfony-controller'})) {
            $defaults['_controller'] = $operation->{'x-symfony-controller'};
        }

        if (isset($operation->requestBody->content->{'application/json'})) {
            $openApiConfiguration['openapi_json_request_validation_pointer'] = sprintf(
                '/paths/%s/%s/requestBody/content/%s/schema',
                $jsonPointer->escape($path),
                $requestMethod,
                $jsonPointer->escape('application/json')
            );
        }

        $defaults['_nijens_openapi'] = $openApiConfiguration;

        $route = new Route($path, $defaults, array());
        $route->setMethods($requestMethod);

        $collection->add(
            $this->createRouteName($path, $requestMethod),
            $route
        );
    }

    /**
     * Returns true when the provided request method is a valid request method in the OpenAPI specification.
     *
     * @param string $requestMethod
     *
     * @return bool
     */
    private function isValidRequestMethod(string $requestMethod): bool
    {
        return in_array(
            strtoupper($requestMethod),
            array(
                Request::METHOD_GET,
                Request::METHOD_PUT,
                Request::METHOD_POST,
                Request::METHOD_DELETE,
                Request::METHOD_OPTIONS,
                Request::METHOD_HEAD,
                Request::METHOD_PATCH,
                Request::METHOD_TRACE,
            )
        );
    }

    /**
     * Creates a route name based on the path and request method.
     *
     * @param string $path
     * @param string $requestMethod
     *
     * @return string
     */
    private function createRouteName(string $path, string $requestMethod): string
    {
        return sprintf('%s_%s',
            trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $path), '_'),
            $requestMethod
        );
    }

    /**
     * Adds a catch-all route to handle responses for non-existing routes.
     *
     * @param RouteCollection $collection
     * @param string          $resource
     */
    private function addDefaultRoutes(RouteCollection $collection, string $resource)
    {
        $catchAllRoute = new Route(
            '/{catchall}',
            array('_controller' => CatchAllController::CONTROLLER_REFERENCE),
            array('catchall' => '.+'),
            array('openapi_resource' => $resource)
        );

        $collection->add('catch_all', $catchAllRoute);
    }
}
