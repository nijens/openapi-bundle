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

namespace Nijens\OpenapiBundle\Tests\Functional\App\Controller;

use Error;
use Exception;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ErrorController
{
    public function triggerError()
    {
        trigger_error('This is an error triggered by the OpenAPI bundle test suite.', E_USER_ERROR);
    }

    public function throwError()
    {
        throw new Error('This is an error thrown by the OpenAPI bundle test suite.');
    }

    public function throwHttpException()
    {
        throw new ServiceUnavailableHttpException(null, 'This is an HTTP exception thrown by the OpenAPI bundle test suite.');
    }

    public function throwException()
    {
        throw new Exception('This is an exception thrown by the OpenAPI bundle test suite.');
    }
}
