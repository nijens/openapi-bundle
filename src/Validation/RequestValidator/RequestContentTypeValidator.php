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

use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidContentTypeProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\RequestProblemExceptionInterface;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates the allowed content types for the request.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
final class RequestContentTypeValidator implements ValidatorInterface
{
    public function validate(Request $request): ?RequestProblemExceptionInterface
    {
        $requestContentType = $this->getRequestContentType($request);
        $routeContext = $this->getRouteContext($request);

        if ($requestContentType === '' && $routeContext[RouteContext::REQUEST_BODY_REQUIRED] === false) {
            return null;
        }

        if (empty($routeContext[RouteContext::REQUEST_ALLOWED_CONTENT_TYPES])) {
            return null;
        }

        if (in_array($requestContentType, $routeContext[RouteContext::REQUEST_ALLOWED_CONTENT_TYPES])) {
            return null;
        }

        return new InvalidContentTypeProblemException(
            ProblemException::DEFAULT_TYPE_URI,
            'The request contains errors.',
            Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
            sprintf(
                "The request content-type '%s' is not supported. (Supported: %s)",
                $requestContentType,
                implode(', ', $routeContext[RouteContext::REQUEST_ALLOWED_CONTENT_TYPES])
            )
        );
    }

    private function getRequestContentType(Request $request): string
    {
        return current(HeaderUtils::split($request->headers->get('Content-Type', ''), ';')) ?: '';
    }

    private function getRouteContext(Request $request): ?array
    {
        return array_replace(
            [
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => [],
            ],
            $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE, [])
        );
    }
}
