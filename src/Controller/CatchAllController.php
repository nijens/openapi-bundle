<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Handles exceptions for routes unavailable within OpenAPI specifications.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class CatchAllController extends Controller
{
    /**
     * The routing reference to this controller.
     *
     * @var string
     */
    public const CONTROLLER_REFERENCE = 'nijens_openapi.controller.catch_all::throwNoRouteException';

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * Constructs a new CatchAllController instance.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Throws a NotFoundHttpException or MethodNotAllowedException when this controller action is reached.
     *
     * @param Request $request
     *
     * @throws NotFoundHttpException         when the route could not be found
     * @throws MethodNotAllowedHttpException when the route was found but the request method is not allowed
     */
    public function throwNoRouteException(Request $request)
    {
        $exceptionMessage = sprintf("No route found for '%s %s'.", $request->getMethod(), $request->getPathInfo());
        $exception = new NotFoundHttpException($exceptionMessage);

        try {
            $urlMatcher = $this->createUrlMatcher();
            $urlMatcher->match($request->getPathInfo());
        } catch (ResourceNotFoundException $exception) {
            $exception = new NotFoundHttpException($exceptionMessage, $exception);
        } catch (MethodNotAllowedException $exception) {
            $exceptionMessage = sprintf(
                "No route found for '%s %s': Method Not Allowed (Allowed: %s).",
                $request->getMethod(),
                $request->getPathInfo(),
                implode(', ', $exception->getAllowedMethods())
            );

            $exception = new MethodNotAllowedHttpException(
                $exception->getAllowedMethods(),
                $exceptionMessage,
                $exception
            );
        }

        throw $exception;
    }

    /**
     * Returns a new URL matcher to match the request with existing API routes.
     *
     * @return UrlMatcherInterface
     */
    private function createUrlMatcher(): UrlMatcherInterface
    {
        return new UrlMatcher(
            $this->getUrlMatcherRouteCollection(),
            $this->router->getContext()
        );
    }

    /**
     * Returns a RouteCollection cloned from the router with the 'catch-all' route removed.
     *
     * @return RouteCollection
     */
    private function getUrlMatcherRouteCollection(): RouteCollection
    {
        $routeCollection = clone $this->router->getRouteCollection();
        foreach ($routeCollection as $routeName => $route) {
            if ($route->getDefault('_controller') !== self::CONTROLLER_REFERENCE) {
                continue;
            }

            $routeCollection->remove($routeName);
        }

        return $routeCollection;
    }
}
