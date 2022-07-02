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

namespace Nijens\OpenapiBundle\DependencyInjection;

use Symfony\Component\Serializer\Serializer;
use Traversable;

/**
 * Creates services that can not be created through only service container configuration.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
final class ServiceFactory
{
    public static function createSerializer($normalizers, $encoders): Serializer
    {
        if ($normalizers instanceof Traversable) {
            $normalizers = iterator_to_array($normalizers);
        }

        if ($encoders instanceof Traversable) {
            $encoders = iterator_to_array($encoders);
        }

        return new Serializer($normalizers, $encoders);
    }
}
