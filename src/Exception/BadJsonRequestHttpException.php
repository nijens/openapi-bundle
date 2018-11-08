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

use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * BadJsonRequestHttpException.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class BadJsonRequestHttpException extends BadRequestHttpException implements HttpExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getErrors(): array
    {
        $errors = array();

        $previousException = $this->getPrevious();
        if ($previousException instanceof Exception) {
            $errors[] = $previousException->getMessage();
        }

        return $errors;
    }
}
