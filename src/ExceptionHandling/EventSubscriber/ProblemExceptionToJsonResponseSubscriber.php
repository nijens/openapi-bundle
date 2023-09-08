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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Creates a JSON response from an exception implementing the {@see ProblemExceptionInterface}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
final class ProblemExceptionToJsonResponseSubscriber implements EventSubscriberInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelExceptionCreateJsonResponse', -32],
            ],
        ];
    }

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function onKernelExceptionCreateJsonResponse(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if ($throwable instanceof ProblemExceptionInterface === false) {
            return;
        }

        $response = JsonResponse::fromJsonString(
            $this->serializer->serialize($throwable, 'json'),
            $throwable->getStatusCode(),
            $throwable->getHeaders()
        );
        $response->headers->set('Content-Type', 'application/problem+json');

        $event->setResponse($response);
    }
}
