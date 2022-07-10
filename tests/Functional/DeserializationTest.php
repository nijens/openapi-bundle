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

namespace Nijens\OpenapiBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeserializationTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCanDeserializeRequestBodyIntoObject(): void
    {
        $jsonRequestBody = [
            'name' => 'Cat',
            'photoUrls' => [
                'https://example.com/photos/cat.jpg',
            ],
        ];

        $this->client->request(
            Request::METHOD_POST,
            '/api/pets/1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($jsonRequestBody)
        );

        $expectedJsonResponseBody = [
            'id' => 1,
            'name' => 'Cat',
            'status' => 'available',
            'photoUrls' => [
                'https://example.com/photos/cat.jpg',
            ],
        ];

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $this->client->getResponse()->getContent()
        );
    }

    public function testCanDeserializeRequestBodyIntoArrayOfObjects(): void
    {
        $jsonRequestBody = [
            [
                'id' => 1,
                'name' => 'Cat',
                'photoUrls' => [
                    'https://example.com/photos/cat.jpg',
                ],
            ],
            [
                'id' => 2,
                'name' => 'Dog',
                'photoUrls' => [
                    'https://example.com/photos/dog.jpg',
                ],
            ],
        ];

        $this->client->request(
            Request::METHOD_PATCH,
            '/api/pets',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($jsonRequestBody)
        );

        $expectedJsonResponseBody = [
            [
                'id' => 1,
                'name' => 'Cat',
                'status' => 'available',
                'photoUrls' => [
                    'https://example.com/photos/cat.jpg',
                ],
            ],
            [
                'id' => 2,
                'name' => 'Dog',
                'status' => 'available',
                'photoUrls' => [
                    'https://example.com/photos/dog.jpg',
                ],
            ],
        ];

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $this->client->getResponse()->getContent()
        );
    }
}
