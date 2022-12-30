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

namespace Nijens\OpenapiBundle\Validation\RequestValidator;

use JsonSchema\Validator;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestBodyProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestProblemExceptionInterface;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\RequestProblemExceptionInterface;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\Violation;
use Nijens\OpenapiBundle\Json\Reference;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Validation\ValidationContext;
use Seld\JsonLint\JsonParser;
use stdClass;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates a JSON request body with the JSON schema.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
final class RequestBodyValidator implements ValidatorInterface
{
    /**
     * @var JsonParser
     */
    private $jsonParser;

    /**
     * @var Validator
     */
    private $jsonValidator;

    public function __construct(JsonParser $jsonParser, Validator $jsonValidator)
    {
        $this->jsonParser = $jsonParser;
        $this->jsonValidator = $jsonValidator;
    }

    public function validate(Request $request): ?RequestProblemExceptionInterface
    {
        $requestContentType = $this->getRequestContentType($request);
        if ($requestContentType !== 'application/json') {
            return null;
        }

        $requestBodySchema = $this->getRequestBodySchemaFromRequest($request);
        if ($requestBodySchema === null) {
            return null;
        }

        $requestBody = $request->getContent();
        $decodedJsonRequestBody = null;

        $violations = $this->validateJsonSyntax($requestBody, $decodedJsonRequestBody);
        if (count($violations) > 0) {
            return $this->createInvalidRequestBodyProblemException(
                $violations,
                'The request body must be valid JSON.'
            );
        }

        $violations = array_merge(
            $violations,
            $this->validateJsonAgainstSchema(
                unserialize($requestBodySchema),
                $decodedJsonRequestBody
            )
        );

        if (count($violations) > 0) {
            return $this->createInvalidRequestBodyProblemException(
                $violations,
                'Validation of JSON request body failed.'
            );
        }

        $request->attributes->set(
            ValidationContext::REQUEST_ATTRIBUTE,
            [
                ValidationContext::VALIDATED => true,
                ValidationContext::REQUEST_BODY => json_encode($decodedJsonRequestBody),
            ]
        );

        return null;
    }

    private function getRequestContentType(Request $request): string
    {
        return current(HeaderUtils::split($request->headers->get('Content-Type', ''), ';')) ?: '';
    }

    private function getRequestBodySchemaFromRequest(Request $request): ?string
    {
        return $request->attributes
            ->get(RouteContext::REQUEST_ATTRIBUTE)[RouteContext::REQUEST_BODY_SCHEMA] ?? null;
    }

    /**
     * @param array|stdClass|string|int|float|bool|null $decodedJsonRequestBody
     *
     * @return Violation[]
     */
    private function validateJsonSyntax(string $requestBody, &$decodedJsonRequestBody): array
    {
        $decodedJsonRequestBody = json_decode($requestBody);
        if ($decodedJsonRequestBody !== null || $requestBody === 'null') {
            return [];
        }

        $exception = $this->jsonParser->lint($requestBody);

        return [
            new Violation('valid_json', $exception->getMessage()),
        ];
    }

    /**
     * @param stdClass|Reference                        $jsonSchema
     * @param array|stdClass|string|int|float|bool|null $decodedJsonRequestBody
     *
     * @return Violation[]
     */
    private function validateJsonAgainstSchema($jsonSchema, &$decodedJsonRequestBody): array
    {
        $this->jsonValidator->validate($decodedJsonRequestBody, $jsonSchema);

        if ($this->jsonValidator->isValid()) {
            return [];
        }

        $validationErrors = $this->jsonValidator->getErrors();
        $this->jsonValidator->reset();

        return array_map(
            function (array $validationError): Violation {
                return Violation::fromArray($validationError);
            },
            $validationErrors
        );
    }

    /**
     * @param Violation[] $violations
     */
    private function createInvalidRequestBodyProblemException(
        array $violations,
        string $message
    ): InvalidRequestProblemExceptionInterface {
        $exception = new InvalidRequestBodyProblemException(
            ProblemException::DEFAULT_TYPE_URI,
            'The request body contains errors.',
            Response::HTTP_BAD_REQUEST,
            $message
        );

        return $exception->withViolations($violations);
    }
}
