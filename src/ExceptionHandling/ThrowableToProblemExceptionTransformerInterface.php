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

use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemExceptionInterface;
use Throwable;

/**
 * Transforms a {@see Throwable} into a {@see ProblemExceptionInterface} implementation.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface ThrowableToProblemExceptionTransformerInterface
{
    public function transform(Throwable $throwable, ?string $instanceUri): ProblemExceptionInterface;
}
