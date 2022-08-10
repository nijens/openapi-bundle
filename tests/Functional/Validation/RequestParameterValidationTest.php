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
 * Functional testing of validation and responses for parameter validation created by the exception handling
 * and validation components.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class RequestParameterValidationTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient(['environment' => 'exception_handling_and_validation']);
    }

    public function testCanReturnSuccessResponseForValidParameter(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/pets',
            [
                'filterByName' => 'Dog',
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testCanReturnBadRequestResponseForInvalidParameter(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/pets',
            [
                'itemsPerPage' => 'abc',
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        static::assertJsonStringEqualsJsonString(
            json_encode([
                'type' => 'about:blank',
                'title' => 'The request contains errors.',
                'status' => Response::HTTP_BAD_REQUEST,
                'detail' => 'Validation of query parameters failed.',
                'violations' => [
                    [
                        'constraint' => 'type',
                        'message' => 'String value found, but an integer is required',
                        'property' => 'itemsPerPage',
                    ],
                ],
            ]),
            $this->client->getResponse()->getContent()
        );
    }
}
