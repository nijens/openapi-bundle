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

namespace Nijens\OpenapiBundle\ExceptionHandling\EventSubscriber;

use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemExceptionInterface;
use Nijens\OpenapiBundle\ExceptionHandling\ThrowableToProblemExceptionTransformerInterface;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Transforms a {@see Throwable} to {@see ProblemExceptionInterface} for routes managed by the OpenAPI bundle.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
final class ThrowableToProblemExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ThrowableToProblemExceptionTransformerInterface
     */
    private $throwableToProblemExceptionTransformer;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelExceptionTransformToProblemException', -16],
            ],
        ];
    }

    public function __construct(ThrowableToProblemExceptionTransformerInterface $throwableToProblemExceptionTransformer)
    {
        $this->throwableToProblemExceptionTransformer = $throwableToProblemExceptionTransformer;
    }

    public function onKernelExceptionTransformToProblemException(ExceptionEvent $event): void
    {
        if ($this->isManagedRoute($event->getRequest()) === false) {
            return;
        }

        $event->setThrowable(
            $this->throwableToProblemExceptionTransformer->transform(
                $event->getThrowable(),
                $event->getRequest()->getRequestUri()
            )
        );
    }

    private function isManagedRoute(Request $request): bool
    {
        $routeOptions = $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE);

        return isset($routeOptions[RouteContext::RESOURCE]);
    }
}
