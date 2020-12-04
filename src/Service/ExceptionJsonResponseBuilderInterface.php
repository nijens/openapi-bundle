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

use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

/**
 * Formats a response to an exception for OpenAPI routes.
 *
 * @author David Cochrum <dcochrum@sonnysdirect.com>
 */
interface ExceptionJsonResponseBuilderInterface
{
    /**
     * Builds and provides a Response from the given Exception.
     */
    public function build(Throwable $exception): JsonResponse;
}
