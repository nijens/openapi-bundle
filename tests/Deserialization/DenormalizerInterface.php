<?php

namespace Nijens\OpenapiBundle\Tests\Deserialization;

use Symfony\Component\Serializer\Encoder\ContextAwareDecoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface as SymfonyDenormalizerInterface;

interface DenormalizerInterface extends ContextAwareDecoderInterface, SymfonyDenormalizerInterface
{
}
