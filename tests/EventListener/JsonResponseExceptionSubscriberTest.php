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

namespace Nijens\OpenapiBundle\Tests\EventListener;

use Exception;
use Nijens\OpenapiBundle\EventListener\JsonResponseExceptionSubscriber;
use Nijens\OpenapiBundle\Service\ExceptionJsonResponseBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tests the {@see JsonResponseExceptionSubscriber}.
 */
class JsonResponseExceptionSubscriberTest extends TestCase
{
    /**
     * @var JsonResponseExceptionSubscriber
     */
    private $subscriber;

    /**
     * @var MockObject|ExceptionJsonResponseBuilderInterface
     */
    private $responseBuilderMock;

    /**
     * Creates a new {@see JsonResponseExceptionSubscriber} instance for testing.
     */
    protected function setUp(): void
    {
        $this->responseBuilderMock = $this->getMockBuilder(ExceptionJsonResponseBuilderInterface::class)
            ->getMock();

        $this->subscriber = new JsonResponseExceptionSubscriber($this->responseBuilderMock);
    }

    /**
     * Tests if {@see JsonResponseExceptionSubscriber::getSubscribedEvents} returns the list with expected listeners.
     */
    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = JsonResponseExceptionSubscriber::getSubscribedEvents();

        $this->assertSame(
            [
                KernelEvents::EXCEPTION => [
                    ['onKernelExceptionTransformToJsonResponse', 0],
                ],
            ],
            $subscribedEvents
        );
    }

    /**
     * Tests if {@see JsonResponseExceptionSubscriber::onKernelExceptionTransformToJsonResponse}
     * sets no response on the event when no Route is found in the {@see RouteCollection}.
     */
    public function testOnKernelExceptionTransformToJsonResponseDoesNothingWhenRouteIsNotFoundInCollection(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'not_in_collection');

        $exceptionMock = $this->createMock(Exception::class);
        $eventMock = $this->createExceptionEvent($request, $exceptionMock);

        $this->subscriber->onKernelExceptionTransformToJsonResponse($eventMock);

        $this->assertNull($eventMock->getResponse());
    }

    /**
     * Tests if {@see JsonResponseExceptionSubscriber::onKernelExceptionTransformToJsonResponse}
     * sets no response on the event when the Route does not have the 'openapi_resource' option set.
     */
    public function testOnKernelExceptionTransformToJsonResponseDoesNothingWhenRouteIsNotAnOpenapiRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'no_openapi_route');

        $exceptionMock = $this->createMock(Exception::class);
        $eventMock = $this->createExceptionEvent($request, $exceptionMock);

        $this->subscriber->onKernelExceptionTransformToJsonResponse($eventMock);

        $this->assertNull($eventMock->getResponse());
    }

    /**
     * Tests if {@see JsonResponseExceptionSubscriber::onKernelExceptionTransformToJsonResponse}
     * sets a response when the Route does have the 'openapi_resource' option set.
     */
    public function testOnKernelExceptionTransformToJsonResponseSetsResponseWhenRouteIsAnOpenapiRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'openapi_route');
        $request->attributes->set('_nijens_openapi', [
            'openapi_resource' => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
        ]);

        $exception = new Exception('This message should not be visible.');
        $event = $this->createExceptionEvent($request, $exception);

        $response = $this->getMockBuilder(JsonResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseBuilderMock->expects($this->once())
            ->method('build')
            ->willReturn($response);

        $this->subscriber->onKernelExceptionTransformToJsonResponse($event);

        $this->assertSame($response, $event->getResponse());
    }

    /**
     * Creates an exception event. The type of event is created based on which
     * Symfony version is being tested.
     *
     * @return GetResponseForExceptionEvent|ExceptionEvent
     */
    private function createExceptionEvent(Request $request, Exception $exception)
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);

        if (class_exists('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')) {
            return new GetResponseForExceptionEvent($kernelMock, $request, HttpKernelInterface::MASTER_REQUEST, $exception);
        }

        return new ExceptionEvent($kernelMock, $request, HttpKernelInterface::MASTER_REQUEST, $exception);
    }
}
