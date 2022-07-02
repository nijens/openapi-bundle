# Previous exception handling
⚠ _**Please note:** This feature is deprecated since version 1.3 and will be removed in version 2.0._

For the path prefix, where you load your OpenAPI specification into, exception handling activates.
The exception handling will transform every exception into a JSON representation. Exceptions that extend
from the `Symfony\Component\HttpKernel\Exception\HttpException` allow you to control the status code of the
exception response.

Every other exception will return a `500 Internal Server Error`.

## Default validation error responses
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

## Customizing exception handling
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
