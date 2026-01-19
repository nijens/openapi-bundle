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

use Nijens\OpenapiBundle\Routing\RouteContext;
use Nijens\OpenapiBundle\Serialization\SerializationContextBuilderInterface;
use Nijens\OpenapiBundle\Tests\Functional\App\Model\UpdatePet;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class UpdatePetController
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

    public function __invoke(
        Request $request,
        string $petId,
        ?UpdatePet $pet,
        string $responseSerializationSchemaObject,
    ): JsonResponse {
        if ($pet instanceof UpdatePet === false) {
            $pet = new UpdatePet('Cat');
        }
        $pet->setId((int) $petId);

        $serializationContext = $this->serializationContextBuilder->getContextForSchemaObject(
            $responseSerializationSchemaObject,
            $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE)[RouteContext::RESOURCE]
        );

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($pet, 'json', $serializationContext)
        );
    }
}
