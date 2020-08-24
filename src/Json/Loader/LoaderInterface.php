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

use stdClass;

/**
 * Defines the implementation for loading and decoding a JSON schema.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface LoaderInterface
{
    /**
     * Returns true if the file is supported by the loader.
     */
    public function supports(string $file): bool;

    /**
     * Loads the file and returns the JSON schema as decoded JSON.
     *
     * @throws LoaderLoadException when the file cannot be loaded
     */
    public function load(string $file): stdClass;
}
