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

namespace Nijens\OpenapiBundle\Json;

use stdClass;

/**
 * Defines the implementation for dereferencing a JSON schema by replacing $ref JSON pointers
 * with a {@see Reference} value-object.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface DereferencerInterface
{
    /**
     * Dereferences and returns the provided JSON schema.
     */
    public function dereference(stdClass $jsonSchema): stdClass;
}
