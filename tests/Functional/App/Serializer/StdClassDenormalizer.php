<?php

namespace Nijens\OpenapiBundle\Tests\Functional\App\Serializer;

use stdClass;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class StdClassDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === stdClass::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        // Symfony >= 6.1: allows caching supportsDenormalization results
        return [
            stdClass::class => true,
        ];
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): stdClass
    {
        $obj = new stdClass();

        // If it's not an array/object-like structure, just store it in a conventional property.
        if (!is_array($data)) {
            $obj->value = $data;
            return $obj;
        }

        foreach ($data as $key => $value) {
            // stdClass property names must be strings
            $prop = (string) $key;

            if (is_array($value)) {
                // Heuristic: list => array of stdClass; assoc => stdClass
                if (array_is_list($value)) {
                    $obj->{$prop} = array_map(
                        fn ($item) => is_array($item)
                            ? $this->denormalizer->denormalize($item, stdClass::class, $format, $context)
                            : $item,
                        $value
                    );
                } else {
                    $obj->{$prop} = $this->denormalizer->denormalize($value, stdClass::class, $format, $context);
                }
            } else {
                $obj->{$prop} = $value;
            }
        }

        return $obj;
    }
}
