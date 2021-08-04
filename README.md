# OpenAPI bundle

[![Latest version on Packagist][ico-version]][link-version]
[![Software License][ico-license]][link-license]
[![Build Status][ico-build]][link-build]
[![Coverage Status][ico-coverage]][link-coverage]
[![Code Quality][ico-code-quality]][link-code-quality]

Helps you create a REST API from your OpenAPI specification.

This bundle supports a design-first methodology for creating an API with Symfony by providing the following tools:

* [Loading the path items and operations of an OpenAPI specification as routes](#routing)
* [Validation of JSON request bodies to those routes](#validation-of-json-request-bodies)
* [OpenAPI-based serialization context for the Symfony Serializer](#openapi-based-serialization-context-for-the-symfony-serializer)
* [Exception handling](#exception-handling)

## Installation

### Applications that use Symfony Flex
Open a command console, enter your project directory and execute:

```console
composer require nijens/openapi-bundle
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
composer require nijens/openapi-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Nijens\OpenapiBundle\NijensOpenapiBundle(),
        );

        // ...
    }

    // ...
}
```

## Usage
Before starting with the implementation of the bundle, you should take the time to design your API according
to the OpenAPI specification.

The following resources can help you with designing the specification:
* [OpenAPI specification version 3](https://swagger.io/specification)
* [Swagger specification editor](https://editor.swagger.io)

### Routing
This bundle provides a route loader that can load [path items](https://swagger.io/specification/#pathItemObject)
and [operations](https://swagger.io/specification/#operationObject) from your OpenAPI specification.

You load your OpenAPI specification by configuring it in the routing of your application:

```yaml
# app/config/routes.yml

api:
    prefix: /api
    resource: ../openapi.json # or ../openapi.yaml
    type: openapi
    name_prefix: "api_"
```

#### Configuring a controller for a route
A Symfony controller for a route is configured by adding the `x-symfony-controller` property to an operation within your OpenAPI specification.
```json
{
    "paths": {
        "/pets/{uuid}": {
            "put": {
                "x-symfony-controller": "Nijens\\OpenapiBundle\\Controller\\PetController::put",
                "requestBody": {
                    "content": {
                        "application/json": {
                            "schema": {
                                "$ref": "#/components/schemas/Pet"
                            }
                        }
                    }
                },
                "responses": {
                    "200": {
                        "description": "Returns the stored pet."
                    }
                }
            }
        }
    }
}
```

The value of the `x-symfony-controller` property is the same as you would normally add to a [Symfony route](https://symfony.com/doc/current/routing.html#creating-routes).

### Validation of JSON request bodies
When the operations of the path items have a `requestBody` property configured with the content-type `application/json`,
the bundle validates the incoming request bodies for those routes in the specification.


#### Default validation error responses
The following error responses can occur when validation fails during a request made to a route managed by the OpenAPI bundle:
* `400 Bad Request`: when the request content-type is not `application/json`
* `400 Bad Request`: when the JSON within the request body is invalid
* `422 Unprocessable Entity`: when the JSON within the request body does not validate with the JSON schema of the route

All validation error responses will return a response body similar to the following:
```json
{
    "message": "Validation of JSON request body failed.",
    "errors": [
        "The property iAmRequired is required",
        "The property iAmExtra is not defined and the definition does not allow additional properties"
    ]
}
```

### OpenAPI-based serialization context for the Symfony Serializer
⚠ _**Please note:** This feature is still experimental. The API might change in a future minor version._

The `SerializationContextBuilder` helps you with creating a serialization context for the Symfony Serializer.
It allows you to easily create a JSON response from an object or entity based on your OpenAPI specification.

The following example shows how to use the serialization context builder by leveraging the request attributes added
by the routing.

```php
<?php

use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Serialization\SerializationContextBuilderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class ExampleController
{
    public function __invoke(
        Request $request,
        SerializerInterface $serializer,
        SerializationContextBuilderInterface $serializationContextBuilder
    ): JsonResponse {
        $pet = new Pet();

        $serializationContext = $serializationContextBuilder->getContextForSchemaObject(
            'Pet',
            $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE)[RouteContext::RESOURCE]
        );

        return JsonResponse::fromJsonString(
            $serializer->serialize($pet, 'json', $serializationContext)
        );
    }
}
```

### Exception handling
For the path prefix, where you load your OpenAPI specification into, exception handling activates.
The exception handling will transform every exception into a JSON representation. Exceptions that extend
from the `Symfony\Component\HttpKernel\Exception\HttpException` allow you to control the status code of the
exception response.

Every other exception will return a `500 Internal Server Error`.

#### Customizing exception handling
If your project requires different formatting of the response or perhaps different response codes, the default
response builder can be overridden. Following the ["How to Decorate Services" guide from Symfony](https://symfony.com/doc/current/service_container/service_decoration.html),
you can create your own service which implements `ExceptionJsonResponseBuilderInterface` and handles the error response
building instead of `nijens_openapi.service.exception_json_response_builder`. When validation against the schema fails,
an exception is thrown, which implements `HttpExceptionInterface`. The `getErrors()` method will provide a
multi-dimensional array similar to this:
```php
[
    [
        'property' => 'iAmRequired',
        'pointer' => '/iAmRequired',
        'message' => 'The property iAmRequired is required',
        'constraint' => 'required',
        'context' => 1,
    ],
    [
        'property' => '',
        'pointer' => '',
        'message' => 'The property iAmExtra is not defined and the definition does not allow additional properties',
        'constraint' => 'additionalProp',
        'context' => 1,
    ],
];
```

## Credits and acknowledgements

* Author: [Niels Nijens][link-author]

Also, see the list of [contributors][link-contributors] who participated in this project.

## License
The OpenAPI bundle is licensed under the MIT License. Please see the [LICENSE file][link-license] for details.

[ico-version]: https://img.shields.io/packagist/v/nijens/openapi-bundle.svg
[ico-pre-release-version]: https://img.shields.io/packagist/vpre/nijens/openapi-bundle.svg
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg
[ico-build]: https://github.com/nijens/openapi-bundle/actions/workflows/continuous-integration.yaml/badge.svg
[ico-coverage]: https://coveralls.io/repos/nijens/openapi-bundle/badge.svg?branch=master
[ico-code-quality]: https://scrutinizer-ci.com/g/nijens/openapi-bundle/badges/quality-score.png?b=master

[link-version]: https://packagist.org/packages/nijens/openapi-bundle
[link-license]: LICENSE
[link-build]: https://github.com/nijens/openapi-bundle/actions/workflows/continuous-integration.yaml
[link-coverage]: https://coveralls.io/r/nijens/openapi-bundle?branch=master
[link-code-quality]: https://scrutinizer-ci.com/g/nijens/openapi-bundle/?branch=master
[link-author]: https://github.com/niels-nijens
[link-contributors]: https://github.com/nijens/openapi-bundle/contributors
