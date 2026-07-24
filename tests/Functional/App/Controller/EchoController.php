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
