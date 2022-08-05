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

namespace Nijens\OpenapiBundle\ExceptionHandling\Exception;

use Throwable;

class InvalidRequestProblemException extends ProblemException implements InvalidRequestProblemExceptionInterface
{
    /**
     * @var ViolationInterface[]
     */
    private $violations;

    public function __construct(
        string $typeUri,
        string $title,
        int $statusCode,
        string $message = '',
        Throwable $previous = null,
        ?string $instanceUri = null,
        array $headers = [],
        array $violations = []
    ) {
        parent::__construct($typeUri, $title, $statusCode, $message, $previous, $instanceUri, $headers);

        $this->violations = $violations;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * @param ViolationInterface[] $violations
     */
    public function withViolations(array $violations): InvalidRequestProblemExceptionInterface
    {
        $exception = $this->clone();
        $exception->violations = $violations;

        return $exception;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['violations'] = $this->getViolations();

        return $data;
    }

    protected function clone()
    {
        $clone = parent::clone();
        $clone->violations = $this->violations;

        return $clone;
    }
}
