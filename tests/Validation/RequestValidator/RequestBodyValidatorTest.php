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
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestBodyProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\Violation;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Validation\RequestValidator\RequestBodyValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Symfony\Component\HttpFoundation\Request;

class RequestBodyValidatorTest extends TestCase
{
    /**
     * @var RequestBodyValidator
     */
    private $validator;

    /**
     * @var MockObject|JsonParser
     */
    private $jsonParserMock;

    protected function setUp(): void
    {
        $this->jsonParserMock = $this->getMockBuilder(JsonParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new RequestBodyValidator(
            $this->jsonParserMock,
            new Validator()
        );
    }

    public function testCanValidateRequestBodyAsInvalidJsonSyntax(): void
    {
        $requestBody = '{"invalid": "json';

        $this->jsonParserMock->expects($this->once())
            ->method('lint')
            ->with($requestBody)
            ->willReturn(new ParsingException('An Invalid JSON error message'));

        $request = new Request([], [], [], [], [], [], $requestBody);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_SCHEMA => $this->createRequestBodySchema(),
            ]
        );

        $exception = $this->validator->validate($request);

        static::assertInstanceOf(InvalidRequestBodyProblemException::class, $exception);
        static::assertSame('The request body must be valid JSON.', $exception->getMessage());
        static::assertEquals(
            [
                new Violation('valid_json', 'An Invalid JSON error message'),
            ],
            $exception->getViolations()
        );
    }

    public function testCanValidateRequestBodyAsInvalidJsonAccordingToSchema(): void
    {
        $requestBody = '{"invalid": "json"}';

        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $request = new Request([], [], [], [], [], [], $requestBody);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_SCHEMA => $this->createRequestBodySchema(),
            ]
        );

        $exception = $this->validator->validate($request);

        static::assertInstanceOf(InvalidRequestBodyProblemException::class, $exception);
        static::assertSame('Validation of JSON request body failed.', $exception->getMessage());
        static::assertEquals(
            [
                new Violation('required', 'The property name is required', 'name'),
                new Violation(
                    'additionalProp',
                    'The property invalid is not defined and the definition does not allow additional properties'
                ),
            ],
            $exception->getViolations()
        );
    }

    public function testCanValidateRequestBodyAsValidJsonAccordingToSchema(): void
    {
        $requestBody = '{"name": "Kitty"}';

        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $request = new Request([], [], [], [], [], [], $requestBody);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_SCHEMA => $this->createRequestBodySchema(),
            ]
        );

        static::assertNull(
            $this->validator->validate($request)
        );
    }

    public function testCannotValidateRequestBodyWhenRequestContentTypeIsSomethingOtherThanJson(): void
    {
        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $request = new Request();
        $request->headers->set('Content-Type', 'text/plain');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::REQUEST_BODY_SCHEMA => $this->createRequestBodySchema(),
            ]
        );

        static::assertNull(
            $this->validator->validate($request)
        );
    }

    public function testCannotValidateRequestBodyWhenNoRequestBodySchemaIsDefined(): void
    {
        $requestBody = '{"name": "Kitty"}';

        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $request = new Request([], [], [], [], [], [], $requestBody);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            []
        );

        static::assertNull(
            $this->validator->validate($request)
        );
    }

    private function createRequestBodySchema(): string
    {
        return serialize((object) [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'format' => 'int32',
                    'readOnly' => true,
                    'example' => 1,
                ],
                'name' => [
                    'type' => 'string',
                    'example' => 'Dog',
                ],
            ],
            'additionalProperties' => false,
            'required' => [
                'name',
            ],
        ]);
    }
}
