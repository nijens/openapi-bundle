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
use Nijens\OpenapiBundle\Tests\Functional\App\Model\Pet;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Controller for functional testing the validation of query parameters from the OpenAPI specification.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class GetPetsController
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
     * Handles GET /api/pets.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $filterByName = $request->query->get('filterByName', '');
        $pets = [
            new Pet(1, 'Cat'),
            new Pet(2, 'Dog'),
            new Pet(3, 'Parrot'),
        ];

        $filteredPets = array_filter(
            $pets,
            function (Pet $pet) use ($filterByName): bool {
                return strpos($pet->getName(), $filterByName) !== false;
            }
        );

        $serializationContext = $this->serializationContextBuilder->getContextForSchemaObject(
            'Pet',
            $request->attributes->get(RouteContext::REQUEST_ATTRIBUTE)[RouteContext::RESOURCE]
        );

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($filteredPets, 'json', $serializationContext)
        );
    }
}
