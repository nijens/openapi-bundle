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
 * JSON reference container.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class Reference
{
    /**
     * @var string
     */
    private $pointer;

    /**
     * @var stdClass
     */
    private $jsonSchema;

    /**
     * Constructs a new {@see Reference} instance.
     */
    public function __construct(string $pointer, stdClass $jsonSchema)
    {
        $this->pointer = $pointer;
        $this->jsonSchema = $jsonSchema;
    }

    /**
     * Returns the JSON pointer of this reference.
     */
    public function getPointer(): string
    {
        return $this->pointer;
    }

    /**
     * Returns the (external) JSON schema being referenced.
     */
    public function getJsonSchema(): stdClass
    {
        return $this->jsonSchema;
    }
}
