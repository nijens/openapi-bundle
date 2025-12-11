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
use Nijens\OpenapiBundle\Validation\RequestValidator\RequestPathParameterValidator;
use Nijens\OpenapiBundle\Validation\ValidationContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestPathParameterValidatorTest extends TestCase
{
    /**
     * @var RequestPathParameterValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new RequestPathParameterValidator(
            new Validator()
        );
    }

    public function testCanValidateRequiredRequestParameter(): void
    {
        $request = new Request();
        $request->attributes->set('_route_params', ['lorum' => 'ipsum']);
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_VALIDATE_PATH_PARAMETERS => [
                    'lorum' => json_encode([
                        'name' => 'lorum',
                        'in' => 'path',
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

        $validatedContent = $request->attributes->get(ValidationContext::REQUEST_ATTRIBUTE);
        static::assertEquals([
            'lorum' => 'ipsum',
        ], \json_decode($validatedContent[ValidationContext::REQUEST_PATH_PARAMETERS], true));
    }

    public function testCannotValidateRequiredRequestParameterWithoutValue(): void
    {
        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_VALIDATE_PATH_PARAMETERS => [
                    'foo' => json_encode([
                        'name' => 'foo',
                        'in' => 'path',
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
        static::assertSame('Validation of path parameters failed.', $exception->getMessage());
        static::assertEquals(
            [
                new Violation('required_path_parameter', 'Path parameter foo is required.', 'foo'),
            ],
            $exception->getViolations()
        );
    }

    public function testCanValidateRequestParameterOfTypeBoolean(): void
    {
        $request = new Request();
        $request->attributes->set('_route_params', ['foo' => 'true']);
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_VALIDATE_PATH_PARAMETERS => [
                    'foo' => json_encode([
                        'name' => 'foo',
                        'in' => 'path',
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
        $request->attributes->set('_route_params', ['foo' => 'bar']);
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_VALIDATE_PATH_PARAMETERS => [
                    'foo' => json_encode([
                        'name' => 'foo',
                        'in' => 'path',
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
        $request->attributes->set('_route_params', ['foo' => '1']);
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_VALIDATE_PATH_PARAMETERS => [
                    'foo' => json_encode([
                        'name' => 'foo',
                        'in' => 'path',
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
        $request->attributes->set('_route_params', ['foo' => 'bar']);
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_VALIDATE_PATH_PARAMETERS => [
                    'foo' => json_encode([
                        'name' => 'foo',
                        'in' => 'path',
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
        static::assertSame('Validation of path parameters failed.', $exception->getMessage());
        static::assertEquals(
            [
                new Violation('type', 'String value found, but an integer is required', 'foo'),
            ],
            $exception->getViolations()
        );
    }
}
