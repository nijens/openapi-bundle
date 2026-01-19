<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Tests\ExceptionHandling;

use Error;
use InvalidArgumentException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemExceptionInterface;
use Nijens\OpenapiBundle\ExceptionHandling\ThrowableToProblemExceptionTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Tests the {@see ThrowableToProblemExceptionTransformer}.
 */
class ThrowableToProblemExceptionTransformerTest extends TestCase
{
    private $throwableToProblemExceptionTransformer;

    protected function setUp(): void
    {
        $this->throwableToProblemExceptionTransformer = new ThrowableToProblemExceptionTransformer([
            NotFoundHttpException::class => [
                'type_uri' => 'https://example.com/not-found-error',
                'title' => 'Resource not found.',
                'add_instance_uri' => true,
            ],
            InvalidArgumentException::class => [
                'type_uri' => 'https://example.com/validation-error',
                'title' => 'Invalid request.',
                'status_code' => 400,
            ],
        ]);
    }

    public function testCanTransformHttpExceptionToProblemException(): void
    {
        $httpException = new BadRequestHttpException('Bad Request.');

        $expectedException = new ProblemException(
            'about:blank',
            'An error occurred.',
            400,
            'Bad Request.',
            $httpException
        );

        $actualException = $this->throwableToProblemExceptionTransformer->transform($httpException, null);

        static::assertEquals($expectedException, $actualException);
    }

    public function testCanTransformThrowableToProblemException(): void
    {
        $throwable = new Error('Something terrible happened!');

        $expectedException = new ProblemException(
            'about:blank',
            'An error occurred.',
            500,
            'Something terrible happened!',
            $throwable
        );

        $actualException = $this->throwableToProblemExceptionTransformer->transform($throwable, null);

        static::assertEquals($expectedException, $actualException);
    }

    /**
     * @dataProvider provideExceptions
     */
    public function testCanAddAdditionalProblemInformationToProblemException(
        Throwable $exception,
        ProblemExceptionInterface $expectedException,
    ): void {
        static::assertEquals(
            $expectedException,
            $this->throwableToProblemExceptionTransformer->transform($exception, 'https://example.com/resource/1')
        );
    }

    public function provideExceptions(): iterable
    {
        $exception = new NotFoundHttpException('Resource with ID "1" not found.');
        $expectedException = new ProblemException(
            'https://example.com/not-found-error',
            'Resource not found.',
            404,
            'Resource with ID "1" not found.',
            $exception,
            'https://example.com/resource/1'
        );

        yield [$exception, $expectedException];

        $exception = new InvalidArgumentException('Invalid value for foo.');
        $expectedException = new ProblemException(
            'https://example.com/validation-error',
            'Invalid request.',
            400,
            'Invalid value for foo.',
            $exception
        );

        yield [$exception, $expectedException];
    }
}
