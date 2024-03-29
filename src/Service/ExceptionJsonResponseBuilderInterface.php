<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Service;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Formats a response to an exception for OpenAPI routes.
 *
 * @deprecated since 1.3, to be removed in 2.0. Use the new exception handling system instead.
 *
 * @author David Cochrum <dcochrum@sonnysdirect.com>
 */
interface ExceptionJsonResponseBuilderInterface
{
    /**
     * Builds and provides a Response from the given Exception.
     */
    public function build(Exception $exception): JsonResponse;
}
