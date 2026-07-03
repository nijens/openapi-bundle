<?php

namespace Nijens\OpenapiBundle\Tests\Deserialization\EventSubscriber;

class DeserializationDummy
{
    public function __construct(
        private bool|null $boolean = null,
        private int|null $int = null,
        private string|null $string = null,
    ) {
    }
}
