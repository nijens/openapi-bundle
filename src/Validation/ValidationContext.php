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

namespace Nijens\OpenapiBundle\Validation;

final class ValidationContext
{
    public const REQUEST_ATTRIBUTE = '_nijens_openapi_validation';

    public const VALIDATED = 'validated';

    public const REQUEST_BODY = 'request_body';

    public const REQUEST_PARAMETERS = 'request_parameters';
}
