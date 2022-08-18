<?php

declare(strict_types=1);

namespace App\Example;

use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestBodyProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\RequestProblemExceptionInterface;
use Nijens\OpenapiBundle\Validation\RequestValidator\ValidatorInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class XmlRequestBodyValidator implements ValidatorInterface
{
    public function validate(Request $request): ?RequestProblemExceptionInterface {
        $requestContentType = $this->getRequestContentType($request);
        if ($requestContentType !== 'application/xml') {
            return null;
        }

        $requestBody = $request->getContent();
        $violations = $this->validateAgainstSchema($requestBody);
        if (count($violations) > 0) {
            $exception = new InvalidRequestBodyProblemException(
                ProblemException::DEFAULT_TYPE_URI,
                'The request body contains errors.',
                Response::HTTP_BAD_REQUEST,
                'Validation of XML request body failed.'
            );

            return $exception->withViolations($violations);
        }

        return null;
    }

    private function getRequestContentType(Request $request): string
    {
        return current(HeaderUtils::split($request->headers->get('Content-Type', ''), ';')) ?: '';
    }

    // ...
}
