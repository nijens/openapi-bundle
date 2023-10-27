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

namespace Nijens\OpenapiBundle\ExceptionHandling\Normalizer;

use Nijens\OpenapiBundle\ExceptionHandling\Exception\ProblemExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Throwable;

/**
 * Normalizes a {@see Throwable} implementing the {@see ProblemExceptionInterface}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
final class ProblemExceptionNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'nijens_openapi.problem_exception_normalizer.already_called';

    /**
     * @var bool
     */
    private $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * @return array
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if ($object instanceof ProblemExceptionInterface === false) {
            throw new InvalidArgumentException(sprintf('The object must implement "%s".', ProblemExceptionInterface::class));
        }

        if (isset($context[self::ALREADY_CALLED])) {
            throw new LogicException(sprintf('The normalizer "%s" can only be called once.', self::class));
        }

        $context[self::ALREADY_CALLED] = true;

        $data = $this->normalizer->normalize($object, $format, $context);

        $this->removeDetailsToPreventInformationDisclosure($object, $data);

        return $this->unsetKeysWithNullValue($data);
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof ProblemExceptionInterface;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ProblemExceptionInterface::class => false,
        ];
    }

    private function removeDetailsToPreventInformationDisclosure(ProblemExceptionInterface $object, array &$data): void
    {
        if ($this->debug) {
            return;
        }

        if ($object->getPrevious() === null || $object->getPrevious() instanceof HttpExceptionInterface) {
            return;
        }

        unset($data['detail']);
    }

    private function unsetKeysWithNullValue(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->unsetKeysWithNullValue($value);
            }

            if ($value === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
