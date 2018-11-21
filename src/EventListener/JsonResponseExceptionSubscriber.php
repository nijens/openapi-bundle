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

use Nijens\OpenapiBundle\Service\ExceptionJsonResponseBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
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
     * @var ExceptionJsonResponseBuilderInterface
     */
    private $responseBuilder;

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
     * @param RouterInterface                       $router
     * @param ExceptionJsonResponseBuilderInterface $responseBuilder
     */
    public function __construct(RouterInterface $router, ExceptionJsonResponseBuilderInterface $responseBuilder)
    {
        $this->router = $router;
        $this->responseBuilder = $responseBuilder;
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

        $event->setResponse($this->responseBuilder->build($event->getException()));
    }
}
