# Deprecated validation component
⚠ _**Please note:** This feature is deprecated since version 1.5 and will be removed in version 2.0._

When the operations of the path items have a `requestBody` property configured with the content-type `application/json`,
the bundle validates the incoming request bodies for those routes in the specification.

The following exceptions can be thrown when validation fails during a request made to a route managed by the OpenAPI bundle:
* `BadJsonRequestHttpException`: when the request content-type is not `application/json`
* `BadJsonRequestHttpException`: when the JSON within the request body is invalid
* `InvalidRequestHttpException`: when the JSON within the request body does not validate with the JSON schema of the route

The exceptions are converted to JSON responses by the [exception handling](#exception-handling) component
of this bundle.
