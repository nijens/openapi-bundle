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

namespace Nijens\OpenapiBundle\Tests\ExceptionHandling\Exception;

use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests a {@see ProblemExceptionInterface} implementation.
 */
abstract class AbstractProblemExceptionTest extends TestCase
{
    /**
     * @var ProblemExceptionInterface
     */
    protected $exception;

    public function testCanSetTypeUri(): void
    {
        $typeUri = 'https://example.com/error';

        $exception = $this->exception->withTypeUri($typeUri);

        static::assertNotSame($this->exception, $exception);
        static::assertProblemExceptionEqualsExcludingProperty($this->exception, $exception, 'type');
        static::assertSame($typeUri, $exception->getTypeUri());
    }

    public function testCanSetTitle(): void
    {
        $title = 'An error occurred';

        $exception = $this->exception->withTitle($title);

        static::assertNotSame($this->exception, $exception);
        static::assertProblemExceptionEqualsExcludingProperty($this->exception, $exception, 'title');
        static::assertSame($title, $exception->getTitle());
    }

    public function testCanSetInstanceUri(): void
    {
        $instanceUri = 'https://example.com/instance/123';

        $exception = $this->exception->withInstanceUri($instanceUri);

        static::assertNotSame($this->exception, $exception);
        static::assertProblemExceptionEqualsExcludingProperty($this->exception, $exception, 'instance');
        static::assertSame($instanceUri, $exception->getInstanceUri());
    }

    public function testCanSetStatusCode(): void
    {
        $statusCode = 400;

        $exception = $this->exception->withStatusCode($statusCode);

        static::assertNotSame($this->exception, $exception);
        static::assertProblemExceptionEqualsExcludingProperty($this->exception, $exception, 'status');
        static::assertSame($statusCode, $exception->getStatusCode());
    }

    public function testCanSetHeaders(): void
    {
        $headers = ['Allow' => 'GET'];

        $exception = $this->exception->withHeaders($headers);

        static::assertNotSame($this->exception, $exception);
        static::assertProblemExceptionEqualsExcludingProperty($this->exception, $exception, 'headers');
        static::assertSame($headers, $exception->getHeaders());
    }

    /**
     * Asserts that two {@see ProblemExceptionInterface} implementations are equal not including the excluded property.
     */
    public static function assertProblemExceptionEqualsExcludingProperty(
        ProblemExceptionInterface $expected,
        ProblemExceptionInterface $actual,
        string $excludedProperty,
    ): void {
        $expected = $expected->jsonSerialize();
        $actual = $actual->jsonSerialize();

        unset($expected[$excludedProperty], $actual[$excludedProperty]);

        static::assertSame($expected, $actual);
    }
}
