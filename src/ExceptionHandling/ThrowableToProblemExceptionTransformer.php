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

use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Transforms a {@see Throwable} into a {@see ProblemException} implementation.
 * Adding additional information to the {@see ProblemException} from the exception map.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
final class ThrowableToProblemExceptionTransformer implements ThrowableToProblemExceptionTransformerInterface
{
    /**
     * @var array<string, array>
     */
    private $exceptionMap;

    public function __construct(array $exceptionMap)
    {
        $this->exceptionMap = $exceptionMap;
    }

    public function transform(Throwable $throwable, ?string $instanceUri): ProblemExceptionInterface
    {
        $class = get_class($throwable);
        $exceptionData = $this->exceptionMap[$class] ?? [];
        $exceptionData['instance_uri'] = $instanceUri;

        if ($throwable instanceof HttpExceptionInterface) {
            $throwable = ProblemException::fromHttpException($throwable, $throwable->getStatusCode());
        }

        if ($throwable instanceof ProblemExceptionInterface === false) {
            $throwable = ProblemException::fromThrowable($throwable);
        }

        return $this->addAdditionalProblemInformation($throwable, $exceptionData);
    }

    /**
     * @param array{type_uri: string, title: string, add_instance_uri: bool, instance_uri: string, status_code: int} $exceptionData
     */
    private function addAdditionalProblemInformation(
        ProblemExceptionInterface $exception,
        array $exceptionData
    ): ProblemExceptionInterface {
        if (isset($exceptionData['type_uri'])) {
            $exception = $exception->withTypeUri($exceptionData['type_uri']);
        }
        if (isset($exceptionData['title'])) {
            $exception = $exception->withTitle($exceptionData['title']);
        }
        if (($exceptionData['add_instance_uri'] ?? false) && isset($exceptionData['instance_uri'])) {
            $exception = $exception->withInstanceUri($exceptionData['instance_uri']);
        }
        if (isset($exceptionData['status_code'])) {
            $exception = $exception->withStatusCode($exceptionData['status_code']);
        }

        return $exception;
    }
}
