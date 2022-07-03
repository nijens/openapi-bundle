# OpenAPI bundle

[![Latest version on Packagist][ico-version]][link-version]
[![Software License][ico-license]][link-license]
[![Build Status][ico-build]][link-build]
[![Code Quality][ico-code-quality]][link-code-quality]

Helps you create a REST API from your OpenAPI specification.

This bundle supports a design-first methodology for creating an API with Symfony by providing the following tools:

* [Loading the path items and operations of an OpenAPI specification as routes](#routing)
* [Validation of a JSON request body to those routes](#validation-of-a-json-request-body)
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
in the `src/Kernel.php` file of your project:

```php
<?php
// src/Kernel.php

// ...
class Kernel extends BaseKernel
{
    public function registerBundles(): iterable
    {
        return [
            // ...
            new Nijens\OpenapiBundle\NijensOpenapiBundle(),
        ];
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

```yaml
paths:
    /pets/{uuid}:
        put:
            x-symfony-controller: 'Nijens\OpenapiBundle\Controller\PetController::put'
            requestBody:
                content:
                    application/json:
                        schema:
                            $ref: '#/components/schemas/Pet'
            responses:
                '200':
                    description: 'Returns the stored pet.'
```

<details>
<summary>JSON example</summary>

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

</details>

The value of the `x-symfony-controller` property is the same as you would normally add to a [Symfony route](https://symfony.com/doc/current/routing.html#creating-routes).

### Validation of a JSON request body
When the operations of the path items have a `requestBody` property configured with the content-type `application/json`,
the bundle validates the incoming request bodies for those routes in the specification.

The following exceptions can be thrown when validation fails during a request made to a route managed by the OpenAPI bundle:
* `BadJsonRequestHttpException`: when the request content-type is not `application/json`
* `BadJsonRequestHttpException`: when the JSON within the request body is invalid
* `InvalidRequestHttpException`: when the JSON within the request body does not validate with the JSON schema of the route

The exceptions will be converted to JSON responses by the [exception handling](#exception-handling) component
of this bundle.

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
By default, the [previous exception handling component](docs/previous-exception-handling.md) is enabled.
To enable the new exception handling component, add the following YAML configuration.

```yaml
# config/packages/nijens_openapi.yaml
nijens_openapi:
    exception_handling:
        enabled: true
```

The new exception handling component uses the [Problem Details JSON Object](https://datatracker.ietf.org/doc/html/rfc7807#section-3)
format to turn an exception (or `Throwable`) into a clear error response.

If you want to implement your own exception handling? Simply change `enabled` to `false`. This will disable the
exception handling component of the bundle.

#### Customizing the Problem Details JSON Object response of an exception
Through the exception handling configuration of the bundle you are able to modify the response status code and
problem JSON response body of any `Throwable`. See the following example for more information.

```yaml
# config/packages/nijens_openapi.yaml
nijens_openapi:
    exception_handling:
        enabled: true
        exceptions:
            InvalidArgumentException:                       # The fully qualified classname of the exception.
                status_code: 400                            # Modify the response status code of the exception response.

                type_uri: https://example.com/invalid-error # Add a unique type URI to the Problem Details.
                                                            # This could be a URL to additional documentation about
                                                            # the error.

                title: The request was invalid.             # Add a clear human-readable title property to the
                                                            # Problem Details.

                add_instance_uri: true                      # Add the current route as instance_uri property to
                                                            # the Problem Details.
```

To help you include the Problem Details JSON object in your OpenAPI document, we provide an
[OpenAPI template](src/Resources/specifications/openapi_problemdetails.yaml) with schemas
for the specific Problem Details JSON objects this bundle creates.

## Credits and acknowledgements

* Author: [Niels Nijens][link-author]

Also, see the list of [contributors][link-contributors] who participated in this project.

## License
The OpenAPI bundle is licensed under the MIT License. Please see the [LICENSE file][link-license] for details.

[ico-version]: https://img.shields.io/packagist/v/nijens/openapi-bundle.svg
[ico-pre-release-version]: https://img.shields.io/packagist/vpre/nijens/openapi-bundle.svg
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg
[ico-build]: https://github.com/nijens/openapi-bundle/actions/workflows/continuous-integration.yaml/badge.svg
[ico-code-quality]: https://scrutinizer-ci.com/g/nijens/openapi-bundle/badges/quality-score.png?b=main

[link-version]: https://packagist.org/packages/nijens/openapi-bundle
[link-license]: LICENSE
[link-build]: https://github.com/nijens/openapi-bundle/actions/workflows/continuous-integration.yaml
[link-coverage]: https://coveralls.io/r/nijens/openapi-bundle?branch=master
[link-code-quality]: https://scrutinizer-ci.com/g/nijens/openapi-bundle/?branch=master
[link-author]: https://github.com/niels-nijens
[link-contributors]: https://github.com/nijens/openapi-bundle/contributors
