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

use Nijens\OpenapiBundle\ExceptionHandling\Exception\RequestProblemExceptionInterface;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Validation\RequestValidator\ValidatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
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
     * @var ValidatorInterface
     */
    private $requestValidator;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['validateRequest', 7],
            ],
        ];
    }

    public function __construct(ValidatorInterface $requestValidator)
    {
        $this->requestValidator = $requestValidator;
    }

    public function validateRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($this->isManagedRoute($request) === false) {
            return;
        }

        $exception = $this->requestValidator->validate($request);
        if ($exception instanceof RequestProblemExceptionInterface) {
            throw $exception;
        }
    }

    private function isManagedRoute(Request $request): bool
    {
        return $request->attributes->has(RouteContext::REQUEST_ATTRIBUTE);
    }
}
