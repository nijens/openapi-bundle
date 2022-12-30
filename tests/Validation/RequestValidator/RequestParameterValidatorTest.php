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

use JsonSchema\Validator;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\Violation;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Validation\RequestValidator\RequestParameterValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestParameterValidatorTest extends TestCase
{
    /**
     * @var RequestParameterValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new RequestParameterValidator(
            new Validator()
        );
    }

    public function testCanValidateRequiredRequestParameter(): void
    {
        $request = new Request();
        $request->query->set('foo', 'bar');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS => [
                    'foo' => json_encode([
                        'name' => 'foo',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                        ],
                    ]),
                ],
            ]
        );

        static::assertNull(
            $this->validator->validate($request)
        );
    }

    public function testCannotValidateRequiredRequestParameterWithoutValue(): void
    {
        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS => [
                    'foo' => json_encode([
                        'name' => 'foo',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                        ],
                    ]),
                ],
            ]
        );

        $exception = $this->validator->validate($request);

        static::assertInstanceOf(InvalidRequestProblemException::class, $exception);
        static::assertSame('Validation of query parameters failed.', $exception->getMessage());
        static::assertEquals(
            [
                new Violation('required_query_parameter', 'Query parameter foo is required.', 'foo'),
            ],
            $exception->getViolations()
        );
    }

    public function testCanValidateRequestParameterOfTypeBoolean(): void
    {
        $request = new Request();
        $request->query->set('foo', 'true');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS => [
                    'foo' => json_encode([
                        'name' => 'foo',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'boolean',
                        ],
                    ]),
                ],
            ]
        );

        static::assertNull(
            $this->validator->validate($request)
        );
    }

    public function testCanValidateRequestParameterOfTypeString(): void
    {
        $request = new Request();
        $request->query->set('foo', 'bar');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS => [
                    'foo' => json_encode([
                        'name' => 'foo',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                        ],
                    ]),
                ],
            ]
        );

        static::assertNull(
            $this->validator->validate($request)
        );
    }

    public function testCanValidateRequestParameterOfTypeInteger(): void
    {
        $request = new Request();
        $request->query->set('foo', '1');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS => [
                    'foo' => json_encode([
                        'name' => 'foo',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                        ],
                    ]),
                ],
            ]
        );

        static::assertNull(
            $this->validator->validate($request)
        );
    }

    public function testCannotValidateRequestParameterOfTypeIntegerAsValidWithInvalidValue(): void
    {
        $request = new Request();
        $request->query->set('foo', 'bar');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS => [
                    'foo' => json_encode([
                        'name' => 'foo',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                        ],
                    ]),
                ],
            ]
        );

        $exception = $this->validator->validate($request);

        static::assertInstanceOf(InvalidRequestProblemException::class, $exception);
        static::assertSame('Validation of query parameters failed.', $exception->getMessage());
        static::assertEquals(
            [
                new Violation('type', 'String value found, but an integer is required', 'foo'),
            ],
            $exception->getViolations()
        );
    }
}
