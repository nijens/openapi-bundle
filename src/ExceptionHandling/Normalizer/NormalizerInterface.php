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

use Nijens\OpenapiBundle\NijensOpenapiBundle;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface as BaseNormalizerInterface;

/*
 * Ensures compatibility with both Symfony versions 5.4 and 7.0.
 *
 * TODO: Remove when support for Symfony 5.4 is dropped.
 */
if (NijensOpenapiBundle::getSymfonyVersion() < 60100) {
    interface NormalizerInterface extends ContextAwareNormalizerInterface
    {
    }
} else {
    interface NormalizerInterface extends BaseNormalizerInterface
    {
    }
}
