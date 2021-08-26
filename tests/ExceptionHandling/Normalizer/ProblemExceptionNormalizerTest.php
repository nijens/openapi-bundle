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

namespace Nijens\OpenapiBundle\Tests\ExceptionHandling\Normalizer;

use Error;
use JsonSerializable;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestBodyProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\Violation;
use Nijens\OpenapiBundle\ExceptionHandling\Normalizer\ProblemExceptionNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Tests the {@see ProblemExceptionNormalizer}.
 */
class ProblemExceptionNormalizerTest extends TestCase
{
    /**
     * @var ProblemExceptionNormalizer
     */
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ProblemExceptionNormalizer(true);
        $this->createAndSetNormalizerAwareness($this->normalizer);
    }

    public function testSupportsProblemException(): void
    {
        $throwable = new ProblemException('about:blank', 'An error occurred.', 500);

        static::assertTrue(
            $this->normalizer->supportsNormalization($throwable)
        );
    }

    public function testCannotSupportProblemExceptionWhenNormalizerIsAlreadyCalled(): void
    {
        $throwable = new ProblemException('about:blank', 'An error occurred.', 500);

        static::assertFalse(
            $this->normalizer->supportsNormalization(
                $throwable,
                null,
                ['nijens_openapi.problem_exception_normalizer.already_called' => true]
            )
        );
    }

    public function testCannotSupportOtherObjects(): void
    {
        $throwable = new Error('An error occurred.');

        static::assertFalse(
            $this->normalizer->supportsNormalization($throwable)
        );
    }

    public function testCanNormalizeProblemException(): void
    {
        $throwable = new ProblemException('about:blank', 'An error occurred.', 500);

        static::assertSame(
            [
                'type' => 'about:blank',
                'title' => 'An error occurred.',
                'status' => 500,
                'detail' => '',
            ],
            $this->normalizer->normalize($throwable)
        );
    }

    public function testCanPreventInformationDisclosureByRemovingDetails(): void
    {
        $this->normalizer = new ProblemExceptionNormalizer(false);
        $this->createAndSetNormalizerAwareness($this->normalizer);

        $throwable = ProblemException::fromThrowable(new Error('Syntax error.'));

        static::assertSame(
            [
                'type' => 'about:blank',
                'title' => 'An error occurred.',
                'status' => 500,
            ],
            $this->normalizer->normalize($throwable)
        );
    }

    public function testCanRemoveKeysWithNullValueDuringNormalization(): void
    {
        $throwable = new InvalidRequestBodyProblemException(
            'about:blank',
            'The provided request body contains errors.',
            400,
            'The request body should be valid JSON.',
            null,
            null,
            [],
            [
                new Violation('valid_json', 'Invalid JSON.'),
            ]
        );

        static::assertSame(
            [
                'type' => 'about:blank',
                'title' => 'The provided request body contains errors.',
                'status' => 400,
                'detail' => 'The request body should be valid JSON.',
                'violations' => [
                    [
                        'constraint' => 'valid_json',
                        'message' => 'Invalid JSON.',
                    ],
                ],
            ],
            $this->normalizer->normalize($throwable)
        );
    }

    public function testCannotNormalizeOtherObjects(): void
    {
        $throwable = new Error('An error occurred.');

        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage(
            'The object must implement "Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemExceptionInterface".'
        );

        $this->normalizer->normalize($throwable);
    }

    public function testCannotNormalizeProblemExceptionWhenNormalizerIsAlreadyCalled(): void
    {
        $throwable = new ProblemException('about:blank', 'An error occurred.', 500);

        static::expectException(LogicException::class);
        static::expectExceptionMessage(
            'The normalizer "Nijens\OpenapiBundle\ExceptionHandling\Normalizer\ProblemExceptionNormalizer" can only be called once.'
        );

        $this->normalizer->normalize(
            $throwable,
            null,
            ['nijens_openapi.problem_exception_normalizer.already_called' => true]
        );
    }

    private function createAndSetNormalizerAwareness(ProblemExceptionNormalizer $normalizer): void
    {
        $normalizerForAwareness = $this->createMock(NormalizerInterface::class);
        $normalizerForAwareness->method('normalize')
            ->willReturnCallback(function (JsonSerializable $data) {
                return json_decode(json_encode($data), true);
            });

        $normalizer->setNormalizer($normalizerForAwareness);
    }
}
