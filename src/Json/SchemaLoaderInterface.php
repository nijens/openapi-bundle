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
use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * Interface for JSON schema loaders.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface SchemaLoaderInterface
{
    /**
     * Loads and dereferences a JSON schema.
     *
     * @param string $file
     *
     * @return stdClass
     */
    public function load(string $file): stdClass;

    /**
     * Returns a FileResource for a loaded JSON schema.
     *
     * @param string $file
     *
     * @return ResourceInterface|null
     */
    public function getFileResource(string $file): ?ResourceInterface;
}
