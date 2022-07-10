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

namespace Nijens\OpenapiBundle\Deserialization\ArgumentResolver;

use Nijens\OpenapiBundle\Deserialization\Attribute\DeserializedObject;
use Nijens\OpenapiBundle\Deserialization\DeserializationContext;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class DeserializedObjectArgumentResolver implements ArgumentValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($request->attributes->has(DeserializationContext::REQUEST_ATTRIBUTE) === false) {
            return false;
        }

        $routeContext = $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE);
        if ($this->isDeserializationObjectType($argument, $routeContext)) {
            return true;
        }

        if ($this->hasDeserializedObjectAttribute($argument)) {
            return true;
        }

        if ($this->isDeserializationObjectArgumentName($argument, $routeContext)) {
            return true;
        }

        return false;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield $request->attributes->get(DeserializationContext::REQUEST_ATTRIBUTE);
    }

    private function isDeserializationObjectType(ArgumentMetadata $argument, ?array $routeContext): bool
    {
        if (isset($routeContext[RouteContext::DESERIALIZATION_OBJECT]) === false) {
            return false;
        }

        return $routeContext[RouteContext::DESERIALIZATION_OBJECT] === $argument->getType();
    }

    private function hasDeserializedObjectAttribute(ArgumentMetadata $argument): bool
    {
        if (method_exists($argument, 'getAttributes')) {
            return count($argument->getAttributes(DeserializedObject::class, ArgumentMetadata::IS_INSTANCEOF)) > 0;
        }

        return false;
    }

    private function isDeserializationObjectArgumentName(ArgumentMetadata $argument, ?array $routeContext): bool
    {
        if (isset($routeContext[RouteContext::DESERIALIZATION_OBJECT_ARGUMENT_NAME]) === false) {
            return false;
        }

        return $routeContext[RouteContext::DESERIALIZATION_OBJECT_ARGUMENT_NAME] === $argument->getName();
    }
}
