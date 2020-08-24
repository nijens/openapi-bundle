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

namespace Nijens\OpenapiBundle\Json\Loader;

use Nijens\OpenapiBundle\Json\Exception\LoaderLoadException;
use stdClass;

/**
 * Loads JSON schema files in JSON format.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
final class JsonLoader implements LoaderInterface
{
    /**
     * The options used for decoding the JSON.
     */
    private const DECODE_OPTIONS = JSON_BIGINT_AS_STRING;

    /**
     * {@inheritdoc}
     */
    public function supports(string $file): bool
    {
        return pathinfo($file, PATHINFO_EXTENSION) === 'json';
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $file): stdClass
    {
        if (file_exists($file) === false) {
            throw new LoaderLoadException(sprintf('The JSON schema "%s" could not be found.', $file));
        }

        $json = json_decode(file_get_contents($file), false, 512, self::DECODE_OPTIONS);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new LoaderLoadException(sprintf('The JSON schema "%s" contains invalid JSON: %s', $file, json_last_error_msg()));
        }

        return $json;
    }
}
