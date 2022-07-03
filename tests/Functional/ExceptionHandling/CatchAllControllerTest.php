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

namespace Nijens\OpenapiBundle\Tests\Functional\ExceptionHandling;

use Nijens\OpenapiBundle\Controller\CatchAllController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional testing of the {@see CatchAllController} combined with the exception handling feature.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class CatchAllControllerTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient(['environment' => 'exception_handling']);
    }

    public function testCanReturnProblemJsonObjectWhenRouteIsNotFound(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/does-not-exist',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        static::assertJsonStringEqualsJsonString(
            '{"type":"about:blank","title":"An error occurred.","status":404,"detail":"No route found for \'GET /api/does-not-exist\'."}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testCanReturnProblemJsonObjectWhenRouteMethodIsNotAllowed(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/pets',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
        static::assertJsonStringEqualsJsonString(
            '{"type":"about:blank","title":"An error occurred.","status":405,"detail":"No route found for \'GET /api/pets\': Method Not Allowed (Allowed: POST)."}',
            $this->client->getResponse()->getContent()
        );
    }
}
