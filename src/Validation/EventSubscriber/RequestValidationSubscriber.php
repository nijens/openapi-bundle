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

namespace Nijens\OpenapiBundle\Validation\EventSubscriber;

use JsonSchema\Validator;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidContentTypeProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestBodyProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\Violation;
use Nijens\OpenapiBundle\Json\JsonPointer;
use Nijens\OpenapiBundle\Json\SchemaLoaderInterface;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Validation\ValidationContext;
use Seld\JsonLint\JsonParser;
use stdClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Validates a request for routes loaded through the OpenAPI specification.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class RequestValidationSubscriber implements EventSubscriberInterface
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

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['validateRequest', 28],
            ],
        ];
    }

    public function __construct(JsonParser $jsonParser, SchemaLoaderInterface $schemaLoader, Validator $jsonValidator)
    {
        $this->jsonParser = $jsonParser;
        $this->schemaLoader = $schemaLoader;
        $this->jsonValidator = $jsonValidator;
    }

    public function validateRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($this->isManagedRoute($request) === false) {
            return;
        }

        $this->validateRequestContentType($request);
        $this->validateJsonRequestBody($request);
    }

    private function validateRequestContentType(Request $request): void
    {
        $requestContentType = $this->getRequestContentType($request);
        $routeContext = $this->getRouteContext($request);

        if ($requestContentType === '' && $routeContext[RouteContext::REQUEST_BODY_REQUIRED] === false) {
            return;
        }

        if (empty($routeContext[RouteContext::REQUEST_ALLOWED_CONTENT_TYPES])) {
            return;
        }

        if (in_array($requestContentType, $routeContext[RouteContext::REQUEST_ALLOWED_CONTENT_TYPES])) {
            return;
        }

        $exception = new InvalidContentTypeProblemException(
            ProblemException::DEFAULT_TYPE_URI,
            ProblemException::DEFAULT_TITLE,
            Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
            sprintf(
                "The request content-type '%s' is not supported. (Supported: %s)",
                $requestContentType,
                implode(', ', $routeContext[RouteContext::REQUEST_ALLOWED_CONTENT_TYPES])
            )
        );

        throw $exception;
    }

    private function validateJsonRequestBody(Request $request): void
    {
        $requestContentType = $this->getRequestContentType($request);
        if ($requestContentType !== 'application/json') {
            return;
        }

        $routeContext = $this->getRouteContext($request);
        if (isset($routeContext[RouteContext::JSON_REQUEST_VALIDATION_POINTER]) === false) {
            return;
        }

        $requestBody = $request->getContent();
        $decodedJsonRequestBody = $this->validateJsonSyntax($requestBody);

        $this->validateJsonAgainstSchema(
            $routeContext[RouteContext::RESOURCE],
            $routeContext[RouteContext::JSON_REQUEST_VALIDATION_POINTER],
            $decodedJsonRequestBody
        );

        $request->attributes->set(
            ValidationContext::REQUEST_ATTRIBUTE,
            [
                ValidationContext::VALIDATED => true,
                ValidationContext::REQUEST_BODY => json_encode($decodedJsonRequestBody),
            ]
        );
    }

    /**
     * Validates if the request body is valid JSON.
     *
     * @return mixed
     */
    private function validateJsonSyntax(string $requestBody)
    {
        $decodedJsonRequestBody = json_decode($requestBody);
        if ($decodedJsonRequestBody !== null || $requestBody === 'null') {
            return $decodedJsonRequestBody;
        }

        $exception = $this->jsonParser->lint($requestBody);

        $this->throwInvalidRequestBodyProblemException([
            new Violation('valid_json', $exception->getMessage()),
        ]);
    }

    /**
     * Validates the JSON request body against the JSON Schema within the OpenAPI document.
     *
     * @param array|stdClass|string|int|float|bool|null $decodedJsonRequestBody
     */
    private function validateJsonAgainstSchema(string $openApiResource, string $openApiValidationPointer, &$decodedJsonRequestBody): void
    {
        $schema = $this->schemaLoader->load($openApiResource);

        $jsonPointer = new JsonPointer($schema);
        $jsonSchema = $jsonPointer->get($openApiValidationPointer);

        $this->jsonValidator->validate($decodedJsonRequestBody, $jsonSchema);

        if ($this->jsonValidator->isValid() === false) {
            $validationErrors = $this->jsonValidator->getErrors();
            $this->jsonValidator->reset();

            $violations = array_map(
                function (array $validationError): Violation {
                    return Violation::fromArray($validationError);
                },
                $validationErrors
            );

            $this->throwInvalidRequestBodyProblemException($violations);
        }
    }

    /**
     * @param Violation[] $violations
     */
    private function throwInvalidRequestBodyProblemException(array $violations): void
    {
        $exception = new InvalidRequestBodyProblemException(
            'about:blank',
            'The request body contains errors.',
            Response::HTTP_BAD_REQUEST,
            'Validation of JSON request body failed.'
        );

        throw $exception->withViolations($violations);
    }

    private function isManagedRoute(Request $request): bool
    {
        $routeContext = $this->getRouteContext($request);

        return isset($routeContext[RouteContext::RESOURCE]);
    }

    private function getRouteContext(Request $request): ?array
    {
        return $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE);
    }

    private function getRequestContentType(Request $request): string
    {
        return current(HeaderUtils::split($request->headers->get('Content-Type', ''), ';')) ?: '';
    }
}