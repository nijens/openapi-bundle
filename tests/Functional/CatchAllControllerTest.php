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

namespace Nijens\OpenapiBundle\Tests\Functional;

use Nijens\OpenapiBundle\Controller\CatchAllController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional testing of the {@see CatchAllController}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class CatchAllControllerTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * Creates a new test client.
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Tests if {@see CatchAllController::throwNoRouteException} returns a '404 Not Found' response
     * when no route is found.
     */
    public function testReturnsNotFoundResponseWhenNoRouteFound(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/does-not-exist',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $expectedJsonResponseBody = [
            'message' => "No route found for 'GET /api/does-not-exist'.",
        ];

        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode(), $response->getContent());
        self::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $response->getContent()
        );
    }

    /**
     * Tests if {@see CatchAllController::throwNoRouteException} returns a '405 Method Not Allowed'
     * when a route is found but the request method is not allowed.
     *
     * @depends testReturnsNotFoundResponseWhenNoRouteFound
     */
    public function testReturnsMethodNotAllowedResponseWhenRouteIsAvailableButRequestMethodIsNotAllowed(): void
    {
        $this->client->request(
            Request::METHOD_DELETE,
            '/api/pets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $expectedJsonResponseBody = [
            'message' => "No route found for 'DELETE /api/pets': Method Not Allowed (Allowed: GET, POST, PATCH).",
        ];

        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $response->getContent()
        );
    }
}
