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

namespace Nijens\OpenapiBundle\ExceptionHandling;

use Nijens\OpenapiBundle\Exception\HttpExceptionInterface;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestBodyProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemExceptionInterface;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\Violation;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Throwable;

/**
 * Transforms a deprecated exception implementing the {@see HttpExceptionInterface}
 * to {@see InvalidRequestBodyProblemException}.
 *
 * @deprecated since 1.3, to be removed in 2.0.
 */
final class DeprecatedExceptionToProblemExceptionTransformer implements ThrowableToProblemExceptionTransformerInterface
{
    /**
     * @var ThrowableToProblemExceptionTransformerInterface
     */
    private $baseThrowableToProblemExceptionTransformer;

    public function __construct(
        ThrowableToProblemExceptionTransformerInterface $baseThrowableToProblemExceptionTransformer
    ) {
        $this->baseThrowableToProblemExceptionTransformer = $baseThrowableToProblemExceptionTransformer;
    }

    public function transform(Throwable $throwable, ?string $instanceUri): ProblemExceptionInterface
    {
        if ($throwable instanceof HttpExceptionInterface) {
            $exception = $this->createInvalidRequestBodyProblemException($throwable);

            $violations = array_map(
                function (array $error): Violation {
                    $error['constraint'] = $error['constraint'] ?? 'valid_json';

                    return Violation::fromArray($error);
                },
                $throwable->getErrors()
            );

            $throwable = $exception->withViolations($violations);
        }

        return $this->baseThrowableToProblemExceptionTransformer->transform($throwable, $instanceUri);
    }

    private function createInvalidRequestBodyProblemException(Throwable $throwable): InvalidRequestBodyProblemException
    {
        if ($throwable instanceof SymfonyHttpExceptionInterface) {
            return InvalidRequestBodyProblemException::fromHttpException($throwable);
        }

        return InvalidRequestBodyProblemException::fromThrowable($throwable);
    }
}
