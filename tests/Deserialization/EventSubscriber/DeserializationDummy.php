<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Tests\Deserialization\EventSubscriber;

class DeserializationDummy
{
    public function __construct(
        private ?bool $boolean = null,
        private ?int $int = null,
        private ?string $string = null,
    ) {
    }
}
