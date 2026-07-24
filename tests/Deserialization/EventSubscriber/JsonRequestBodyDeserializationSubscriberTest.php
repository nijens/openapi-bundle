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

use JsonSchema\Validator;
use Nijens\OpenapiBundle\Deserialization\DeserializationContext;
use Nijens\OpenapiBundle\Deserialization\EventSubscriber\JsonRequestBodyDeserializationSubscriber;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Tests\Deserialization\DenormalizerInterface;
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
     * @var Validator
     */
    private $validator;

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
        $this->validator = $this->createMock(Validator::class);
        $this->serializer = $this->createMock(DenormalizerInterface::class);

        $this->subscriber = new JsonRequestBodyDeserializationSubscriber($this->validator, $this->serializer);
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
        $this->serializer->expects($this->never())->method('decode');

        $request = new Request([], [], [], [], [], [], '{"name":"Dog"}');
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::DESERIALIZATION_OBJECT => UpdatePet::class,
                RouteContext::DESERIALIZATION_PARAMETERS => [],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->deserializeRequestBody($event);

        static::assertFalse($request->attributes->has(DeserializationContext::REQUEST_ATTRIBUTE));
    }

    public function testCannotDeserializeRequestBodyWhenNoDeserializationObjectHasBeenDefinedInRouteContext(): void
    {
        $this->serializer->expects($this->never())->method('decode');

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
            ->method('decode')
            ->with('{"name":"Dog"}', 'json')
            ->willReturn(['name' => 'Dog']);

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
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::DESERIALIZATION_OBJECT => UpdatePet::class,
                RouteContext::DESERIALIZATION_PARAMETERS => [],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->deserializeRequestBody($event);

        static::assertTrue($request->attributes->has(DeserializationContext::REQUEST_ATTRIBUTE));
    }

    public function testCanDeserializeBooleanQueryParameterIntoObject(): void
    {
        $expectedValue = new DeserializationDummy(true);

        $this->serializer->expects($this->never())
            ->method('decode');

        $this->serializer->expects($this->once())
            ->method('denormalize')
            ->with(['boolean' => true], DeserializationDummy::class, 'json')
            ->willReturn($expectedValue);

        $parameter = [
            'name' => 'value',
            'in' => 'query',
            'schema' => [
                'type' => 'boolean',
            ],
            'x-openapi-bundle-deserialize-as' => 'boolean',
        ];

        $request = new Request(['value' => '1'], [], [], [], [], []);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::DESERIALIZATION_OBJECT => DeserializationDummy::class,
                RouteContext::DESERIALIZATION_PARAMETERS => [
                    'value' => json_encode($parameter),
                ],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->deserializeRequestBody($event);

        static::assertSame($expectedValue, $request->attributes->get(DeserializationContext::REQUEST_ATTRIBUTE));
    }

    public function testCanDeserializeIntegerQueryParameterIntoObject(): void
    {
        $expectedValue = new DeserializationDummy(null, 1);

        $this->serializer->expects($this->never())
            ->method('decode');

        $this->serializer->expects($this->once())
            ->method('denormalize')
            ->with(['integer' => 100], DeserializationDummy::class, 'json')
            ->willReturn($expectedValue);

        $parameter = [
            'name' => 'value',
            'in' => 'query',
            'schema' => [
                'type' => 'integer',
            ],
            'x-openapi-bundle-deserialize-as' => 'integer',
        ];

        $request = new Request(['value' => '100'], [], [], [], [], []);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::DESERIALIZATION_OBJECT => DeserializationDummy::class,
                RouteContext::DESERIALIZATION_PARAMETERS => [
                    'value' => json_encode($parameter),
                ],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->deserializeRequestBody($event);

        static::assertSame($expectedValue, $request->attributes->get(DeserializationContext::REQUEST_ATTRIBUTE));
    }

    public function testCanDeserializeStringQueryParameterIntoObject(): void
    {
        $expectedValue = new DeserializationDummy(string: 'a string');

        $this->serializer->expects($this->never())
            ->method('decode');

        $this->serializer->expects($this->once())
            ->method('denormalize')
            ->with(['string' => 'a string'], DeserializationDummy::class, 'json')
            ->willReturn($expectedValue);

        $parameter = [
            'name' => 'value',
            'in' => 'query',
            'schema' => [
                'type' => 'string',
            ],
            'x-openapi-bundle-deserialize-as' => 'string',
        ];

        $request = new Request(['value' => 'a string'], [], [], [], [], []);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::DESERIALIZATION_OBJECT => DeserializationDummy::class,
                RouteContext::DESERIALIZATION_PARAMETERS => [
                    'value' => json_encode($parameter),
                ],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->deserializeRequestBody($event);

        static::assertSame($expectedValue, $request->attributes->get(DeserializationContext::REQUEST_ATTRIBUTE));
    }

    public function testCanDeserializeUnavailableQueryParameterAsNullIntoObject(): void
    {
        $expectedValue = new DeserializationDummy(false);

        $this->serializer->expects($this->never())
            ->method('decode');

        $this->serializer->expects($this->once())
            ->method('denormalize')
            ->with(['boolean' => false], DeserializationDummy::class, 'json')
            ->willReturn($expectedValue);

        $parameter = [
            'name' => 'value',
            'in' => 'query',
            'schema' => [
                'type' => 'boolean',
                'default' => false,
            ],
            'x-openapi-bundle-deserialize-as' => 'boolean',
        ];

        $request = new Request([], [], [], [], [], []);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::DESERIALIZATION_OBJECT => DeserializationDummy::class,
                RouteContext::DESERIALIZATION_PARAMETERS => [
                    'value' => json_encode($parameter),
                ],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->deserializeRequestBody($event);

        static::assertSame($expectedValue, $request->attributes->get(DeserializationContext::REQUEST_ATTRIBUTE));
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent(
            $kernelMock,
            $request,
            defined('Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST
        );
    }
}
