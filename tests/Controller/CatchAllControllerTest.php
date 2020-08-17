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

namespace Nijens\OpenapiBundle\Tests\Controller;

use Nijens\OpenapiBundle\Controller\CatchAllController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Tests the {@see CatchAllController}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class CatchAllControllerTest extends TestCase
{
    /**
     * @var CatchAllController
     */
    private $controller;

    /**
     * @var RouteCollection
     */
    private $routeCollection;

    /**
     * @var MockObject|RouterInterface
     */
    private $routerMock;

    /**
     * Creates a new {@see CatchAllController} instance for testing.
     */
    protected function setUp(): void
    {
        $this->routeCollection = new RouteCollection();
        $this->routeCollection->add(
            'test',
            new Route(
                '/test',
                [],
                [],
                [],
                '',
                [],
                [Request::METHOD_GET]
            )
        );
        $this->routeCollection->add(
            'catch_all',
            new Route(
                '/{catchall}',
                ['_controller' => CatchAllController::CONTROLLER_REFERENCE],
                ['catchall' => '.+']
            )
        );

        $this->routerMock = $this->getMockBuilder(RouterInterface::class)
            ->getMock();
        $this->routerMock->expects($this->any())
            ->method('getRouteCollection')
            ->willReturn($this->routeCollection);

        $this->controller = new CatchAllController($this->routerMock);
    }

    /**
     * Tests if {@see CatchAllController::throwNoRouteException} throws a {@see NotFoundHttpException}
     * when no route is found.
     */
    public function testThrowNoRouteExceptionThrowsNotFoundHttpException(): void
    {
        $this->routerMock->expects($this->once())
            ->method('getContext')
            ->willReturn(new RequestContext());

        $request = Request::create('/does-not-exist');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage("No route found for 'GET /does-not-exist'.");

        $this->controller->throwNoRouteException($request);
    }

    /**
     * Tests if {@see CatchAllController::throwNoRouteException} throws a {@see MethodNotAllowedHttpException}
     * when a route is found but the request method is not allowed.
     *
     * @depends testThrowNoRouteExceptionThrowsNotFoundHttpException
     */
    public function testThrowNoRouteExceptionThrowsMethodNotAllowedHttpException(): void
    {
        $requestContext = new RequestContext('', Request::METHOD_POST);
        $requestContext->setPathInfo('/test');

        $this->routerMock->expects($this->once())
            ->method('getContext')
            ->willReturn($requestContext);

        $request = Request::create('/test', Request::METHOD_POST);

        $this->expectException(MethodNotAllowedHttpException::class);
        $this->expectExceptionMessage("No route found for 'POST /test': Method Not Allowed (Allowed: GET).");

        $this->controller->throwNoRouteException($request);
    }

    /**
     * Tests if {@see CatchAllController::throwNoRouteException} retains the routes in the original
     * {@see RouteCollection}.
     */
    public function testThrowNoRouteExceptionRetrainsTheOriginalRouteCollection(): void
    {
        $this->routerMock->expects($this->once())
            ->method('getContext')
            ->willReturn(new RequestContext());

        $request = Request::create('/does-not-exist');

        try {
            $this->controller->throwNoRouteException($request);
            $this->fail();
        } catch (NotFoundHttpException | MethodNotAllowedHttpException $exception) {
        }

        $this->assertCount(2, $this->routeCollection);
    }
}
