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

use Nijens\OpenapiBundle\Deserialization\DeserializationContext;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Validation\ValidationContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class JsonRequestBodyDeserializationSubscriber implements EventSubscriberInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['deserializeRequestBody', 27],
            ],
        ];
    }

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function deserializeRequestBody(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $routeContext = $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE);
        $validationContext = $request->attributes->get(ValidationContext::REQUEST_ATTRIBUTE);

        if ($validationContext === null || isset($routeContext[RouteContext::DESERIALIZATION_OBJECT]) === false) {
            return;
        }

        /**
         * TODO: Remove when support for Symfony 5.4 is dropped.
         */
        $format = method_exists($request, 'getContentTypeFormat') ? $request->getContentTypeFormat() : $request->getContentType();

        $request->attributes->set(
            DeserializationContext::REQUEST_ATTRIBUTE,
            $this->serializer->deserialize(
                $validationContext[ValidationContext::REQUEST_BODY],
                $routeContext[RouteContext::DESERIALIZATION_OBJECT],
                $format
            )
        );
    }
}
