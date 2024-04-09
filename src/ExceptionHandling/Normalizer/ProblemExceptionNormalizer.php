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

use ArrayObject;
use Nijens\OpenapiBundle\NijensOpenapiBundle;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;

/*
 * Normalizes a {@see Throwable} implementing the {@see ProblemExceptionInterface}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
if (NijensOpenapiBundle::getSymfonyVersion() < 70000) {
    final class ProblemExceptionNormalizer extends AbstractProblemExceptionNormalizer implements NormalizerInterface, NormalizerAwareInterface
    {
        /**
         * @return array
         */
        public function normalize($object, $format = null, array $context = [])
        {
            return $this->doNormalize($object, $format, $context);
        }
    }
} else {
    final class ProblemExceptionNormalizer extends AbstractProblemExceptionNormalizer implements NormalizerInterface, NormalizerAwareInterface
    {
        public function normalize($object, $format = null, array $context = []): float|int|bool|ArrayObject|array|string|null
        {
            return $this->doNormalize($object, $format, $context);
        }
    }
}
