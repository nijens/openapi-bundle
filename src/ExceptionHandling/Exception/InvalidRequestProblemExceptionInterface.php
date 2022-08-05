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

/**
 * The interface describing the information required for an RFC 7807 Problem Details JSON object response
 * with violations extension.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc7807#section-3.2
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface InvalidRequestProblemExceptionInterface extends ProblemExceptionInterface
{
    /**
     * @return ViolationInterface[]
     */
    public function getViolations(): array;

    /**
     * @param ViolationInterface[] $violations
     */
    public function withViolations(array $violations): InvalidRequestProblemExceptionInterface;
}
