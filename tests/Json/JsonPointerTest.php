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

namespace Nijens\OpenapiBundle\Tests\Json;

use Nijens\OpenapiBundle\Json\Exception\InvalidJsonPointerException;
use Nijens\OpenapiBundle\Json\JsonPointer;
use PHPUnit\Framework\TestCase;

/**
 * Tests the {@see JsonPointer}.
 */
class JsonPointerTest extends TestCase
{
    /**
     * @var JsonPointer
     */
    private $jsonPointer;

    /**
     * Creates a new {@see JsonPointer} instance for testing.
     */
    protected function setUp(): void
    {
        $this->jsonPointer = new JsonPointer(
            json_decode(file_get_contents(__DIR__.'/../Resources/pointer.json'))
        );
    }

    /**
     * Tests if {@see JsonPointer::has} returns the expected boolean for the JSON pointer.
     *
     * @dataProvider provideHasTestCases
     */
    public function testHas(string $pointer, bool $exists): void
    {
        $this->assertSame($exists, $this->jsonPointer->has($pointer));
    }

    /**
     * Returns test cases for {@see JsonPointer::has}.
     */
    public function provideHasTestCases(): array
    {
        return [
            ['/array', true],
            ['/array/0', true],
            ['/array/1', true],
            ['/array-with-objects/0', true],
            ['/array-with-objects/1/object', true],
            ['/with|pipe', true],
            ['#/with~1slash', true],
            ['#/with~0curly', true],
            ['#/really/0/deep/nesting/0', true],
            ['#/does-not-exist', false],
        ];
    }

    /**
     * Tests if {@see JsonPointer::get} returns the expected JSON for the JSON pointer.
     *
     * @dataProvider provideGetTestCases
     *
     * @param mixed $expectedJson
     */
    public function testGet(string $pointer, $expectedJson): void
    {
        $json = $this->jsonPointer->get($pointer);

        self::assertJsonStringEqualsJsonString(json_encode($expectedJson), json_encode($json));
    }

    /**
     * Returns test cases for {@see JsonPointer::get}.
     */
    public function provideGetTestCases(): array
    {
        return [
            ['/array', ['bar', 'baz']],
            ['/array/0', 'bar'],
            ['/array/1', 'baz'],
            ['/array-with-objects/0', ['object' => 1]],
            ['/array-with-objects/1/object', 2],
            ['/with|pipe', 3],
            ['#/with~1slash', 4],
            ['#/with~0curly', 5],
            ['#/really/0/deep/nesting/0', 'of data'],
        ];
    }

    /**
     * Tests if {@see JsonPointer::get} throws an {@see InvalidJsonPointerException} when the JSON pointer
     * does not exist within the JSON.
     */
    public function testGetThrowsInvalidJsonPointerException(): void
    {
        $this->expectException(InvalidJsonPointerException::class);
        $this->expectExceptionMessage('The JSON pointer "/does-not-exist" does not exist.');

        $this->jsonPointer->get('/does-not-exist');
    }

    /**
     * Tests if {@see JsonPointer::escape} escapes the ~ and / characters.
     *
     * @dataProvider provideEscapeTestCases
     */
    public function testEscape(string $expectedResult, string $value): void
    {
        $this->assertSame($expectedResult, $this->jsonPointer->escape($value));
    }

    /**
     * Returns a list with test cases for {@see testEscape}.
     */
    public function provideEscapeTestCases(): array
    {
        return [
            ['application~1json', 'application/json'],
            ['~0~1some-home-directory', '~/some-home-directory'],
        ];
    }
}
