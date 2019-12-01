<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Tests\Json;

use Nijens\OpenapiBundle\Json\JsonPointer;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * JsonPointerTest.
 */
class JsonPointerTest extends TestCase
{
    /**
     * @var JsonPointer
     */
    private $jsonPointer;

    /**
     * Creates a new JsonPointer instance for testing.
     */
    protected function setUp()
    {
        $this->jsonPointer = new JsonPointer(new stdClass());
    }

    /**
     * Tests if JsonPointer::escape escapes the ~ and / characters.
     *
     * @dataProvider provideEscapeTestCases
     */
    public function testEscape(string $expectedResult, string $value)
    {
        $this->assertSame($expectedResult, $this->jsonPointer->escape($value));
    }

    /**
     * Returns a list with test cases for @see testEscape.
     */
    public function provideEscapeTestCases(): array
    {
        return [
            ['application~1json', 'application/json'],
            ['~0~1some-home-directory', '~/some-home-directory'],
        ];
    }
}
