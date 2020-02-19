<?php

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
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * JsonResponseExceptionSubscriberTest.
 */
class JsonResponseExceptionSubscriberTest extends TestCase
{
    /**
     * @var JsonResponseExceptionSubscriber
     */
    private $subscriber;

    /**
     * @var MockObject|RouterInterface
     */
    private $routerMock;

    /**
     * @var MockObject|ExceptionJsonResponseBuilderInterface
     */
    private $responseBuilderMock;

    /**
     * Creates a new JsonResponseExceptionSubscriber instance for testing.
     */
    protected function setUp(): void
    {
        $this->responseBuilderMock = $this->getMockBuilder(ExceptionJsonResponseBuilderInterface::class)
            ->getMock();

        $this->subscriber = new JsonResponseExceptionSubscriber($this->responseBuilderMock);
    }

    /**
     * Tests if JsonResponseExceptionSubscriber::getSubscribedEvents returns the list with expected listeners.
     */
    public function testGetSubscribedEvents()
    {
        $subscribedEvents = JsonResponseExceptionSubscriber::getSubscribedEvents();

        $this->assertSame(
            [
                KernelEvents::EXCEPTION => [
                    ['onKernelExceptionTransformToJsonResponse', 10],
                ],
            ],
            $subscribedEvents
        );
    }

    /**
     * Tests if constructing a new JsonResponseExceptionSubscriber instance sets the instance properties.
     */
    public function testConstruct()
    {
        $this->assertAttributeSame($this->responseBuilderMock, 'responseBuilder', $this->subscriber);
    }

    /**
     * Tests if JsonResponseExceptionSubscriber::onKernelExceptionTransformToJsonResponse
     * sets no response on the event when no Route is found in the RouteCollection.
     *
     * @depends testConstruct
     */
    public function testOnKernelExceptionTransformToJsonResponseDoesNothingWhenRouteIsNotFoundInCollection()
    {
        $request = new Request();
        $request->attributes->set('_route', 'not_in_collection');

        /** @var MockObject|ExceptionEvent $eventMock */
        $eventMock = $this->getMockBuilder(ExceptionEvent::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest'])
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->subscriber->onKernelExceptionTransformToJsonResponse($eventMock);

        $this->assertNull($eventMock->getResponse());
    }

    /**
     * Tests if JsonResponseExceptionSubscriber::onKernelExceptionTransformToJsonResponse
     * sets no response on the event when the Route does not have the 'openapi_resource' option set.
     *
     * @depends testConstruct
     */
    public function testOnKernelExceptionTransformToJsonResponseDoesNothingWhenRouteIsNotAnOpenapiRoute()
    {
        $request = new Request();
        $request->attributes->set('_route', 'no_openapi_route');

        /** @var MockObject|ExceptionEvent $eventMock */
        $eventMock = $this->getMockBuilder(ExceptionEvent::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest'])
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->subscriber->onKernelExceptionTransformToJsonResponse($eventMock);

        $this->assertNull($eventMock->getResponse());
    }

    /**
     * Tests if JsonResponseExceptionSubscriber::onKernelExceptionTransformToJsonResponse
     * sets a response when the Route does have the 'openapi_resource' option set.
     *
     * @depends testConstruct
     */
    public function testOnKernelExceptionTransformToJsonResponseSetsResponseWhenRouteIsAnOpenapiRoute()
    {
        $request = new Request();
        $request->attributes->set('_route', 'openapi_route');
        $request->attributes->set('_nijens_openapi', [
            'openapi_resource' => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
        ]);

        /** @var MockObject|ExceptionEvent $eventMock */
        $eventMock = $this->getMockBuilder(ExceptionEvent::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest', 'getThrowable'])
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);
        $eventMock->expects($this->once())
            ->method('getThrowable')
            ->willReturn(new Exception('This message should not be visible.'));

        $response = $this->getMockBuilder(JsonResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseBuilderMock->expects($this->once())
            ->method('build')
            ->willReturn($response);

        $this->subscriber->onKernelExceptionTransformToJsonResponse($eventMock);

        $this->assertSame($response, $eventMock->getResponse());
    }
}
