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

namespace Nijens\OpenapiBundle\Deserialization\EventSubscriber;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Nijens\OpenapiBundle\Deserialization\DeserializationContext;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Validation\ValidationContext;
use stdClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Encoder\ContextAwareDecoderInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class JsonRequestBodyDeserializationSubscriber implements EventSubscriberInterface
{
    /**
     * @var Validator
     */
    private $jsonValidator;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['deserializeRequestBody', 6],
            ],
        ];
    }

    public function __construct(Validator $jsonValidator, ContextAwareDecoderInterface&DenormalizerInterface $serializer)
    {
        $this->jsonValidator = $jsonValidator;
        $this->serializer = $serializer;
    }

    public function deserializeRequestBody(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $routeContext = $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE);
        $validationContext = $request->attributes->get(ValidationContext::REQUEST_ATTRIBUTE);

        if (isset($routeContext[RouteContext::DESERIALIZATION_OBJECT]) === false) {
            return;
        }

        if ($routeContext[RouteContext::REQUEST_BODY_REQUIRED] === true && isset($validationContext[ValidationContext::REQUEST_BODY]) === false) {
            return;
        }

        /**
         * TODO: Remove when support for Symfony 6.4 is dropped.
         */
        $format = method_exists($request, 'getContentTypeFormat') ? $request->getContentTypeFormat() : $request->getContentType();
        if ($format === null) {
            $format = 'json';
        }

        $data = [];
        if (isset($validationContext[ValidationContext::REQUEST_BODY])) {
            $data = $this->serializer->decode(
                $validationContext[ValidationContext::REQUEST_BODY],
                $format,
                [
                    JsonDecode::ASSOCIATIVE => true,
                ]
            );
        }

        foreach ($routeContext[RouteContext::DESERIALIZATION_PARAMETERS] as $encodedParameter) {
            $parameter = json_decode($encodedParameter);

            $parameterValue = $this->resolveParameterValue($request, $parameter);

            $this->jsonValidator->reset();
            $this->jsonValidator->validate($parameterValue, $parameter->schema, Constraint::CHECK_MODE_COERCE_TYPES);
            if ($this->jsonValidator->isValid() === false) {
                continue;
            }

            $data[$parameter->{'x-openapi-bundle-deserialize-as'}] = $parameterValue;
        }

        $request->attributes->set(
            DeserializationContext::REQUEST_ATTRIBUTE,
            $this->serializer->denormalize(
                $data,
                $routeContext[RouteContext::DESERIALIZATION_OBJECT],
                $format
            )
        );
    }

    private function resolveParameterValue(Request $request, stdClass $parameter)
    {
        return match ($parameter->in) {
            'header' => $request->headers->get($parameter->name),
            'query' => $this->resolveQueryParameterValue($request, $parameter),
            'path' => $request->attributes->get('_route_params')[$parameter->name] ?? null,
            default => null,
        };
    }

    private function resolveQueryParameterValue(Request $request, stdClass $parameter): bool|int|string|null
    {
        $name = $parameter->name;
        $type = $parameter->schema->type ?? 'string';
        $default = $parameter->schema->default ?? null;

        $parameterValueCollection = $request->query;
        if ($request->getMethod() === Request::METHOD_POST) {
            $parameterValueCollection = $request->request;
        }

        if ($parameterValueCollection->has($name) === false) {
            return null;
        }

        return match ($type) {
            'boolean' => $parameterValueCollection->getBoolean($name, $default ?? false),
            'integer' => $parameterValueCollection->getInt($name, $default ?? 0),
            default => $parameterValueCollection->get($name, $default ?? ''),
        };
    }
}
