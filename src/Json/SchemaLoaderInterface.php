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

use stdClass;

/**
 * Interface for JSON schema loaders.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface SchemaLoaderInterface
{
    /**
     * Loads a dereferenced JSON schema.
     *
     * @param string $file
     *
     * @return stdClass
     */
    public function load(string $file): stdClass;
}
