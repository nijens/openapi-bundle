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
    private $escapeCharacters = array(
        '~' => '~0',
        '/' => '~1',
    );

    /**
     * Constructs a new JsonPointer instance.
     *
     * @param stdClass $json
     */
    public function __construct(stdClass $json)
    {
        $this->pointer = new Pointer($json);
    }

    /**
     * Returns the JSON found by the pointer.
     *
     * @param string $pointer
     *
     * @return mixed
     *
     * @throws InvalidPointerException when the JSON pointer does not exist
     */
    public function get(string $pointer)
    {
        return $this->pointer->get($pointer);
    }

    /**
     * Escapes the ~ and / characters within the value for use within a JSON pointer.
     *
     * @param string $value
     *
     * @return string
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
