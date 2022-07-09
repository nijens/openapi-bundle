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

namespace Nijens\OpenapiBundle\Tests\Deserialization\ArgumentResolver;

use Nijens\OpenapiBundle\Deserialization\ArgumentResolver\DeserializedObjectArgumentResolver;
use Nijens\OpenapiBundle\Deserialization\Attribute\DeserializedObject;
use Nijens\OpenapiBundle\Deserialization\DeserializationContext;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Tests\Functional\App\Model\Pet;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class DeserializedObjectArgumentResolverTest extends TestCase
{
    private $argumentResolver;

    protected function setUp(): void
    {
        $this->argumentResolver = new DeserializedObjectArgumentResolver();
    }

    public function testCanSupportDeserializationObjectBasedOnArgumentType(): void
    {
        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::DESERIALIZATION_OBJECT => Pet::class,
            ]
        );
        $request->attributes->set(DeserializationContext::REQUEST_ATTRIBUTE, new Pet(1, 'Dog'));

        $argument = new ArgumentMetadata('foo', Pet::class, false, false, null);

        static::assertTrue($this->argumentResolver->supports($request, $argument));
    }

    public function testCanSupportDeserializationObjectBasedOnAttribute(): void
    {
        if (method_exists(ArgumentMetadata::class, 'getAttributes') === false) {
            static::markTestSkipped('This version of Symfony does not support PHP attributes.');
        }

        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::DESERIALIZATION_OBJECT => Pet::class,
            ]
        );
        $request->attributes->set(DeserializationContext::REQUEST_ATTRIBUTE, new Pet(1, 'Dog'));

        $argument = new ArgumentMetadata('foo', null, false, false, null, false, [new DeserializedObject()]);

        static::assertTrue($this->argumentResolver->supports($request, $argument));
    }

    public function testCanSupportDeserializationObjectBasedOnDeserializationArgumentName(): void
    {
        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::DESERIALIZATION_OBJECT => Pet::class,
                RouteContext::DESERIALIZATION_OBJECT_ARGUMENT_NAME => 'foo',
            ]
        );
        $request->attributes->set(DeserializationContext::REQUEST_ATTRIBUTE, new Pet(1, 'Dog'));

        $argument = new ArgumentMetadata('foo', null, false, false, null);

        static::assertTrue($this->argumentResolver->supports($request, $argument));
    }

    public function testCannotSupportDeserializationObjectWhenNoDeserializationContextInRequest(): void
    {
        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::DESERIALIZATION_OBJECT => Pet::class,
            ]
        );

        $argument = new ArgumentMetadata('foo', Pet::class, false, false, null);

        static::assertFalse($this->argumentResolver->supports($request, $argument));
    }

    public function testCannotSupportDeserializationObjectWhenNoRouteContextInRequest(): void
    {
        $request = new Request();
        $request->attributes->set(DeserializationContext::REQUEST_ATTRIBUTE, new Pet(1, 'Dog'));

        $argument = new ArgumentMetadata('foo', Pet::class, false, false, null);

        static::assertFalse($this->argumentResolver->supports($request, $argument));
    }
}
