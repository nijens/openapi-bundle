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

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * InvalidRequestHttpException.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class InvalidRequestHttpException extends UnprocessableEntityHttpException
{
    /**
     * The list with errors resulting in this exception.
     *
     * @var array
     */
    private $errors = array();

    /**
     * Sets the list of errors resulting in this exception.
     *
     * @param array $errors
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * Returns the list of errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
