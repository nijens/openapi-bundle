<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Tests\Validation\EventSubscriber;

use JsonSchema\Validator;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidContentTypeProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestBodyProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\Violation;
use Nijens\OpenapiBundle\Json\SchemaLoaderInterface;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Validation\EventSubscriber\RequestValidationSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestValidationSubscriberTest extends TestCase
{
    /**
     * @var RequestValidationSubscriber
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

    protected function setUp(): void
    {
        $this->jsonParserMock = $this->getMockBuilder(JsonParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->schemaLoaderMock = $this->getMockBuilder(SchemaLoaderInterface::class)
            ->getMock();

        $this->jsonValidator = new Validator();

        $this->subscriber = new RequestValidationSubscriber(
            $this->jsonParserMock,
            $this->schemaLoaderMock,
            $this->jsonValidator
        );
    }

    public function testCanReturnSubscribedEvents(): void
    {
        $subscribedEvents = RequestValidationSubscriber::getSubscribedEvents();

        $this->assertSame(
            [
                KernelEvents::REQUEST => [
                    ['validateRequest', 28],
                ],
            ],
            $subscribedEvents
        );
    }

    public function testCannotValidateRequestForRouteWithoutRouteContext(): void
    {
        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->never())
            ->method('load');

        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');

        $event = $this->createRequestEvent($request);

        $this->subscriber->validateRequest($event);
    }

    public function testCannotValidateRequestWithoutContentTypeAndRequestBodyIsNotRequired(): void
    {
        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->never())
            ->method('load');

        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => ['application/json'],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->validateRequest($event);
    }

    public function testCannotValidateRequestBodyWhenRequestContentTypeNotSet(): void
    {
        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
                RouteContext::REQUEST_BODY_REQUIRED => true,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => ['application/json'],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->expectException(InvalidContentTypeProblemException::class);
        $this->expectExceptionMessage("The request content-type '' is not supported. (Supported: application/json)");

        $this->subscriber->validateRequest($event);
    }

    public function testCannotValidateRequestBodyWhenRequestContentTypeNotSupported(): void
    {
        $request = new Request();
        $request->headers->set('Content-Type', 'text/plain');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
                RouteContext::REQUEST_BODY_REQUIRED => true,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => ['application/json'],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->expectException(InvalidContentTypeProblemException::class);
        $this->expectExceptionMessage("The request content-type 'text/plain' is not supported. (Supported: application/json)");

        $this->subscriber->validateRequest($event);
    }

    public function testCannotValidateRequestBodyWhenNoRequestBodySchemaIsDefined(): void
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
                RouteContext::RESOURCE => __DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => [],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->validateRequest($event);
    }

    public function testCanValidateRequestBodyAsInvalidJsonSyntax(): void
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
                RouteContext::RESOURCE => __DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
                RouteContext::REQUEST_BODY_REQUIRED => true,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => ['application/json'],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->expectException(InvalidRequestBodyProblemException::class);
        $this->expectExceptionMessage('Validation of JSON request body failed.');

        try {
            $this->subscriber->validateRequest($event);
        } catch (InvalidRequestBodyProblemException $exception) {
            // Also assert contents of violations.
            $this->assertEquals(
                [
                    new Violation('valid_json', 'An Invalid JSON error message'),
                ],
                $exception->getViolations()
            );

            throw $exception;
        }
    }

    public function testCanValidateRequestBodyAsInvalidJsonAccordingToSchema(): void
    {
        $requestBody = '{"invalid": "json"}';

        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->once())
            ->method('load')
            ->with(__DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json')
            ->willReturn(json_decode(file_get_contents(__DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json')));

        $request = new Request([], [], [], [], [], [], $requestBody);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
                RouteContext::REQUEST_BODY_REQUIRED => true,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => ['application/json'],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->expectException(InvalidRequestBodyProblemException::class);
        $this->expectExceptionMessage('Validation of JSON request body failed.');

        try {
            $this->subscriber->validateRequest($event);
        } catch (InvalidRequestBodyProblemException $exception) {
            // Also assert contents of violations.
            $this->assertEquals(
                [
                    new Violation('required', 'The property name is required', 'name'),
                    new Violation('additionalProp', 'The property invalid is not defined and the definition does not allow additional properties'),
                ],
                $exception->getViolations()
            );

            throw $exception;
        }
    }

    public function testCanValidateRequestBodyAsValidJsonAccordingToSchema(): void
    {
        $requestBody = '{"name": "Kitty"}';

        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->once())
            ->method('load')
            ->with(__DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json')
            ->willReturn(json_decode(file_get_contents(__DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json')));

        $request = new Request([], [], [], [], [], [], $requestBody);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
                RouteContext::REQUEST_BODY_REQUIRED => true,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => ['application/json'],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->validateRequest($event);
    }

    public function testCanOnlyValidateRequestContentTypeForNonJsonMimeTypes(): void
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
                RouteContext::RESOURCE => __DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::JSON_REQUEST_VALIDATION_POINTER => '/paths/~1pets/put/requestBody/content/application~1json/schema',
                RouteContext::REQUEST_BODY_REQUIRED => true,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => ['application/json', 'application/xml'],
            ]
        );

        $event = $this->createRequestEvent($request);

        $this->subscriber->validateRequest($event);
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
