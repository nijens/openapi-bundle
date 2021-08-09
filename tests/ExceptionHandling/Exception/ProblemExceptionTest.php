<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Tests\ExceptionHandling\Exception;

use Error;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Tests the {@see ProblemException}.
 */
class ProblemExceptionTest extends TestCase
{
    /**
     * @var ProblemException
     */
    private $exception;

    protected function setUp(): void
    {
        $this->exception = new ProblemException('about:blank', 'Error', 500);
    }

    public function testCanSetTypeUri(): void
    {
        $typeUri = 'https://example.com/error';

        $exception = $this->exception->withTypeUri($typeUri);

        static::assertNotSame($this->exception, $exception);
        static::assertSame($typeUri, $exception->getTypeUri());
    }

    public function testCanSetTitle(): void
    {
        $title = 'An error occurred';

        $exception = $this->exception->withTitle($title);

        static::assertNotSame($this->exception, $exception);
        static::assertSame($title, $exception->getTitle());
    }

    public function testCanSetInstanceUri(): void
    {
        $instanceUri = 'https://example.com/instance/123';

        $exception = $this->exception->withInstanceUri($instanceUri);

        static::assertNotSame($this->exception, $exception);
        static::assertSame($instanceUri, $exception->getInstanceUri());
    }

    public function testCanSetStatusCode(): void
    {
        $statusCode = 400;

        $exception = $this->exception->withStatusCode($statusCode);

        static::assertNotSame($this->exception, $exception);
        static::assertSame($statusCode, $exception->getStatusCode());
    }

    public function testCanSetHeaders(): void
    {
        $headers = ['Allow' => 'GET'];

        $exception = $this->exception->withHeaders($headers);

        static::assertNotSame($this->exception, $exception);
        static::assertSame($headers, $exception->getHeaders());
    }

    public function testCanCreateFromHttpException(): void
    {
        $httpException = new MethodNotAllowedHttpException(['GET'], 'Not allowed to POST to this endpoint.');

        $expectedException = new ProblemException(
            'about:blank',
            'An error occurred.',
            405,
            'Not allowed to POST to this endpoint.',
            $httpException,
            null,
            [
                'Allow' => 'GET',
            ]
        );
        $actualException = ProblemException::fromHttpException($httpException);

        static::assertEquals($expectedException, $actualException);
    }

    public function testCanCreateFromThrowable(): void
    {
        $throwable = new Error('Some error happened!');

        $expectedException = new ProblemException(
            'about:blank',
            'An error occurred.',
            500,
            'Some error happened!',
            $throwable
        );
        $actualException = ProblemException::fromThrowable($throwable);

        static::assertEquals($expectedException, $actualException);
    }
}
