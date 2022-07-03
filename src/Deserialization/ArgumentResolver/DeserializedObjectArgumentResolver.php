<?php

namespace Nijens\OpenapiBundle\Deserialization\ArgumentResolver;

use Nijens\OpenapiBundle\Routing\RouteContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class DeserializedObjectArgumentResolver implements ArgumentValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($request->attributes->has('data') === false) {
            return false;
        }

        $routeContext = $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE);

        return isset($routeContext[RouteContext::DESERIALIZATION_OBJECT]) && $routeContext[RouteContext::DESERIALIZATION_OBJECT] === $argument->getType();
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield $request->attributes->get('data');
    }
}
