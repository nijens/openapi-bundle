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

namespace Nijens\OpenapiBundle\EventListener;

use JsonSchema\Validator;
use Nijens\OpenapiBundle\Exception\BadJsonRequestHttpException;
use Nijens\OpenapiBundle\Exception\InvalidRequestHttpException;
use Nijens\OpenapiBundle\Json\JsonPointer;
use Nijens\OpenapiBundle\Json\SchemaLoaderInterface;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Seld\JsonLint\JsonParser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Validates a JSON request body for routes loaded through the OpenAPI specification.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class JsonRequestBodyValidationSubscriber implements EventSubscriberInterface
{
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
        return [
            KernelEvents::REQUEST => [
                ['validateRequestBody', 28],
            ],
        ];
    }

    /**
     * Constructs a new JsonRequestBodyValidationSubscriber instance.
     */
    public function __construct(JsonParser $jsonParser, SchemaLoaderInterface $schemaLoader, Validator $jsonValidator)
    {
        $this->jsonParser = $jsonParser;
        $this->schemaLoader = $schemaLoader;
        $this->jsonValidator = $jsonValidator;
    }

    /**
     * Validates the body of a request to an OpenAPI specification route. Throws an exception when validation fails.
     */
    public function validateRequestBody(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $requestContentType = current(HeaderUtils::split($request->headers->get('Content-Type', ''), ';'));

        $routeOptions = $event->getRequest()->attributes->get(RouteContext::REQUEST_ATTRIBUTE);

        // Not an openAPI route.
        if (isset($routeOptions[RouteContext::RESOURCE]) === false) {
            return;
        }

        // No need for validation.
        if (isset($routeOptions[RouteContext::JSON_REQUEST_VALIDATION_POINTER]) === false) {
            return;
        }

        if ($requestContentType !== 'application/json') {
            throw new BadJsonRequestHttpException("The request content-type must be 'application/json'.");
        }

        $requestBody = $request->getContent();
        $decodedJsonRequestBody = $this->validateJsonRequestBody($requestBody);

        $this->validateJsonAgainstSchema(
            $routeOptions[RouteContext::RESOURCE],
            $routeOptions[RouteContext::JSON_REQUEST_VALIDATION_POINTER],
            $decodedJsonRequestBody
        );
    }

    /**
     * Validates if the request body is valid JSON.
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
     * @param mixed $decodedJsonRequestBody
     */
    private function validateJsonAgainstSchema(string $openApiResource, string $openApiValidationPointer, $decodedJsonRequestBody): void
    {
        $schema = $this->schemaLoader->load($openApiResource);

        $jsonPointer = new JsonPointer($schema);
        $jsonSchema = $jsonPointer->get($openApiValidationPointer);

        $this->jsonValidator->validate($decodedJsonRequestBody, $jsonSchema);

        if ($this->jsonValidator->isValid() === false) {
            $validationErrors = $this->jsonValidator->getErrors();
            $this->jsonValidator->reset();

            $this->throwInvalidRequestException($validationErrors);
        }
    }

    private function throwInvalidRequestException(array $errors): void
    {
        $exception = new InvalidRequestHttpException('Validation of JSON request body failed.');
        $exception->setErrors($errors);

        throw $exception;
    }
}
