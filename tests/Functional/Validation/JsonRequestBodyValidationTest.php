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

namespace Nijens\OpenapiBundle\Tests\Functional\Validation;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional testing of validation and responses for JSON request body created by the exception handling
 * and validation components.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class JsonRequestBodyValidationTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient(['environment' => 'exception_handling_and_validation']);
    }

    public function testCanReturnProblemDetailsJsonObjectForInvalidJsonSyntaxRequestBody(): void
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
            'detail' => 'The request body must be valid JSON.',
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

    public function testCanReturnProblemDetailsJsonObjectForInvalidRequestBody(): void
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

    public function testCannotReturnProblemDetailsJsonObjectWithoutRequiredRequestBody(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/api/pets/1',
            [],
            [],
            [
                'CONTENT_TYPE' => '',
            ]
        );

        $expectedJsonResponseBody = [
            'id' => 1,
            'name' => 'Cat',
            'status' => 'available',
            'photoUrls' => [],
        ];

        static::assertResponseStatusCodeSame(Response::HTTP_OK);
        static::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $this->client->getResponse()->getContent()
        );
    }

    public function testCannotReturnProblemDetailsJsonObjectWhenNotAuthenticated(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/api/authenticated/pets',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            '{}'
        );

        $expectedJsonResponseBody = [
            'type' => 'about:blank',
            'title' => 'An error occurred.',
            'status' => 401,
            'detail' => 'Full authentication is required to access this resource.',
        ];

        static::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        static::assertJsonStringEqualsJsonString(
            json_encode($expectedJsonResponseBody),
            $this->client->getResponse()->getContent()
        );
    }
}
