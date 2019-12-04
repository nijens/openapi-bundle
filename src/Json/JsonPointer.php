<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Json;

use League\JsonReference\Pointer;
use League\JsonReference\Reference;
use stdClass;

/**
 * JSON Pointer wrapper.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class JsonPointer
{
    /**
     * @var Pointer
     */
    private $pointer;

    /**
     * @var array
     */
    private $escapeCharacters = [
        '~' => '~0',
        '/' => '~1',
    ];

    /**
     * Constructs a new JsonPointer instance.
     */
    public function __construct(stdClass $json)
    {
        $this->pointer = new Pointer($json);
    }

    /**
     * Returns the JSON found by the pointer.
     *
     * @return mixed
     *
     * @throws InvalidPointerException when the JSON pointer does not exist
     */
    public function get(string $pointer)
    {
        $json = $this->pointer->get($pointer);
        if ($json instanceof Reference) {
            $json = $json->resolve();
        }

        return $json;
    }

    /**
     * Escapes the ~ and / characters within the value for use within a JSON pointer.
     */
    public function escape(string $value): string
    {
        return str_replace(
            array_keys($this->escapeCharacters),
            array_values($this->escapeCharacters),
            $value
        );
    }
}
