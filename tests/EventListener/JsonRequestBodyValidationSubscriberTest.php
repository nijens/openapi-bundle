<?php

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
use League\JsonReference\Dereferencer;
use League\JsonReference\ReferenceSerializer\InlineReferenceSerializer;
use Nijens\OpenapiBundle\EventListener\JsonRequestBodyValidationSubscriber;
use Nijens\OpenapiBundle\Exception\BadJsonRequestHttpException;
use Nijens\OpenapiBundle\Exception\InvalidRequestHttpException;
use Nijens\OpenapiBundle\Json\SchemaLoaderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * JsonRequestBodyValidationSubscriberTest.
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
     * Creates a new JsonRequestBodyValidationSubscriber instance for testing.
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
     * Tests if JsonRequestBodyValidationSubscriber::getSubscribedEvents returns the list with expected listeners.
     */
    public function testGetSubscribedEvents()
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
     * Tests if constructing a new JsonRequestBodyValidationSubscriber instance sets the instance properties.
     */
    public function testConstruct()
    {
        $this->assertAttributeSame($this->jsonParserMock, 'jsonParser', $this->subscriber);
        $this->assertAttributeSame($this->schemaLoaderMock, 'schemaLoader', $this->subscriber);
        $this->assertAttributeSame($this->jsonValidator, 'jsonValidator', $this->subscriber);
    }

    /**
     * Tests if JsonRequestBodyValidationSubscriber::validateRequestBody skips validation when no Route is available.
     * This could happen when the priority/order of event listeners is changed, as this listener
     * depends on the output of the RouterListener.
     *
     * @depends testConstruct
     */
    public function testValidateRequestBodySkipsValidationWhenRouteIsNotAvailable()
    {
        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->never())
            ->method('load');

        /** @var MockObject|HttpKernelInterface $kernelMock */
        $kernelMock = $this->getMockBuilder(HttpKernelInterface::class)
            ->getMock();

        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');

        $event = new RequestEvent($kernelMock, $request, HttpKernelInterface::MASTER_REQUEST);

        $this->subscriber->validateRequestBody($event);
    }

    /**
     * Tests if JsonRequestBodyValidationSubscriber::validateRequestBody skips validation when the Route
     * does not contain the following OpenAPI options set by the RouteLoader:
     * - The path to the OpenAPI specification file.
     * - The JSON pointer to a JSON Schema in the OpenAPI specification.
     *
     * @depends testConstruct
     */
    public function testValidateRequestBodySkipsValidationWhenRouteDoesNotContainOpenApiOptions()
    {
        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->never())
            ->method('load');

        /** @var MockObject|HttpKernelInterface $kernelMock */
        $kernelMock = $this->getMockBuilder(HttpKernelInterface::class)
            ->getMock();

        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');

        $event = new RequestEvent($kernelMock, $request, HttpKernelInterface::MASTER_REQUEST);

        $this->subscriber->validateRequestBody($event);
    }

    /**
     * Tests if JsonRequestBodyValidationSubscriber::validateRequestBody skips validation when the Route
     * does not contain the following OpenAPI options set by the RouteLoader:
     * - The JSON pointer to a JSON Schema in the OpenAPI specification.
     *
     * @depends testConstruct
     */
    public function testValidateRequestBodySkipsValidationWhenRouteDoesNotContainValidationPointer()
    {
        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->never())
            ->method('load');

        /** @var MockObject|HttpKernelInterface $kernelMock */
        $kernelMock = $this->getMockBuilder(HttpKernelInterface::class)
            ->getMock();

        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set('_nijens_openapi', [
            'openapi_resource' => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
        ]);

        $event = new RequestEvent($kernelMock, $request, HttpKernelInterface::MASTER_REQUEST);

        $this->subscriber->validateRequestBody($event);
    }

    /**
     * Tests if JsonRequestBodyValidationSubscriber::validateRequestBody throws a InvalidRequestHttpException
     * when the content-type of the request is not 'application/json'.
     *
     * @depends testConstruct
     */
    public function testValidateRequestBodyThrowsInvalidRequestHttpExceptionWhenRequestContentTypeInvalid()
    {
        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $this->schemaLoaderMock->expects($this->never())
            ->method('load');

        /** @var MockObject|HttpKernelInterface $kernelMock */
        $kernelMock = $this->getMockBuilder(HttpKernelInterface::class)
            ->getMock();

        $request = new Request();
        $request->headers->set('Content-Type', 'application/xml');
        $request->attributes->set('_nijens_openapi', [
            'openapi_resource' => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
            'openapi_json_request_validation_pointer' => '/paths/~1pets/put/requestBody/content/application~1json/schema',
        ]);

        $event = new RequestEvent($kernelMock, $request, HttpKernelInterface::MASTER_REQUEST);

        $this->expectException(BadJsonRequestHttpException::class);
        $this->expectExceptionMessage("The request content-type should be 'application/json'.");

        $this->subscriber->validateRequestBody($event);
    }

    /**
     * Tests if JsonRequestBodyValidationSubscriber::validateRequestBody throws a InvalidRequestHttpException
     * when the body of the request is not valid JSON.
     *
     * @depends testConstruct
     */
    public function testValidateRequestBodyThrowsInvalidRequestHttpExceptionWhenRequestBodyIsInvalidJson()
    {
        $requestBody = '{"invalid": "json';

        $this->jsonParserMock->expects($this->once())
            ->method('lint')
            ->with($requestBody)
            ->willReturn(new ParsingException('An Invalid JSON error message'));

        $this->schemaLoaderMock->expects($this->never())
            ->method('load');

        /** @var MockObject|HttpKernelInterface $kernelMock */
        $kernelMock = $this->getMockBuilder(HttpKernelInterface::class)
            ->getMock();

        $request = new Request([], [], [], [], [], [], $requestBody);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set('_nijens_openapi', [
            'openapi_resource' => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
            'openapi_json_request_validation_pointer' => '/paths/~1pets/put/requestBody/content/application~1json/schema',
        ]);

        $event = new RequestEvent($kernelMock, $request, HttpKernelInterface::MASTER_REQUEST);

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
     * Tests if JsonRequestBodyValidationSubscriber::validateRequestBody throws a InvalidRequestHttpException
     * when the body of the request does not validate against the JSON Schema.
     *
     * @depends testConstruct
     */
    public function testValidateRequestBodyThrowsInvalidRequestHttpExceptionWhenRequestBodyDoesNotValidateWithJsonSchema()
    {
        $requestBody = '{"invalid": "json"}';

        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $schemaLoaderDereferencer = new Dereferencer(null, new InlineReferenceSerializer());

        $this->schemaLoaderMock->expects($this->once())
            ->method('load')
            ->with(__DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json')
            ->willReturn($schemaLoaderDereferencer->dereference('file://'.__DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json'));

        /** @var MockObject|HttpKernelInterface $kernelMock */
        $kernelMock = $this->getMockBuilder(HttpKernelInterface::class)
            ->getMock();

        $request = new Request([], [], [], [], [], [], $requestBody);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set('_nijens_openapi', [
            'openapi_resource' => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
            'openapi_json_request_validation_pointer' => '/paths/~1pets/put/requestBody/content/application~1json/schema',
        ]);

        $event = new RequestEvent($kernelMock, $request, HttpKernelInterface::MASTER_REQUEST);

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
     * Tests if JsonRequestBodyValidationSubscriber::validateRequestBody does not throw exceptions
     * on successful validation.
     */
    public function testValidateRequestBodySuccessful()
    {
        $requestBody = '{"name": "Dog"}';

        $this->jsonParserMock->expects($this->never())
            ->method('lint');

        $schemaLoaderDereferencer = new Dereferencer(null, new InlineReferenceSerializer());

        $this->schemaLoaderMock->expects($this->once())
            ->method('load')
            ->with(__DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json')
            ->willReturn($schemaLoaderDereferencer->dereference('file://'.__DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json'));

        /** @var MockObject|HttpKernelInterface $kernelMock */
        $kernelMock = $this->getMockBuilder(HttpKernelInterface::class)
            ->getMock();

        $request = new Request([], [], [], [], [], [], $requestBody);
        $request->headers->set('Content-Type', 'application/json');
        $request->attributes->set('_nijens_openapi', [
            'openapi_resource' => __DIR__.'/../Resources/specifications/json-request-body-validation-subscriber.json',
            'openapi_json_request_validation_pointer' => '/paths/~1pets/put/requestBody/content/application~1json/schema',
        ]);

        $event = new RequestEvent($kernelMock, $request, HttpKernelInterface::MASTER_REQUEST);

        $this->subscriber->validateRequestBody($event);
    }
}
