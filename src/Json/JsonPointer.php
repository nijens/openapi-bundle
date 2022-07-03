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
 * Query a {@see stdClass} using JSON Pointer.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class JsonPointer implements JsonPointerInterface
{
    /**
     * @var array
     */
    private const ESCAPE_CHARACTERS = [
        '~' => '~0',
        '/' => '~1',
    ];

    /**
     * @var stdClass
     */
    private $json;

    /**
     * Constructs a new {@see JsonPointer} instance.
     */
    public function __construct(?stdClass $json = null)
    {
        $this->json = $json;
    }

    /**
     * {@inheritdoc}
     */
    public function withJson(stdClass $json): JsonPointerInterface
    {
        return new self($json);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $pointer): bool
    {
        try {
            $this->traverseJson($pointer);

            return true;
        } catch (InvalidJsonPointerException $exception) {
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $pointer)
    {
        return $this->traverseJson($pointer);
    }

    /**
     * {@inheritdoc}
     */
    public function &getByReference(string $pointer)
    {
        return $this->traverseJson($pointer);
    }

    /**
     * {@inheritdoc}
     */
    public function escape(string $value): string
    {
        return str_replace(
            array_keys(self::ESCAPE_CHARACTERS),
            array_values(self::ESCAPE_CHARACTERS),
            $value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function appendSegmentsToPointer(string $pointer, string ...$segments): string
    {
        $segments = array_map([$this, 'escape'], $segments);

        return $pointer.'/'.implode('/', $segments);
    }

    /**
     * Traverses through the segments of the JSON pointer and returns the result.
     *
     * @return mixed
     *
     * @throws InvalidJsonPointerException when the JSON pointer does not exist
     */
    private function &traverseJson(string $pointer)
    {
        $json = &$this->json;

        $pointerSegments = $this->splitPointerIntoSegments($pointer);
        foreach ($pointerSegments as $pointerSegment) {
            $json = &$this->resolveReference($json);

            if (is_object($json) && property_exists($json, $pointerSegment)) {
                $json = &$json->{$pointerSegment};

                continue;
            }

            if (is_array($json) && array_key_exists($pointerSegment, $json)) {
                $json = &$json[$pointerSegment];

                continue;
            }

            throw new InvalidJsonPointerException(sprintf('The JSON pointer "%s" does not exist.', $pointer));
        }

        $json = &$this->resolveReference($json);

        return $json;
    }

    /**
     * Splits the JSON pointer into segments.
     *
     * @return string[]
     */
    private function splitPointerIntoSegments(string $pointer): array
    {
        $segments = array_slice(explode('/', $pointer), 1);

        return $this->unescape($segments);
    }

    /**
     * Unescapes the JSON pointer variants of the ~ and / characters.
     *
     * @param string|string[] $value
     *
     * @return string|string[]
     */
    private function unescape($value)
    {
        return str_replace(
            array_values(self::ESCAPE_CHARACTERS),
            array_keys(self::ESCAPE_CHARACTERS),
            $value
        );
    }

    /**
     * Resolves the reference when the provided JSON is a {@see Reference} instance.
     *
     * @param mixed $json
     *
     * @return mixed
     */
    private function &resolveReference(&$json)
    {
        if ($json instanceof Reference) {
            $jsonPointer = $this->withJson($json->getJsonSchema());
            $json = &$jsonPointer->getByReference($json->getPointer());
        }

        return $json;
    }
}
