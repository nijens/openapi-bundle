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

namespace Nijens\OpenapiBundle\Routing;

use Nijens\OpenapiBundle\Controller\CatchAllController;
use Nijens\OpenapiBundle\Json\JsonPointer;
use Nijens\OpenapiBundle\Json\SchemaLoaderInterface;
use stdClass;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads the paths from an OpenAPI specification as routes.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class RouteLoader extends FileLoader
{
    /**
     * @var string
     */
    public const TYPE = 'openapi';

    /**
     * @var SchemaLoaderInterface
     */
    private $schemaLoader;

    /**
     * @var bool
     */
    private $useOperationIdAsRouteName;

    /**
     * Constructs a new RouteLoader instance.
     */
    public function __construct(
        FileLocatorInterface $locator,
        SchemaLoaderInterface $schemaLoader,
        bool $useOperationIdAsRouteName = false
    ) {
        parent::__construct($locator);

        $this->schemaLoader = $schemaLoader;
        $this->useOperationIdAsRouteName = $useOperationIdAsRouteName;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null): bool
    {
        return self::TYPE === $type;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null): RouteCollection
    {
        $file = $this->getLocator()->locate($resource, null, true);

        $schema = $this->schemaLoader->load($file);

        $jsonPointer = new JsonPointer($schema);

        $routeCollection = new RouteCollection();
        $routeCollection->addResource($this->schemaLoader->getFileResource($file));

        $paths = get_object_vars($jsonPointer->get('/paths'));
        foreach ($paths as $path => $pathItem) {
            $this->parsePathItem($jsonPointer, $file, $routeCollection, $path, $pathItem);
        }

        $this->addDefaultRoutes($routeCollection, $file);

        return $routeCollection;
    }

    /**
     * Parses a path item of the OpenAPI specification for a route.
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

            $this->parseOperation($jsonPointer, $resource, $collection, $path, $requestMethod, $operation, $pathItem);
        }
    }

    /**
     * Parses an operation of the OpenAPI specification for a route.
     */
    private function parseOperation(
        JsonPointer $jsonPointer,
        string $resource,
        RouteCollection $collection,
        string $path,
        string $requestMethod,
        stdClass $operation,
        stdClass $pathItem
    ): void {
        $defaults = [];
        $openapiRouteContext = [
            RouteContext::RESOURCE => $resource,
        ];

        $this->parseOpenapiBundleSpecificationExtension($operation, $defaults, $openapiRouteContext);
        $this->addRouteContextForValidation(
            $jsonPointer,
            $path,
            $requestMethod,
            $operation,
            $pathItem,
            $openapiRouteContext
        );

        $defaults[RouteContext::REQUEST_ATTRIBUTE] = $openapiRouteContext;

        $route = new Route($path, $defaults, []);
        $route->setMethods($requestMethod);

        $routeName = null;
        if ($this->useOperationIdAsRouteName && isset($operation->operationId)) {
            $routeName = $operation->operationId;
        }

        $collection->add(
            $routeName ?? $this->createRouteName($path, $requestMethod),
            $route
        );
    }

    private function parseOpenapiBundleSpecificationExtension(stdClass $operation, array &$defaults, array &$openapiRouteContext): void
    {
        if (isset($operation->{'x-openapi-bundle'}->controller)) {
            $defaults['_controller'] = $operation->{'x-openapi-bundle'}->controller;
        }

        if (isset($defaults['_controller']) === false && isset($operation->{'x-symfony-controller'})) {
            $defaults['_controller'] = $operation->{'x-symfony-controller'};
        }

        if (isset($operation->{'x-openapi-bundle'}->deserializationObject)) {
            $openapiRouteContext[RouteContext::DESERIALIZATION_OBJECT] = $operation->{'x-openapi-bundle'}->deserializationObject;
        }

        if (isset($operation->{'x-openapi-bundle'}->deserializationObjectArgumentName)) {
            $openapiRouteContext[RouteContext::DESERIALIZATION_OBJECT_ARGUMENT_NAME] = $operation->{'x-openapi-bundle'}->deserializationObjectArgumentName;
        }

        if (isset($operation->{'x-openapi-bundle'}->additionalRouteAttributes)) {
            $additionalRouteAttributes = get_object_vars($operation->{'x-openapi-bundle'}->additionalRouteAttributes);
            foreach ($additionalRouteAttributes as $key => $value) {
                $defaults[$key] = $value;
            }
        }
    }

    private function addRouteContextForValidation(
        JsonPointer $jsonPointer,
        string $path,
        string $requestMethod,
        stdClass $operation,
        stdClass $pathItem,
        array &$openapiRouteContext
    ): void {
        $openapiRouteContext[RouteContext::REQUEST_BODY_REQUIRED] = false;
        if (isset($operation->requestBody->required)) {
            $openapiRouteContext[RouteContext::REQUEST_BODY_REQUIRED] = $operation->requestBody->required;
        }

        $openapiRouteContext[RouteContext::REQUEST_ALLOWED_CONTENT_TYPES] = [];
        if (isset($operation->requestBody->content)) {
            $openapiRouteContext[RouteContext::REQUEST_ALLOWED_CONTENT_TYPES] = array_keys(
                get_object_vars($operation->requestBody->content)
            );
        }

        $openapiRouteContext[RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS] = [];
        $parameters = array_merge(
            $pathItem->parameters ?? [],
            $operation->parameters ?? []
        );
        foreach ($parameters as $parameter) {
            if ($parameter->in !== 'query') {
                continue;
            }

            $openapiRouteContext[RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS][$parameter->name] = $parameter;
        }

        if (isset($operation->requestBody->content->{'application/json'})) {
            $openapiRouteContext[RouteContext::JSON_REQUEST_VALIDATION_POINTER] = sprintf(
                '/paths/%s/%s/requestBody/content/%s/schema',
                $jsonPointer->escape($path),
                $requestMethod,
                $jsonPointer->escape('application/json')
            );
        }
    }

    /**
     * Returns true when the provided request method is a valid request method in the OpenAPI specification.
     */
    private function isValidRequestMethod(string $requestMethod): bool
    {
        return in_array(
            strtoupper($requestMethod),
            [
                Request::METHOD_GET,
                Request::METHOD_PUT,
                Request::METHOD_POST,
                Request::METHOD_DELETE,
                Request::METHOD_OPTIONS,
                Request::METHOD_HEAD,
                Request::METHOD_PATCH,
                Request::METHOD_TRACE,
            ]
        );
    }

    /**
     * Creates a route name based on the path and request method.
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
     */
    private function addDefaultRoutes(RouteCollection $collection, string $resource): void
    {
        $catchAllRoute = new Route(
            '/{catchall}',
            [
                '_controller' => CatchAllController::CONTROLLER_REFERENCE,
                RouteContext::REQUEST_ATTRIBUTE => [RouteContext::RESOURCE => $resource],
            ],
            ['catchall' => '.*']
        );

        $collection->add('catch_all', $catchAllRoute);
    }
}
