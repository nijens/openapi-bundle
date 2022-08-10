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

use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Nijens\OpenapiBundle\Validation\RequestValidator\CompositeRequestValidator;
use Nijens\OpenapiBundle\Validation\RequestValidator\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompositeRequestValidatorTest extends TestCase
{
    /**
     * @var CompositeRequestValidator
     */
    private $validator;

    /**
     * @var MockObject|ValidatorInterface
     */
    private $firstValidator;

    /**
     * @var MockObject|ValidatorInterface
     */
    private $secondValidator;

    protected function setUp(): void
    {
        $this->firstValidator = $this->createMock(ValidatorInterface::class);
        $this->secondValidator = $this->createMock(ValidatorInterface::class);

        $this->validator = new CompositeRequestValidator([
            $this->firstValidator,
            $this->secondValidator,
        ]);
    }

    public function testCanValidate(): void
    {
        $request = new Request();

        $this->firstValidator->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willReturn(null);

        $this->secondValidator->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willReturn(null);

        static::assertNull(
            $this->validator->validate($request)
        );
    }

    public function testCanValidateAsInvalidWhenFirstValidatorReturnsException(): void
    {
        $request = new Request();

        $exception = new InvalidRequestProblemException(
            ProblemException::DEFAULT_TYPE_URI,
            ProblemException::DEFAULT_TITLE,
            Response::HTTP_BAD_REQUEST
        );

        $this->firstValidator->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willReturn($exception);

        $this->secondValidator->expects($this->never())
            ->method('validate');

        static::assertSame(
            $exception,
            $this->validator->validate($request)
        );
    }

    public function testCanValidateAsInvalidWhenSecondValidatorReturnsException(): void
    {
        $request = new Request();

        $exception = new InvalidRequestProblemException(
            ProblemException::DEFAULT_TYPE_URI,
            ProblemException::DEFAULT_TITLE,
            Response::HTTP_BAD_REQUEST
        );

        $this->firstValidator->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willReturn(null);

        $this->secondValidator->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willReturn($exception);

        static::assertSame(
            $exception,
            $this->validator->validate($request)
        );
    }
}
