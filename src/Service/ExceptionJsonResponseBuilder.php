<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Service;

use Nijens\OpenapiBundle\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Builds a JSON response to an exception for OpenAPI routes.
 *
 * @author David Cochrum <dcochrum@sonnysdirect.com>
 */
class ExceptionJsonResponseBuilder implements ExceptionJsonResponseBuilderInterface
{
    /**
     * The boolean indicating if the kernel is in debug mode.
     *
     * @var bool
     */
    private $debugMode;

    /**
     * Default constructor.
     */
    public function __construct(bool $debugMode)
    {
        $this->debugMode = $debugMode;
    }

    /**
     * {@inheritdoc}
     */
    public function build(Throwable $exception): JsonResponse
    {
        $response = new JsonResponse();

        $statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'Unexpected error.';

        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
        }

        if ($this->debugMode || $exception instanceof HttpException) {
            $message = $exception->getMessage();
        }

        $responseBody = ['message' => $message];
        if ($exception instanceof HttpExceptionInterface) {
            $responseBody['errors'] = array_map(function ($error) {
                return $error['message'];
            }, $exception->getErrors());
        }

        $response->setStatusCode($statusCode);
        $response->setData($responseBody);

        return $response;
    }
}
