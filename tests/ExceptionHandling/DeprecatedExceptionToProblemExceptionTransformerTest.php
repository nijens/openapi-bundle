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

namespace Nijens\OpenapiBundle\Tests\ExceptionHandling;

use Error;
use Nijens\OpenapiBundle\Exception\InvalidRequestHttpException;
use Nijens\OpenapiBundle\ExceptionHandling\DeprecatedExceptionToProblemExceptionTransformer;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestBodyProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemExceptionInterface;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\Violation;
use Nijens\OpenapiBundle\ExceptionHandling\ThrowableToProblemExceptionTransformerInterface;
use PHPUnit\Framework\TestCase;
use Throwable;

class DeprecatedExceptionToProblemExceptionTransformerTest extends TestCase
{
    private $deprecatedProblemExceptionTransformer;

    protected function setUp(): void
    {
        $baseProblemExceptionTransformer = $this->createMock(ThrowableToProblemExceptionTransformerInterface::class);
        $baseProblemExceptionTransformer->method('transform')
            ->willReturnCallback(function (Throwable $throwable) {
                if ($throwable instanceof ProblemExceptionInterface) {
                    return $throwable;
                }

                return ProblemException::fromThrowable($throwable);
            });

        $this->deprecatedProblemExceptionTransformer = new DeprecatedExceptionToProblemExceptionTransformer(
            $baseProblemExceptionTransformer
        );
    }

    public function testCanTransformHttpExceptionImplementationToInvalidRequestBodyProblemException(): void
    {
        $throwable = new InvalidRequestHttpException('Validation of JSON request body failed.');
        $throwable->setErrors([
            [
                'property' => 'name',
                'pointer' => '/name',
                'message' => 'The property name is required',
                'constraint' => 'required',
                'context' => 1,
            ],
        ]);

        $expected = new InvalidRequestBodyProblemException(
            'about:blank',
            'An error occurred.',
            422,
            'Validation of JSON request body failed.',
            $throwable,
            null,
            [],
            [
                new Violation('required', 'The property name is required', 'name'),
            ]
        );

        static::assertEquals(
            $expected,
            $this->deprecatedProblemExceptionTransformer->transform($throwable, null)
        );
    }

    public function testCanPassThrowableToInjectedProblemExceptionTransformer(): void
    {
        $throwable = new Error('Syntax error.');

        $expected = new ProblemException('about:blank', 'An error occurred.', 500, 'Syntax error.', $throwable);

        static::assertEquals(
            $expected,
            $this->deprecatedProblemExceptionTransformer->transform($throwable, null)
        );
    }
}
