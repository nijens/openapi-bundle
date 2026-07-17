<?php

declare(strict_types=1);

namespace Nijens\OpenapiBundle\Tests\Functional\App\Controller;

use Nijens\OpenapiBundle\Deserialization\Attribute\DeserializedObject;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class EchoController
{
    public function __invoke(stdClass $deserializedObject, SerializerInterface $serializer): Response
    {
        return JsonResponse::fromJsonString(
            $serializer->serialize($deserializedObject, 'json')
        );
    }
}
