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

namespace Nijens\OpenapiBundle\Tests\Validation\RequestValidator;

use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidContentTypeProblemException;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Validation\RequestValidator\RequestContentTypeValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestContentTypeValidatorTest extends TestCase
{
    /**
     * @var RequestContentTypeValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new RequestContentTypeValidator();
    }

    public function testCanValidateContentType(): void
    {
        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_REQUIRED => true,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => ['application/json'],
            ]
        );

        static::assertNull(
            $this->validator->validate($request)
        );
    }

    public function testCannotValidateContentTypeWhenNoAllowedTypesAreDefined(): void
    {
        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_REQUIRED => true,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => [],
            ]
        );

        static::assertNull(
            $this->validator->validate($request)
        );
    }

    public function testCannotValidateContentTypeWhenRequestDoesNotHaveContentTypeAndRequestBodyIsNotRequired(): void
    {
        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => ['application/json'],
            ]
        );

        static::assertNull(
            $this->validator->validate($request)
        );
    }

    public function testCannotValidateContentTypeAsValidWhenRequestDoesNotHaveContentTypeAndRequestBodyIsRequired(): void
    {
        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_REQUIRED => true,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => ['application/json'],
            ]
        );

        $exception = $this->validator->validate($request);

        static::assertInstanceOf(InvalidContentTypeProblemException::class, $exception);
        static::assertSame(
            "The request content-type '' is not supported. (Supported: application/json)",
            $exception->getMessage()
        );
    }

    public function testCannotValidateContentTypeAsValidWhenRequestContentTypeNotAllowed(): void
    {
        $request = new Request();
        $request->headers->set('Content-Type', 'text/plain');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_REQUIRED => true,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => ['application/json'],
            ]
        );

        $exception = $this->validator->validate($request);

        static::assertInstanceOf(InvalidContentTypeProblemException::class, $exception);
        static::assertSame(
            "The request content-type 'text/plain' is not supported. (Supported: application/json)",
            $exception->getMessage()
        );
    }
}
