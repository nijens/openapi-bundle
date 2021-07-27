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

class SerializationTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testCanSerializeObjectWithOpenApiSchemaObject(): void
    {
        $this->client->request(Request::METHOD_GET, '/api/pet/1');

        static::assertResponseIsSuccessful();

        $responseBody = $this->client->getResponse()->getContent();

        static::assertJson($responseBody);
        static::assertJsonStringEqualsJsonString(
            '{"id":1,"name":"Cat","photoUrls":[],"status":"available"}',
            $responseBody
        );
    }
}
