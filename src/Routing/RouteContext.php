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

    public const RESOURCE = 'resource';

    public const JSON_REQUEST_VALIDATION_POINTER = 'json_request_validation_pointer';

    public const REQUEST_ALLOWED_CONTENT_TYPES = 'request_allowed_content_types';

    public const REQUEST_BODY_REQUIRED = 'request_body_required';

    public const REQUEST_BODY_SCHEMA = 'request_body_schema';

    public const REQUEST_VALIDATE_QUERY_PARAMETERS = 'request_validate_query_parameters';

    public const DESERIALIZATION_OBJECT = 'deserialization_object';

    public const DESERIALIZATION_OBJECT_ARGUMENT_NAME = 'deserialization_object_argument_name';
}
