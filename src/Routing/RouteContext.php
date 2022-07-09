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

namespace Nijens\OpenapiBundle\Routing;

/**
 * Contains the context keys added by the {@see RouteLoader}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
final class RouteContext
{
    public const REQUEST_ATTRIBUTE = '_nijens_openapi';

    public const RESOURCE = 'openapi_resource';

    public const CONTROLLER = 'openapi_controller';

    public const JSON_REQUEST_VALIDATION_POINTER = 'openapi_json_request_validation_pointer';

    public const DESERIALIZATION_OBJECT = 'openapi_deserialization_object';

    public const DESERIALIZATION_OBJECT_ARGUMENT_NAME = 'deserialization_object_argument_name';
}
