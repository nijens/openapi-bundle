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

namespace Nijens\OpenapiBundle\Tests\Deserialization\EventSubscriber;

use Nijens\OpenapiBundle\Deserialization\DeserializationContext;
use Nijens\OpenapiBundle\Deserialization\EventSubscriber\JsonRequestBodyDeserializationSubscriber;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Tests\Functional\App\Model\UpdatePet;
use Nijens\OpenapiBundle\Validation\ValidationContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

class JsonRequestBodyDeserializationSubscriberTest extends TestCase
{
    /**
     * @var JsonRequestBodyDeserializationSubscriber
     */
    private $subscriber;

    /**
     * @var MockObject|SerializerInterface
     */
    private $serializer;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->subscriber = new JsonRequestBodyDeserializationSubscriber($this->serializer);
    }

    public function testCanGetSubscribedEvents(): void
    {
        $subscribedEvents = JsonRequestBodyDeserializationSubscriber::getSubscribedEvents();

        static::assertSame(
            [
                KernelEvents::REQUEST => [
                    ['deserializeRequestBody', 6],
                ],
            ],
            $subscribedEvents
        );
    }

    public function testCannotDeserializeRequestBodyWhenNotValidated(): void
    {
        $this->serializer->expects($this->never())->method('deserialize');

        $request = new Request([], [], [], [], [], [], '{"name":"Dog"}');
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::DESERIALIZATION_OBJECT => UpdatePet::class,
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->deserializeRequestBody($event);

        static::assertFalse($request->attributes->has(DeserializationContext::REQUEST_ATTRIBUTE));
    }

    public function testCannotDeserializeRequestBodyWhenNoDeserializationObjectHasBeenDefinedInRouteContext(): void
    {
        $this->serializer->expects($this->never())->method('deserialize');

        $request = new Request([], [], [], [], [], [], '{"name":"Dog"}');
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            ValidationContext::REQUEST_ATTRIBUTE,
            [
                ValidationContext::VALIDATED => true,
                ValidationContext::REQUEST_BODY => '{"name":"Dog"}',
            ]
        );
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            []
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->deserializeRequestBody($event);

        static::assertFalse($request->attributes->has(DeserializationContext::REQUEST_ATTRIBUTE));
    }

    public function testCanDeserializeRequestBodyIntoObject(): void
    {
        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with('{"name":"Dog"}', UpdatePet::class, 'json');

        $request = new Request([], [], [], [], [], [], '{"name":"Dog"}');
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            ValidationContext::REQUEST_ATTRIBUTE,
            [
                ValidationContext::VALIDATED => true,
                ValidationContext::REQUEST_BODY => '{"name":"Dog"}',
            ]
        );
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::DESERIALIZATION_OBJECT => UpdatePet::class,
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->deserializeRequestBody($event);

        static::assertTrue($request->attributes->has(DeserializationContext::REQUEST_ATTRIBUTE));
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernelMock, $request, HttpKernelInterface::MASTER_REQUEST);
    }
}
