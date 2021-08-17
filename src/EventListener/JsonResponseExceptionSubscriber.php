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

use Exception;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Service\ExceptionJsonResponseBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Transforms an exception to a JSON response for OpenAPI routes.
 *
 * @deprecated since 1.3, to be removed in 2.0. Use the new exception handling system instead.
 */
class JsonResponseExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ExceptionJsonResponseBuilderInterface
     */
    private $responseBuilder;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelExceptionTransformToJsonResponse', 0],
            ],
        ];
    }

    /**
     * Constructs a new JsonResponseExceptionSubscriber instance.
     */
    public function __construct(ExceptionJsonResponseBuilderInterface $responseBuilder)
    {
        $this->responseBuilder = $responseBuilder;
    }

    /**
     * Converts the exception to a JSON response.
     */
    public function onKernelExceptionTransformToJsonResponse(ExceptionEvent $event): void
    {
        $routeOptions = $event->getRequest()->attributes->get(RouteContext::REQUEST_ATTRIBUTE);

        if (isset($routeOptions[RouteContext::RESOURCE]) === false) {
            return;
        }

        $exception = $event->getThrowable();
        if ($exception === null) {
            return;
        }

        if ($exception instanceof Exception === false) {
            $exception = new Exception($exception->getMessage(), $exception->getCode(), $exception);
        }

        $event->setResponse($this->responseBuilder->build($exception));
    }
}
