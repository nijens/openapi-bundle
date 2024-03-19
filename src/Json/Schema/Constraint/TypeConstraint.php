<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Json\Schema\Constraint;

use JsonSchema\Constraints\TypeConstraint as BaseTypeConstraint;
use JsonSchema\Entity\JsonPointer;

/**
 * Extends the type check with the nullable property from the OpenAPI specification.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class TypeConstraint extends BaseTypeConstraint
{
    /**
     * {@inheritdoc}
     */
    public function check(&$value = null, $schema = null, ?JsonPointer $path = null, $i = null): void
    {
        $type = $schema->type ?? null;
        $nullable = $schema->nullable ?? false;

        if (is_array($type) === false) {
            $type = [$type];
        }

        if ($nullable === true) {
            $type[] = 'null';

            $schema->type = $type;
        }

        parent::check($value, $schema, $path, $i);
    }
}
