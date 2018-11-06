<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\EventListener;

use Exception;
use JsonSchema\Validator;
use Nijens\OpenapiBundle\Exception\BadJsonRequestHttpException;
use Nijens\OpenapiBundle\Exception\InvalidRequestHttpException;
use Nijens\OpenapiBundle\Json\JsonPointer;
use Nijens\OpenapiBundle\Json\SchemaLoaderInterface;
use Seld\JsonLint\JsonParser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Validates a JSON request body for routes loaded through the OpenAPI specification.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class JsonRequestBodyValidationSubscriber implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var JsonParser
     */
    private $jsonParser;

    /**
     * @var SchemaLoaderInterface
     */
    private $schemaLoader;

    /**
     * @var Validator
     */
    private $jsonValidator;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            KernelEvents::REQUEST => array(
                array('validateRequestBody', 28),
            ),
        );
    }

    /**
     * Constructs a new JsonRequestBodyValidationSubscriber instance.
     *
     * @param RouterInterface       $router
     * @param JsonParser            $jsonParser
     * @param SchemaLoaderInterface $schemaLoader
     * @param Validator             $jsonValidator
     */
    public function __construct(
        RouterInterface $router,
        JsonParser $jsonParser,
        SchemaLoaderInterface $schemaLoader,
        Validator $jsonValidator
    ) {
        $this->router = $router;
        $this->jsonParser = $jsonParser;
        $this->schemaLoader = $schemaLoader;
        $this->jsonValidator = $jsonValidator;
    }

    /**
     * Validates the body of a request to an OpenAPI specification route. Throws an exception when validation failed.
     *
     * @param GetResponseEvent $event
     */
    public function validateRequestBody(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $requestContentType = $request->headers->get('Content-Type');

        $route = $this->router->getRouteCollection()->get(
            $request->attributes->get('_route')
        );

        if ($route instanceof Route === false) {
            return;
        }

        if ($route->hasOption('openapi_resource') === false || $route->hasOption('openapi_json_request_validation_pointer') === false) {
            return;
        }

        if ($requestContentType !== 'application/json') {
            throw new BadJsonRequestHttpException("The request content-type should be 'application/json'.");
        }

        $requestBody = $request->getContent();
        $decodedJsonRequestBody = $this->validateJsonRequestBody($requestBody);

        $this->validateJsonAgainstSchema($route, $decodedJsonRequestBody);
    }

    /**
     * Validates if the request body is valid JSON.
     *
     * @param string $requestBody
     *
     * @return mixed
     */
    private function validateJsonRequestBody(string $requestBody)
    {
        $decodedJsonRequestBody = json_decode($requestBody);
        if ($decodedJsonRequestBody !== null || $requestBody === 'null') {
            return $decodedJsonRequestBody;
        }

        $exception = $this->jsonParser->lint($requestBody);

        throw new BadJsonRequestHttpException('The request body should be valid JSON.', $exception);
    }

    /**
     * Validates the JSON request body against the JSON Schema within the OpenAPI specification.
     *
     * @param Route $route
     * @param mixed $decodedJsonRequestBody
     */
    private function validateJsonAgainstSchema(Route $route, $decodedJsonRequestBody): void
    {
        $schema = $this->schemaLoader->load($route->getOption('openapi_resource'));

        $jsonPointer = new JsonPointer($schema);
        $jsonSchema = $jsonPointer->get($route->getOption('openapi_json_request_validation_pointer'));

        $this->jsonValidator->validate($decodedJsonRequestBody, $jsonSchema);

        if ($this->jsonValidator->isValid() === false) {
            $validationErrors = $this->jsonValidator->getErrors();
            $this->jsonValidator->reset();

            $this->throwInvalidRequestException($validationErrors);
        }
    }

    /**
     * @param array $errors
     */
    private function throwInvalidRequestException(array $errors): void
    {
        $errorMessages = array_map(
            function ($error) {
                return $error['message'];
            },
            $errors
        );

        $exception = new InvalidRequestHttpException('Validation of JSON request body failed.');
        $exception->setErrors($errorMessages);

        throw $exception;
    }
}
