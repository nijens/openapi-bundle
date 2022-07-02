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
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Tests the {@see ProblemException}.
 */
class ProblemExceptionTest extends AbstractProblemExceptionTest
{
    protected function setUp(): void
    {
        $this->exception = new ProblemException('about:blank', 'Error', 500);
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
