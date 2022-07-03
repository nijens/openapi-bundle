<?php

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
}
