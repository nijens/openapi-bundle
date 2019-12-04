<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Tests\Service;

use Exception;
use Nijens\OpenapiBundle\Exception\BadJsonRequestHttpException;
use Nijens\OpenapiBundle\Exception\InvalidRequestHttpException;
use Nijens\OpenapiBundle\Service\ExceptionJsonResponseBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * ExceptionJsonResponseBuilderTest.
 */
class ExceptionJsonResponseBuilderTest extends TestCase
{
    /**
     * @var ExceptionJsonResponseBuilder
     */
    private $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->builder = new ExceptionJsonResponseBuilder(false);
    }

    /**
     * Tests if constructing a new JsonExceptionResponseBuilder instance sets the instance properties.
     */
    public function testConstruct()
    {
        $this->assertAttributeSame(false, 'debugMode', $this->builder);
    }

    /**
     * Tests if JsonExceptionResponseBuilder::build builds a response with 'Unexpected error' message for non-http
     * exceptions and not the actual error message, as that might expose private information.
     *
     * @depends testConstruct
     */
    public function testBuildReturnsJsonResponseWithUnexpectedErrorMessage()
    {
        $response = $this->builder->build(new Exception('This message should not be visible.'));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('{"message":"Unexpected error."}', $response->getContent());
    }

    /**
     * Tests if JsonExceptionResponseBuilder::build builds a response with the message and status of the http exception.
     *
     * @depends testConstruct
     */
    public function testBuildReturnsJsonResponseWithExceptionMessage()
    {
        $response = $this->builder->build(
            new BadJsonRequestHttpException(
                'This message should be visible.',
                new Exception('This previous exception message should be visible too.')
            )
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(
            '{"message":"This message should be visible.","errors":["This previous exception message should be visible too."]}',
            $response->getContent()
        );
    }

    /**
     * Tests if JsonExceptionResponseBuilder::build builds a response with the message of any exception when debug mode
     * is active.
     *
     * @depends testConstruct
     */
    public function testBuildReturnsJsonResponseWithExceptionMessageInDebugMode()
    {
        $builder = new ExceptionJsonResponseBuilder(true);
        $response = $builder->build(new Exception('This message should be visible in debug mode.'));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('{"message":"This message should be visible in debug mode."}', $response->getContent());
    }

    /**
     * Tests if JsonExceptionResponseBuilder::build builds a response with simplified error messages within an
     * OpenapiBundle HttpExceptionInterface exception.
     *
     * @depends testConstruct
     */
    public function testBuildReturnsJsonResponseContainingSimplifiedOpenapiErrorMessages()
    {
        $exception = new InvalidRequestHttpException('An overall error message.');
        $exception->setErrors([
            ['ignore' => 'me', 'message' => 'An additional error message.'],
            ['ignore' => 'me too', 'message' => 'Another additional error message.'],
        ]);

        $response = $this->builder->build($exception);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($exception->getStatusCode(), $response->getStatusCode());
        $this->assertSame('{"message":"An overall error message.","errors":["An additional error message.","Another additional error message."]}', $response->getContent());
    }
}
