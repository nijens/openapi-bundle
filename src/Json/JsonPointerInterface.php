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

use Nijens\OpenapiBundle\Json\Exception\InvalidJsonPointerException;
use stdClass;

/**
 * Defines a JSON pointer implementation.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface JsonPointerInterface
{
    /**
     * Returns a new JSON pointer instance with the provided JSON loaded.
     */
    public function withJson(stdClass $json): JsonPointerInterface;

    /**
     * Returns true if the pointer referencing JSON exists.
     */
    public function has(string $pointer): bool;

    /**
     * Returns the result found by the JSON pointer.
     *
     * @return mixed
     *
     * @throws InvalidJsonPointerException when the JSON pointer does not exist
     */
    public function get(string $pointer);

    /**
     * Returns the result found by the JSON pointer as reference.
     *
     * @return mixed
     *
     * @throws InvalidJsonPointerException when the JSON pointer does not exist
     */
    public function &getByReference(string $pointer);

    /**
     * Escapes the ~ and / characters within the value for use within a JSON pointer.
     */
    public function escape(string $value): string;

    /**
     * Appends segment(s) to the given JSON Pointer.
     *
     * @param string[] $segments
     */
    public function appendSegmentsToPointer(string $pointer, string ...$segments): string;
}
