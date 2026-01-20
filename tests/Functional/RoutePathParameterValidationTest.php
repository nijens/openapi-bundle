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

class RoutePathParameterValidationTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * @dataProvider provideTestCases
     */
    public function testCanValidateString(string $path, string $parameter, int $expectedStatusCode): void
    {
        $this->client->request(Request::METHOD_GET, sprintf($path, $parameter));

        static::assertResponseStatusCodeSame($expectedStatusCode);
    }

    public static function provideTestCases(): array
    {
        return [
            ['/api/validate-path/boolean/%s', 'true', Response::HTTP_OK],
            ['/api/validate-path/boolean/%s', 'false', Response::HTTP_OK],
            ['/api/validate-path/boolean/%s', 'no', Response::HTTP_NOT_FOUND],
            ['/api/validate-path/integer/%s', '1', Response::HTTP_OK],
            ['/api/validate-path/integer/%s', '1.0', Response::HTTP_NOT_FOUND],
            ['/api/validate-path/integer/%s', 'abc', Response::HTTP_NOT_FOUND],
            ['/api/validate-path/number/%s', '1', Response::HTTP_OK],
            ['/api/validate-path/number/%s', '1.0', Response::HTTP_OK],
            ['/api/validate-path/number/%s', 'abc', Response::HTTP_NOT_FOUND],
            ['/api/validate-path/string/date/%s', '2026-01-19', Response::HTTP_OK],
            ['/api/validate-path/string/date/%s', 'abc', Response::HTTP_NOT_FOUND],
            ['/api/validate-path/string/date-time/%s', '2026-01-19T12:34:56Z', Response::HTTP_OK],
            ['/api/validate-path/string/date-time/%s', '1', Response::HTTP_NOT_FOUND],
            ['/api/validate-path/string/date-time/%s', 'abc', Response::HTTP_NOT_FOUND],
            ['/api/validate-path/string/email/%s', 'john@doe.com', Response::HTTP_OK],
            ['/api/validate-path/string/email/%s', 'abc', Response::HTTP_NOT_FOUND],
            ['/api/validate-path/string/uuid/%s', '5f89d86d-4ead-4bc6-ba3e-61726d22fe13', Response::HTTP_OK],
            ['/api/validate-path/string/uuid/%s', '1', Response::HTTP_NOT_FOUND],
            ['/api/validate-path/string/uuid/%s', 'abc', Response::HTTP_NOT_FOUND],
            ['/api/validate-path/string/pattern/%s', 'NL', Response::HTTP_OK],
            ['/api/validate-path/string/pattern/%s', 'ab', Response::HTTP_NOT_FOUND],
            ['/api/validate-path/string/pattern/%s', 'abc', Response::HTTP_NOT_FOUND],
            ['/api/validate-path/string/%s', '1', Response::HTTP_OK],
            ['/api/validate-path/string/%s', 'abc', Response::HTTP_OK],
            ['/api/validate-path/string/%s', 'abc/abc', Response::HTTP_NOT_FOUND],
        ];
    }
}
