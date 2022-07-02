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

namespace Nijens\OpenapiBundle\Tests\ExceptionHandling\Exception;

use Error;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestBodyProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\Violation;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Tests the {@see InvalidRequestBodyProblemException}.
 */
class InvalidRequestBodyProblemExceptionTest extends AbstractProblemExceptionTest
{
    protected function setUp(): void
    {
        $this->exception = new InvalidRequestBodyProblemException(
            'about:blank',
            'Error',
            400,
            'The request body contains errors.',
            null,
            null,
            [],
            [
                new Violation('valid_json', 'Invalid JSON.'),
            ]
        );
    }

    public function testCanSetViolations(): void
    {
        $violations = [new Violation('valid_json', 'Invalid JSON string.')];

        $exception = $this->exception->withViolations($violations);

        static::assertNotSame($this->exception, $exception);
        static::assertProblemExceptionEqualsExcludingProperty($this->exception, $exception, 'violations');
        static::assertSame($violations, $exception->getViolations());
    }

    public function testCanCreateFromHttpException(): void
    {
        $httpException = new MethodNotAllowedHttpException(['GET'], 'Not allowed to POST to this endpoint.');

        $expectedException = new InvalidRequestBodyProblemException(
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
        $actualException = InvalidRequestBodyProblemException::fromHttpException($httpException);

        static::assertEquals($expectedException, $actualException);
    }

    public function testCanCreateFromThrowable(): void
    {
        $throwable = new Error('Some error happened!');

        $expectedException = new InvalidRequestBodyProblemException(
            'about:blank',
            'An error occurred.',
            500,
            'Some error happened!',
            $throwable
        );
        $actualException = InvalidRequestBodyProblemException::fromThrowable($throwable);

        static::assertEquals($expectedException, $actualException);
    }
}
