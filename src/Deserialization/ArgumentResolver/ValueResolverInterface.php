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

namespace Nijens\OpenapiBundle\Deserialization\ArgumentResolver;

/*
 * Ensures compatibility with both Symfony versions 5.4 and 7.0.
 *
 * TODO: Remove when support for Symfony 6.4 is dropped.
 */

use Nijens\OpenapiBundle\NijensOpenapiBundle;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface as BaseValueResolverInterface;

if (NijensOpenapiBundle::getSymfonyVersion() < 60200) {
    interface ValueResolverInterface extends ArgumentValueResolverInterface
    {
    }
} else {
    interface ValueResolverInterface extends BaseValueResolverInterface
    {
    }
}
