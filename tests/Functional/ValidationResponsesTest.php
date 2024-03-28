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

use stdClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional testing of validation responses.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class ValidationResponsesTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * Creates a new test client.
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Tests if sending a request body with invalid JSON syntax returns a '400 Bad Request' response.
     */
    public function testInvalidJsonSyntaxRequestBodyReturnsBadRequestResponse(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/api/pets',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            ''
        );

        $expectedJsonResponseBody = [
            'type' => 'about:blank',
            'title' => 'The request body contains errors.',
            'status' => Response::HTTP_BAD_REQUEST,
            'detail' => 'The request body should be valid JSON.',
            'violations' => [
                [
                    'constraint' => 'valid_json',
                    'message' => "Parse error on line 1:\n\n^\nExpected one of: 'STRING', 'NUMBER', 'NULL', 'TRUE', 'FALSE', '{', '['",
                ],
            ],
        ];

        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $response->getContent()
        );
    }

    /**
     * Tests if sending a request body with invalid JSON according to the OpenAPI specification
     * returns a '400 Bad Request' response.
     */
    public function testInvalidRequestBodyReturnsBadRequestResponse(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/api/pets',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            '{}'
        );

        $expectedJsonResponseBody = [
            'type' => 'about:blank',
            'title' => 'The request body contains errors.',
            'status' => Response::HTTP_BAD_REQUEST,
            'detail' => 'Validation of JSON request body failed.',
            'violations' => [
                [
                    'constraint' => 'required',
                    'property' => 'name',
                    'message' => 'The property name is required',
                ],
                [
                    'constraint' => 'required',
                    'property' => 'photoUrls',
                    'message' => 'The property photoUrls is required',
                ],
            ],
        ];

        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $response->getContent()
        );
    }

    public function testInvalidNestedRequestBodyReturnsBadRequestResponse(): void
    {
        $jsonRequestBody = [
            'name' => 'Cat',
            'photoUrls' => [
                'https://example.com/photos/cat.jpg',
            ],
            'category' => new stdClass(),
        ];

        $this->client->request(
            Request::METHOD_POST,
            '/api/pets',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($jsonRequestBody)
        );

        $expectedJsonResponseBody = [
            'type' => 'about:blank',
            'title' => 'The request body contains errors.',
            'status' => Response::HTTP_BAD_REQUEST,
            'detail' => 'Validation of JSON request body failed.',
            'violations' => [
                [
                    'constraint' => 'required',
                    'property' => 'category.name',
                    'message' => 'The property name is required',
                ],
            ],
        ];

        static::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        static::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * Tests if sending a request body with valid JSON according to the OpenAPI specification
     * returns a '201 Created' response.
     */
    public function testValidRequestBodyReturnsSuccessResponse(): void
    {
        $jsonRequestBody = [
            'name' => 'Cat',
            'photoUrls' => [
                'https://example.com/photos/cat.jpg',
            ],
        ];

        $this->client->request(
            Request::METHOD_POST,
            '/api/pets',
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

        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $this->client->getResponse()->getContent()
        );
    }
}
