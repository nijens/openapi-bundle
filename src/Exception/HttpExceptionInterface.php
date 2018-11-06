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
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface HttpExceptionInterface
{
    /**
     * Returns the list of errors.
     *
     * @return array
     */
    public function getErrors(): array;
}
