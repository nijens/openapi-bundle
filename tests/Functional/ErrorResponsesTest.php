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

use Nijens\OpenapiBundle\EventListener\JsonResponseExceptionSubscriber;
use Nijens\OpenapiBundle\Service\ExceptionJsonResponseBuilder;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional testing of error responses created by {@see JsonResponseExceptionSubscriber} and
 * {@see ExceptionJsonResponseBuilder}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class ErrorResponsesTest extends WebTestCase
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

    public function testCanHandleTriggeredError(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/error/trigger-error',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            ''
        );

        static::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

        $responseBody = $this->client->getResponse()->getContent();

        static::assertJson($responseBody);
        static::assertJsonStringEqualsJsonString(
            '{"message":"This is an error triggered by the OpenAPI bundle test suite."}',
            $responseBody
        );
    }

    public function testCanHandleThrownThrowable(): void
    {
        /*
         * Insulating the client to prevent PHPUnit from catching the error before
         * the {@see JsonResponseExceptionSubscriber}.
         */
        $this->client->insulate();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OUTPUT: {"message":"This is an error thrown by the OpenAPI bundle test suite."} ERROR OUTPUT: .');

        $this->client->request(
            Request::METHOD_GET,
            '/api/error/throw-error',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            ''
        );
    }

    public function testCanHandleThrownHttpException(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/error/throw-http-exception',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            ''
        );

        static::assertResponseStatusCodeSame(Response::HTTP_SERVICE_UNAVAILABLE);

        $responseBody = $this->client->getResponse()->getContent();

        static::assertJson($responseBody);
        static::assertJsonStringEqualsJsonString(
            '{"message":"This is an HTTP exception thrown by the OpenAPI bundle test suite."}',
            $responseBody
        );
    }

    public function testCanHandleThrownException(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/error/throw-exception',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            ''
        );

        static::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

        $responseBody = $this->client->getResponse()->getContent();

        static::assertJson($responseBody);
        static::assertJsonStringEqualsJsonString(
            '{"message":"This is an exception thrown by the OpenAPI bundle test suite."}',
            $responseBody
        );
    }
}
