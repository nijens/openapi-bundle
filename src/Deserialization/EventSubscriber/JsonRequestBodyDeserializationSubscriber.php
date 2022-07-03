<?php

declare(strict_types=1);

namespace Nijens\OpenapiBundle\Deserialization\EventSubscriber;

use Nijens\OpenapiBundle\Routing\RouteContext;
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

    public static function getSubscribedEvents()
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

        // TODO Add defaults from OpenAPI schema.

        $routeContext = $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE);
        $requestBodyValidated = $request->attributes->get('_nijens_openapi_validated', false);

        if ($requestBodyValidated === false || isset($routeContext[RouteContext::DESERIALIZATION_OBJECT]) === false) {
            return;
        }

        $request->attributes->set(
            'data',
            $this->serializer->deserialize($request->getContent(), $routeContext[RouteContext::DESERIALIZATION_OBJECT], 'json')
        );
    }
}
