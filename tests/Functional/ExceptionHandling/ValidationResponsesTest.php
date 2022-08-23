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

namespace Nijens\OpenapiBundle\Tests\Functional\ExceptionHandling;

use stdClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional testing of validation responses created by the exception handling feature.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class ValidationResponsesTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient(['environment' => 'exception_handling']);
    }

    public function testCanReturnProblemJsonObjectForInvalidJsonSyntaxRequestBody(): void
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
            'status' => 400,
            'detail' => 'The request body should be valid JSON.',
            'violations' => [
                [
                    'constraint' => 'valid_json',
                    'message' => "Parse error on line 1:\n\n^\nExpected one of: 'STRING', 'NUMBER', 'NULL', 'TRUE', 'FALSE', '{', '['",
                ],
            ],
        ];

        static::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        static::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $this->client->getResponse()->getContent()
        );
    }

    public function testCanReturnProblemJsonObjectForInvalidRequestBody(): void
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
            'status' => 400,
            'detail' => 'Validation of JSON request body failed.',
            'violations' => [
                [
                    'constraint' => 'required',
                    'message' => 'The property name is required',
                    'property' => 'name',
                ],
                [
                    'constraint' => 'required',
                    'message' => 'The property photoUrls is required',
                    'property' => 'photoUrls',
                ],
            ],
        ];

        static::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        static::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $this->client->getResponse()->getContent()
        );
    }

    public function testCanReturnProblemJsonObjectForInvalidRequestBodyWithNestedObjects(): void
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
            'status' => 400,
            'detail' => 'Validation of JSON request body failed.',
            'violations' => [
                [
                    'constraint' => 'required',
                    'message' => 'The property name is required',
                    'property' => 'category.name',
                ],
            ],
        ];

        static::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        static::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $this->client->getResponse()->getContent()
        );
    }
}
