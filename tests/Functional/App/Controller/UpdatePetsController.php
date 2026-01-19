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

namespace Nijens\OpenapiBundle\Tests\Functional\App\Controller;

use Nijens\OpenapiBundle\Deserialization\Attribute\DeserializedObject;
use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Serialization\SerializationContextBuilderInterface;
use Nijens\OpenapiBundle\Tests\Functional\App\Model\UpdatePet;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractUpdatePetsController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SerializationContextBuilderInterface
     */
    private $serializationContextBuilder;

    public function __construct(
        SerializerInterface $serializer,
        SerializationContextBuilderInterface $serializationContextBuilder,
    ) {
        $this->serializer = $serializer;
        $this->serializationContextBuilder = $serializationContextBuilder;
    }

    /**
     * @param UpdatePet[] $updatePets
     */
    protected function invoke(
        Request $request,
        array $updatePets,
        string $responseSerializationSchemaObject,
    ): JsonResponse {
        $serializationContext = $this->serializationContextBuilder->getContextForSchemaObject(
            $responseSerializationSchemaObject,
            $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE)[RouteContext::RESOURCE]
        );

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($updatePets, 'json', $serializationContext)
        );
    }
}

if (PHP_VERSION_ID >= 80000) {
    class UpdatePetsController extends AbstractUpdatePetsController
    {
        public function __invoke(
            Request $request,
            #[DeserializedObject] array $updatePets,
            string $responseSerializationSchemaObject,
        ): JsonResponse {
            return $this->invoke($request, $updatePets, $responseSerializationSchemaObject);
        }
    }
} else {
    class UpdatePetsController extends AbstractUpdatePetsController
    {
        /**
         * @param UpdatePet[] $updatePets
         */
        public function __invoke(
            Request $request,
            array $updatePets,
            string $responseSerializationSchemaObject,
        ): JsonResponse {
            return $this->invoke($request, $updatePets, $responseSerializationSchemaObject);
        }
    }
}
