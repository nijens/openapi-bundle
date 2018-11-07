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

use Nijens\OpenapiBundle\Exception\HttpExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Transforms an exception to a JSON response for OpenAPI routes.
 */
class JsonResponseExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * The boolean indicating if the kernel is in debug mode.
     *
     * @var bool
     */
    private $debugMode;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            KernelEvents::EXCEPTION => array(
                array('onKernelExceptionTransformToJsonResponse', 0),
            ),
        );
    }

    /**
     * Constructs a new JsonResponseExceptionSubscriber instance.
     *
     * @param RouterInterface $router
     * @param bool            $debugMode
     */
    public function __construct(RouterInterface $router, bool $debugMode)
    {
        $this->router = $router;
        $this->debugMode = $debugMode;
    }

    /**
     * Converts the exception to a JSON response.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelExceptionTransformToJsonResponse(GetResponseForExceptionEvent $event): void
    {
        $request = $event->getRequest();

        $route = $this->router->getRouteCollection()->get(
            $request->attributes->get('_route')
        );

        if ($route instanceof Route === false || $route->hasOption('openapi_resource') === false) {
            return;
        }

        $response = new JsonResponse();

        $statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'Unexpected error.';

        $exception = $event->getException();
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
        }

        if ($this->debugMode || $exception instanceof HttpException) {
            $message = $exception->getMessage();
        }

        $responseBody = array('message' => $message);
        if ($exception instanceof HttpExceptionInterface) {
            $responseBody['errors'] = $exception->getErrors();
        }

        $response->setStatusCode($statusCode);
        $response->setData($responseBody);

        $event->setResponse($response);
    }
}
