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
use Nijens\OpenapiBundle\Exception\BadJsonRequestHttpException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
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
     * @var MockObject
     */
    private $routerMock;

    /**
     * Creates a new JsonResponseExceptionSubscriber instance for testing.
     */
    protected function setUp()
    {
        $this->routerMock = $this->getMockBuilder(RouterInterface::class)
            ->getMock();

        $this->subscriber = new JsonResponseExceptionSubscriber($this->routerMock, false);
    }

    /**
     * Tests if JsonResponseExceptionSubscriber::getSubscribedEvents returns the list with expected listeners.
     */
    public function testGetSubscribedEvents()
    {
        $subscribedEvents = JsonResponseExceptionSubscriber::getSubscribedEvents();

        $this->assertSame(
            array(
                KernelEvents::EXCEPTION => array(
                    array('onKernelExceptionTransformToJsonResponse', 0),
                ),
            ),
            $subscribedEvents
        );
    }

    /**
     * Tests if constructing a new JsonResponseExceptionSubscriber instance sets the instance properties.
     */
    public function testConstruct()
    {
        $this->assertAttributeSame($this->routerMock, 'router', $this->subscriber);
        $this->assertAttributeSame(false, 'debugMode', $this->subscriber);
    }

    /**
     * Tests if JsonResponseExceptionSubscriber::onKernelExceptionTransformToJsonResponse
     * sets no response on the event when no Route is found in the RouteCollection.
     *
     * @depends testConstruct
     */
    public function testOnKernelExceptionTransformToJsonResponseDoesNothingWhenRouteIsNotFoundInCollection()
    {
        $routeCollection = new RouteCollection();

        $this->routerMock->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $request = new Request();
        $request->attributes->set('_route', 'not_in_collection');

        $eventMock = $this->getMockBuilder(GetResponseForExceptionEvent::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest'))
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
        $routeCollection = new RouteCollection();
        $routeCollection->add('no_openapi_route', new Route('/no-openapi-route'));

        $this->routerMock->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $request = new Request();
        $request->attributes->set('_route', 'no_openapi_route');

        $eventMock = $this->getMockBuilder(GetResponseForExceptionEvent::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest'))
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->subscriber->onKernelExceptionTransformToJsonResponse($eventMock);

        $this->assertNull($eventMock->getResponse());
    }

    /**
     * Tests if JsonResponseExceptionSubscriber::onKernelExceptionTransformToJsonResponse
     * sets a response with 'Unexpected error' message for non-http exceptions and not
     * the actual error message, as that might expose private information.
     *
     * @depends testConstruct
     */
    public function testOnKernelExceptionTransformToJsonResponseSetsJsonResponseWithUnexpectedErrorMessage()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(
            'openapi_route',
            new Route(
                '/openapi-route',
                array(),
                array(),
                array('openapi_resource' => 'openapi.json')
            )
        );

        $this->routerMock->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $request = new Request();
        $request->attributes->set('_route', 'openapi_route');

        $eventMock = $this->getMockBuilder(GetResponseForExceptionEvent::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getException'))
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);
        $eventMock->expects($this->once())
            ->method('getException')
            ->willReturn(new Exception('This message should not be visible.'));

        $this->subscriber->onKernelExceptionTransformToJsonResponse($eventMock);

        $response = $eventMock->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('{"message":"Unexpected error."}', $response->getContent());
    }

    /**
     * Tests if JsonResponseExceptionSubscriber::onKernelExceptionTransformToJsonResponse
     * sets a response with the message and status of the http exception.
     *
     * @depends testConstruct
     */
    public function testOnKernelExceptionTransformToJsonResponseSetsJsonResponseWithExceptionMessage()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(
            'openapi_route',
            new Route(
                '/openapi-route',
                array(),
                array(),
                array('openapi_resource' => 'openapi.json')
            )
        );

        $this->routerMock->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $request = new Request();
        $request->attributes->set('_route', 'openapi_route');

        $eventMock = $this->getMockBuilder(GetResponseForExceptionEvent::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getException'))
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);
        $eventMock->expects($this->once())
            ->method('getException')
            ->willReturn(
                new BadJsonRequestHttpException(
                    'This message should be visible.',
                    new Exception('This previous exception message should be visible too.')
                )
            );

        $this->subscriber->onKernelExceptionTransformToJsonResponse($eventMock);

        $response = $eventMock->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(
            '{"message":"This message should be visible.","errors":["This previous exception message should be visible too."]}',
            $response->getContent()
        );
    }

    /**
     * Tests if JsonResponseExceptionSubscriber::onKernelExceptionTransformToJsonResponse
     * sets a response with the message of any exception when debug mode is active.
     *
     * @depends testConstruct
     */
    public function testOnKernelExceptionTransformToJsonResponseSetsJsonResponseWithExceptionMessageInDebugMode()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(
            'openapi_route',
            new Route(
                '/openapi-route',
                array(),
                array(),
                array('openapi_resource' => 'openapi.json')
            )
        );

        $this->routerMock->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $request = new Request();
        $request->attributes->set('_route', 'openapi_route');

        $eventMock = $this->getMockBuilder(GetResponseForExceptionEvent::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRequest', 'getException'))
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);
        $eventMock->expects($this->once())
            ->method('getException')
            ->willReturn(new Exception('This message should be visible in debug mode.'));

        $this->subscriber = new JsonResponseExceptionSubscriber($this->routerMock, true);
        $this->subscriber->onKernelExceptionTransformToJsonResponse($eventMock);

        $response = $eventMock->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('{"message":"This message should be visible in debug mode."}', $response->getContent());
    }
}
