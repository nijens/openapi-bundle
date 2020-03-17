<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Tests\Json\Schema\Constraint;

use Nijens\OpenapiBundle\Json\Schema\Constraint\TypeConstraint;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests for {@see TypeConstraint}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class TypeConstraintTest extends TestCase
{
    /**
     * @var TypeConstraint
     */
    private $constraint;

    /**
     * Creates a {@see TypeConstraint} for testing.
     */
    protected function setUp(): void
    {
        $this->constraint = new TypeConstraint();
    }

    /**
     * Tests if {@see TypeConstraint::check} returns the expected value with {@see TypeConstraint::isValid}.
     *
     * @dataProvider provideCheckTestCases
     *
     * @param mixed $value
     */
    public function testCheckNullable(string $type, bool $nullable, $value, bool $expectedIsValid): void
    {
        $schema = new stdClass();
        $schema->type = $type;
        $schema->nullable = $nullable;

        $this->constraint->check($value, $schema);

        $this->assertSame($expectedIsValid, $this->constraint->isValid());
    }

    /**
     * Returns a list with test cases for {@see testCheckNullable}.
     */
    public function provideCheckTestCases(): array
    {
        return [
            ['string', false, 'value', true],
            ['string', true, 'value', true],
            ['string', false, null, false],
            ['string', true, null, true],
        ];
    }
}
