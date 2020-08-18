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

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for functional testing the successful validation test cases.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class PetController
{
    /**
     * Handles POST /api/pet.
     */
    public function create(): JsonResponse
    {
        $data = [
            'id' => 1,
            'name' => 'Cat',
            'status' => 'available',
            'photoUrls' => [
                'https://example.com/photos/cat.jpg',
            ],
        ];

        return new JsonResponse($data, JsonResponse::HTTP_CREATED);
    }
}
