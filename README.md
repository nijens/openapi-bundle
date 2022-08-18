# OpenAPI bundle

[![Latest version on Packagist][ico-version]][link-version]
[![Software License][ico-license]][link-license]
[![Build Status][ico-build]][link-build]
[![Code Quality][ico-code-quality]][link-code-quality]

Helps you create a REST API from your OpenAPI specification.

This bundle supports a design-first methodology for creating an API with Symfony by providing the following tools:

* [Loading the path items and operations of an OpenAPI specification as routes](#routing)
* [Validation of the request to those routes](#validation-of-the-request)
* [Deserialization of a validated JSON request body into an object](#deserialize-a-json-request-body)
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
This bundle provides a route loader that loads [path items](https://swagger.io/specification/#pathItemObject)
and [operations](https://swagger.io/specification/#operationObject) from your OpenAPI document.

You load your OpenAPI document by configuring it in the routing of your application:

```yaml
# app/config/routes.yml

api:
    prefix: /api
    resource: ../openapi.yaml # or ../openapi.json
    type: openapi
    name_prefix: "api_"
```

Within the OpenAPI document we will use the `x-openapi-bundle` specification extension to add additional configuration
to the operations defined in the document.

#### Configuring a controller for a route
A Symfony controller for a route is configured by adding the `controller` property to the `x-openapi-bundle`
specification extension within an operation within your OpenAPI document.

```yaml
paths:
  /pets/{uuid}:
    put:
      x-openapi-bundle:
        controller: 'Nijens\OpenapiBundle\Controller\PetController::put'
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
<summary>Example of an OpenAPI document in JSON format</summary>

```json
{
  "paths": {
    "/pets/{uuid}": {
      "put": {
        "x-openapi-bundle": {
          "controller": "Nijens\\OpenapiBundle\\Controller\\PetController::put"
        },
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

The value of the `controller` property is the same as you would normally add to a [Symfony route](https://symfony.com/doc/current/routing.html#creating-routes).

#### Using the operationId of an operation as the name of the Symfony route
Within an OpenAPI document, you can give each operation an
[operationId](https://spec.openapis.org/oas/latest.html#fixed-fields-7) to better identify it.
To use the `operationId` as the name for a loaded Symfony route, add the following bundle configuration:

```yaml
# config/packages/nijens_openapi.yaml
nijens_openapi:
    routing:
        operation_id_as_route_name: true
```

Using the `operationId` for your routes gives you more control over the API route names and allows you to better use
them with a `UrlGenerator`.

### Validation of the request
By default, the [deprecated validation component](docs/validation/deprecated-validation-component.md) is enabled.
To enable the improved validation component, add the following YAML configuration.

```yaml
# config/packages/nijens_openapi.yaml
nijens_openapi:
    exception_handling:
        enabled: true

    validation:
        enabled: true
```

It is strongly advised to also enable the improved exception handling component, as it will convert the details of
the validation exceptions into proper JSON responses.

The validation component comes with validation for the following parts of a request:

* **Content-type**: Based on the configured content types configured in the `requestBody` property of an operation
* **Query parameters**: Validates the query parameters configured of the operation and path item.
  *Note that this type of validation is experimental as it might be missing validation of certain query parameter types.*
* **JSON request body**: Based on the JSON schema in the `requestBody` property of an operation

#### Learn more

* [Activate query parameter request validation](docs/validation/activate-query-parameter-request-validation.md)
* [Create your custom request validator](docs/validation/create-your-custom-request-validator.md)
* Content-type validation explained (coming soon™)
* JSON request body validation explained (coming soon™)
* Query parameter validation explained (coming soon™)

### Deserialize a JSON request body

Adding the `deserializationObject` property to the `x-openapi-bundle` specification extension of an operation activates
the request body deserialization.

When the request body is successfully validated against the JSON schema within your OpenAPI document,
it will deserialize the request body into the configured deserialization object.

The deserialized object is injected into the controller based on:

1. The type hint of the argument in the controller method.

2. The `#[DeserializedObject]` parameter attribute. (supported since PHP 8.0)

   This method is the recommended way, as it supports argument resolving for both array deserialization
   and mixed argument types.

3. The `deserializationObjectArgumentName` property that can be added to the `x-openapi-bundle`
   specification extension.

#### Learn more

* [How to use a single controller with the Symfony Messenger component](docs/deserialization/how-to-use-a-single-controller-with-the-symfony-messenger-component.md)

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

If you want to implement your own exception handling? Change `enabled` to `false`. It will disable the
exception handling component of the bundle.

#### Customizing the Problem Details JSON Object response of an exception
Through the exception handling configuration of the bundle, you can modify the response status code and
problem JSON response body of any `Throwable`. See the following example for more information.

```yaml
# config/packages/nijens_openapi.yaml
nijens_openapi:
    exception_handling:
        enabled: true
        exceptions:
            InvalidArgumentException:               # The fully qualified classname of the exception.
                status_code: 400                    # Modify the response status code of
                                                    # the exception response.

                type_uri: https://example.com/error # Add a unique type URI to the Problem Details.
                                                    # This could be a URL to additional documentation
                                                    # about the error.

                title: The request was invalid.     # Add a clear human-readable title property
                                                    # to the Problem Details.

                add_instance_uri: true              # Add the current route as instance_uri property
                                                    # to the Problem Details.
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
