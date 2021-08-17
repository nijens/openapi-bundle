<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Exception;

/**
 * HttpExceptionInterface.
 *
 * @deprecated since 1.3, to be removed in 2.0. Use a ProblemExceptionInterface implementation instead.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface HttpExceptionInterface
{
    /**
     * Returns the list of errors.
     */
    public function getErrors(): array;
}
