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

namespace Nijens\OpenapiBundle\Tests\Validation\EventSubscriber;

use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Validation\EventSubscriber\RequestValidationSubscriber;
use Nijens\OpenapiBundle\Validation\RequestValidator\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @var MockObject|ValidatorInterface
     */
    private $requestValidator;

    protected function setUp(): void
    {
        $this->requestValidator = $this->createMock(ValidatorInterface::class);

        $this->subscriber = new RequestValidationSubscriber(
            $this->requestValidator
        );
    }

    public function testCanReturnSubscribedEvents(): void
    {
        $subscribedEvents = RequestValidationSubscriber::getSubscribedEvents();

        $this->assertSame(
            [
                KernelEvents::REQUEST => [
                    ['validateRequest', 7],
                ],
            ],
            $subscribedEvents
        );
    }

    public function testCannotValidateRequestForRouteWithoutRouteContext(): void
    {
        $this->requestValidator->expects($this->never())
            ->method('validate');

        $request = new Request();
        $request->headers->set('Content-Type', 'application/json');

        $event = $this->createRequestEvent($request);

        $this->subscriber->validateRequest($event);
    }

    public function testCanValidateRequestAsValid(): void
    {
        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => [],
                RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS => [],
            ]
        );

        $this->requestValidator->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willReturn(null);

        $event = $this->createRequestEvent($request);

        $this->subscriber->validateRequest($event);
    }

    public function testCanValidateRequestAsInvalid(): void
    {
        $request = new Request();
        $request->attributes->set(
            RouteContext::REQUEST_ATTRIBUTE,
            [
                RouteContext::RESOURCE => __DIR__.'/../../Resources/specifications/json-request-body-validation-subscriber.json',
                RouteContext::REQUEST_BODY_REQUIRED => false,
                RouteContext::REQUEST_ALLOWED_CONTENT_TYPES => [],
                RouteContext::REQUEST_VALIDATE_QUERY_PARAMETERS => [],
            ]
        );

        $exception = new InvalidRequestProblemException(
            ProblemException::DEFAULT_TYPE_URI,
            ProblemException::DEFAULT_TITLE,
            Response::HTTP_BAD_REQUEST,
            'Validation of JSON request body failed.'
        );

        $this->requestValidator->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willReturn($exception);

        $event = $this->createRequestEvent($request);

        $this->expectException(InvalidRequestProblemException::class);
        $this->expectExceptionMessage('Validation of JSON request body failed.');

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
