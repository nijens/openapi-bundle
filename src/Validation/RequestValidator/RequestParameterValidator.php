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

use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestParameterProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\RequestProblemExceptionInterface;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\Violation;
use Nijens\OpenapiBundle\Routing\RouteContext;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates the query parameters of the request with the schema from the OpenAPI document.
 *
 * @experimental
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
final class RequestParameterValidator implements ValidatorInterface
{
    /**
     * @var Validator
     */
    private $jsonValidator;

    public function __construct(Validator $jsonValidator)
    {
        $this->jsonValidator = $jsonValidator;
    }

    public function validate(Request $request): ?RequestProblemExceptionInterface
    {
        $validateQueryParameters = $this->getValidateQueryParametersFromRequest($request);
        $validateHeaderParameters = $this->getValidateHeaderParametersFromRequest($request);

        $violations = [];
        foreach ($validateQueryParameters as $parameterName => $parameter) {
            $violations = array_merge(
                $violations,
                $this->validateQueryParameter($request, $parameterName, json_decode($parameter))
            );
        }

        foreach ($validateHeaderParameters as $parameterName => $parameter) {
            $violations = array_merge(
                $violations,
                $this->validateHeaderParameter($request, $parameterName, json_decode($parameter))
            );
        }

        if (count($violations) > 0) {
            $exception = new InvalidRequestParameterProblemException(
                ProblemException::DEFAULT_TYPE_URI,
                'The request contains errors.',
                Response::HTTP_BAD_REQUEST,
                'Validation of query parameters failed.'
            );

            return $exception->withViolations($violations);
        }

        return null;
    }

    private function getValidateQueryParametersFromRequest(Request $request): array
    {
        return $request->attributes
            ->get(RouteContext::REQUEST_ATTRIBUTE)[RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS] ?? [];
    }

    private function validateQueryParameter(Request $request, string $parameterName, stdClass $parameter): array
    {
        $violations = [];
        if ($request->query->has($parameterName) === false && $parameter->required ?? false) {
            $violations[] = new Violation(
                'required_query_parameter',
                sprintf('Query parameter %s is required.', $parameterName),
                $parameterName
            );

            return $violations;
        }

        if ($request->query->has($parameterName) === false) {
            return $violations;
        }

        $parameterValue = $request->query->get($parameterName);

        return $this->validateParameterValue($parameterName, $parameter, $parameterValue, $violations);
    }

    private function getValidateHeaderParametersFromRequest(Request $request): array
    {
        return $request->attributes
            ->get(RouteContext::REQUEST_ATTRIBUTE)[RouteContext::REQUEST_VALIDATE_HEADER_PARAMETERS] ?? [];
    }

    private function validateHeaderParameter(Request $request, string $parameterName, stdClass $parameter): array
    {
        $violations = [];
        if ($request->headers->has($parameterName) === false && $parameter->required ?? false) {
            $violations[] = new Violation(
                'required_header_parameter',
                sprintf('Header parameter %s is required.', $parameterName),
                $parameterName
            );

            return $violations;
        }

        if ($request->headers->has($parameterName) === false) {
            return $violations;
        }

        $parameterValue = $request->headers->get($parameterName);

        return $this->validateParameterValue($parameterName, $parameter, $parameterValue, $violations);
    }

    private function validateParameterValue(string $parameterName, stdClass $parameter, mixed $parameterValue, array $currentViolations): array
    {
        $this->jsonValidator->validate($parameterValue, $parameter->schema, Constraint::CHECK_MODE_COERCE_TYPES);
        if ($this->jsonValidator->isValid()) {
            return $currentViolations;
        }

        $validationErrors = $this->jsonValidator->getErrors();
        $this->jsonValidator->reset();

        return array_map(
            function (array $validationError) use ($parameterName): Violation {
                $validationError['property'] = $parameterName;

                return Violation::fromArray($validationError);
            },
            $validationErrors
        );
    }
}
