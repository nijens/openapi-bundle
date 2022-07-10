<?php

declare(strict_types=1);

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
