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

use Nijens\OpenapiBundle\Tests\Functional\App\Model\Pet;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Controller for functional testing the successful serialization of an object based on a schema object of
 * the OpenAPI specification.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class GetPetController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $pet = new Pet(1, 'Cat');

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($pet, 'json')
        );
    }
}
