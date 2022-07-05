<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Deserialization\ArgumentResolver;

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

        return isset($routeContext[RouteContext::DESERIALIZATION_OBJECT]) && $routeContext[RouteContext::DESERIALIZATION_OBJECT] === $argument->getType();
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield $request->attributes->get(DeserializationContext::REQUEST_ATTRIBUTE);
    }
}
