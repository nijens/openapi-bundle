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

namespace Nijens\OpenapiBundle\Tests\EventListener;

use JsonSchema\Validator;
use Nijens\OpenapiBundle\EventListener\JsonRequestBodyValidationSubscriber;
use Nijens\OpenapiBundle\Exception\BadJsonRequestHttpException;
use Nijens\OpenapiBundle\Exception\InvalidRequestHttpException;
use Nijens\OpenapiBundle\Json\SchemaLoaderInterface;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Routing\RouteLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;

/**
 * Tests the {@see JsonRequestBodyValidationSubscriber}.
 */
class JsonRequestBodyValidationSubscriberTest extends TestCase
{
    /**
     * @var JsonRequestBodyValidationSubscriber
     */
    private $subscriber;

    /**
     * @var MockObject|JsonParser
     */
    private $jsonParserMock;

    /**
     * @var MockObject|SchemaLoaderInterface
     */
    private $schemaLoaderMock;

    /**
     * @var Validator
     */
    private $jsonValidator;

    /**
     * Creates a new {@see JsonRequestBodyValidationSubscriber} instance for testing.
     */
    protected function setUp(): void
    {
        $this->jsonParserMock = $this->getMockBuilder(JsonParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->schemaLoaderMock = $this->getMockBuilder(SchemaLoaderInterface::class)
            ->getMock();

        $this->jsonValidator = new Validator();

        $this->subscriber = new JsonRequestBodyValidationSubscriber(
            $this->jsonParserMock,
            $this->schemaLoaderMock,
            $this->jsonValidator
        );
    }

    /**
     * Tests if {@see JsonRequestBodyValidationSubscriber::getSubscribedEvents} returns the list with expected listeners.
     */
    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = JsonRequestBodyValidationSubscriber::getSubscribedEvents();

        $this->assertSame(
            [
                KernelEvents::REQUEST => [
                    ['validateRequestBody', 28],
                ],
            ],
            $subscribedEvents
        );
    }

    /**
     * Tests if {@see JsonRequestBodyValidationSubscriber::validateRequestBody} skips validation when no {@see Route} is available.
     * This could happen when the priority/order of event listeners is changed, as this listener
     * depends on the output of the {@see RouterListener}.
     */
    public function testValidateRequestBodySkipsValidationWhenRouteIsNotAvailable(): void
    {
        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->never())
            ->method('load');

        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');

        $event = $this->createRequestEvent($request);

        $this->subscriber->validateRequestBody($event);
    }

    /**
     * Tests if {@see JsonRequestBodyValidationSubscriber::validateRequestBody} skips validation when the {@see Route}
     * does not contain the following OpenAPI options set by the {@see RouteLoader}:
     * - The path to the OpenAPI specification file.
     * - The JSON pointer to a JSON Schema in the OpenAPI specification.
     */
    public function testValidateRequestBodySkipsValidationWhenRouteDoesNotContainOpenApiOptions(): void
    {
        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->never())
            ->method('load');

        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');

        $event = $this->createRequestEvent($request);

        $this->subscriber->validateRequestBody($event);
    }

    /**
     * Tests if {@see JsonRequestBodyValidationSubscriber::validateRequestBody} skips validation when the {@see Route}
     * does not contain the following OpenAPI options set by the {@see RouteLoader}:
     * - The JSON pointer to a JSON Schema in the OpenAPI specification.
     */
    public function testValidateRequestBodySkipsValidationWhenRouteDoesNotContainValidationPointer(): void
    {
        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->never())
            ->method('load');

        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->validateRequestBody($event);
    }

    public function testCannotValidateRequestBodyWhenRequestContentTypeNotSet(): void
    {
        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->expectException(BadJsonRequestHttpException::class);
        $this->expectExceptionMessage("The request content-type must be 'application/json'.");

        $this->subscriber->validateRequestBody($event);
    }

    /**
     * Tests if {@see JsonRequestBodyValidationSubscriber::validateRequestBody} throws a {@see InvalidRequestHttpException}
     * when the content-type of the request is not 'application/json'.
     */
    public function testValidateRequestBodyThrowsInvalidRequestHttpExceptionWhenRequestContentTypeInvalid(): void
    {
        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->never())
            ->method('load');

        $request = new Request();
        $request->headers->set('Content-Type', 'application/xml');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->expectException(BadJsonRequestHttpException::class);
        $this->expectExceptionMessage("The request content-type must be 'application/json'.");

        $this->subscriber->validateRequestBody($event);
    }

    /**
     * Tests if {@see JsonRequestBodyValidationSubscriber::validateRequestBody} throws a {@see InvalidRequestHttpException}
     * when the body of the request is not valid JSON.
     */
    public function testValidateRequestBodyThrowsInvalidRequestHttpExceptionWhenRequestBodyIsInvalidJson(): void
    {
        $requestBody = '{"invalid": "json';

        $this->jsonParserMock->expects($this->once())
            ->method('lint')
            ->with($requestBody)
            ->willReturn(new ParsingException('An Invalid JSON error message'));

        $this->schemaLoaderMock->expects($this->never())
            ->method('load');

        $request = new Request([], [], [], [], [], [], $requestBody);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->expectException(BadJsonRequestHttpException::class);
        $this->expectExceptionMessage('The request body should be valid JSON.');

        try {
            $this->subscriber->validateRequestBody($event);
        } catch (InvalidRequestHttpException $exception) {
            // Also assert contents of errors.
            $this->assertSame(
                ['An Invalid JSON error message'],
                $exception->getErrors()
            );

            throw $exception;
        }
    }

    /**
     * Tests if {@see JsonRequestBodyValidationSubscriber::validateRequestBody} throws a {@see InvalidRequestHttpException}
     * when the body of the request does not validate against the JSON Schema.
     */
    public function testValidateRequestBodyThrowsInvalidRequestHttpExceptionWhenRequestBodyDoesNotValidateWithJsonSchema(): void
    {
        $requestBody = '{"invalid": "json"}';

        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->once())
            ->method('load')
            ->with(__DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json')
            ->willReturn(json_decode(file_get_contents(__DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json')));

        $request = new Request([], [], [], [], [], [], $requestBody);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->expectException(InvalidRequestHttpException::class);
        $this->expectExceptionMessage('Validation of JSON request body failed.');

        try {
            $this->subscriber->validateRequestBody($event);
        } catch (InvalidRequestHttpException $exception) {
            // Also assert contents of errors.
            $this->assertSame(
                [
                    [
                        'property' => 'name',
                        'pointer' => '/name',
                        'message' => 'The property name is required',
                        'constraint' => 'required',
                        'context' => 1,
                    ],
                    [
                        'property' => '',
                        'pointer' => '',
                        'message' => 'The property invalid is not defined and the definition does not allow additional properties',
                        'constraint' => 'additionalProp',
                        'context' => 1,
                    ],
                ],
                $exception->getErrors()
            );

            throw $exception;
        }
    }

    /**
     * Tests if {@see JsonRequestBodyValidationSubscriber::validateRequestBody} does not throw exceptions
     * on successful validation.
     *
     * @dataProvider provideRequestContentTypes
     */
    public function testValidateRequestBodySuccessful(string $requestContentType): void
    {
        $requestBody = '{"name": "Dog"}';

        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->once())
            ->method('load')
            ->with(__DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json')
            ->willReturn(json_decode(file_get_contents(__DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json')));

        $request = new Request([], [], [], [], [], [], $requestBody);
        $request->headers->set('Content-Type', $requestContentType);
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->validateRequestBody($event);
    }

    public function provideRequestContentTypes(): array
    {
        return [
            ['application/json'],
            ['application/json; charset=utf-8'],
        ];
    }

    /**
     * Creates a request event.
     */
    private function createRequestEvent(Request $request): RequestEvent
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernelMock, $request, HttpKernelInterface::MASTER_REQUEST);
    }
}
