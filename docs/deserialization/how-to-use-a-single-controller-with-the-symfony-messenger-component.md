# How to use a single controller with the Symfony Messenger component

## 1. Install the OpenAPI bundle and additional Symfony components

```shell
composer require nijens/openapi-bundle symfony/messenger symfony/validator
```

## 2. Configure the bundle

```yaml
# config/packages/nijens_openapi.yaml

nijens_openapi:
    routing:
        operation_id_as_route_name: true

    exception_handling:
        enabled: true
```

## 3. Create an OpenAPI document

```yaml
# config/openapi.yaml

openapi: 3.0.1
info:
  title: Pet store
  version: 1.0.0

paths:
  /pets:
    post:
      x-openapi-bundle:
        controller: 'App\Controller\CommandController'
        deserializationObject: 'App\Command\CreatePet'
        additionalRouteAttributes:
          responseSerializationSchemaObject: 'Pet'
      summary: Add a new pet to the store.
      operationId: addPet
      requestBody:
        description: Pet object that needs to be added to the store.
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/Pet'
      responses:
        '201':
          description: Successfully added a new pet to the store.
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Pet'
        '400':
          description: Invalid input.

  /pets/{id}:
    post:
      x-openapi-bundle:
        controller: 'App\Controller\CommandController'
        deserializationObject: 'App\Command\UpdatePet'
        additionalRouteAttributes:
          responseSerializationSchemaObject: 'Pet'
      summary: Add a new pet to the store.
      operationId: updatePet
      parameters:
        - name: id
          in: path
          required: true
          schema: 
            type: integer
            minimum: 1
      requestBody:
        description: Pet object that needs to be added to the store.
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/Pet'
      responses:
        '201':
          description: Successfully added a new pet to the store.
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Pet'
        '400':
          description: Invalid input.
```

## 4. Load your OpenAPI document

```yaml
# config/routes.yaml

api:
    prefix: /api
    resource: ../openapi.yaml
    type: openapi
    name_prefix: "api_"
```

## 5. Create the controller

```php

<?php

declare(strict_types=1);

namespace App\Controller;

use Nijens\OpenapiBundle\Deserialization\Attribute\DeserializedObject;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestBodyProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\Violation;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Serialization\SerializationContextBuilderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CommandController
{
    private ValidatorInterface $validator;

    private MessageBusInterface $messageBus;

    private SerializerInterface $serializer;

    private SerializationContextBuilderInterface $serializationContextBuilder;

    public function __construct(
        ValidatorInterface $validator,
        MessageBusInterface $messageBus,
        SerializerInterface $serializer,
        SerializationContextBuilderInterface $serializationContextBuilder
    ) {
        $this->validator = $validator;
        $this->messageBus = $messageBus;
        $this->serializer = $serializer;
        $this->serializationContextBuilder = $serializationContextBuilder;
    }

    public function __invoke(
        Request $request,
        #[DeserializedObject] $command,
        string $responseSerializationSchemaObject
    ): JsonResponse {
        $this->validateCommand($command);

        $message = $this->messageBus->dispatch($command);

        /** @var HandledStamp $handledStamp */
        $handledStamp = $message->last(HandledStamp::class);
        $result = $handledStamp->getResult();

        $serializationContext = $this->serializationContextBuilder->getContextForSchemaObject(
            $responseSerializationSchemaObject,
            $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE)[RouteContext::RESOURCE]
        );

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($result, 'json', $serializationContext)
        );
    }

    private function validateCommand($command): void
    {
        $validationErrors = $this->validator->validate($command);
        if (count($validationErrors) > 0) {
            $exception = new InvalidRequestBodyProblemException(
                'about:blank',
                'The request body contains errors.',
                Response::HTTP_BAD_REQUEST
            );

            $violations = array_map(
                function (ConstraintViolation $validationError): Violation {
                    return new Violation(
                        $validationError->getConstraint(),
                        $validationError->getMessage(),
                        $validationError->getPropertyPath()
                    );
                },
                $validationErrors
            );

            throw $exception->withViolations($violations);
        }
    }
}

```
