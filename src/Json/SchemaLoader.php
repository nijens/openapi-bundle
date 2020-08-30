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

use Nijens\OpenapiBundle\Json\Loader\LoaderInterface;
use stdClass;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * Loads a dereferenced JSON schema.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class SchemaLoader implements SchemaLoaderInterface
{
    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var DereferencerInterface
     */
    private $dereferencer;

    /**
     * The loaded JSON schemas.
     *
     * @var array
     */
    private $schemas = [];

    /**
     * Constructs a new {@see SchemaLoader} instance.
     */
    public function __construct(LoaderInterface $loader, DereferencerInterface $dereferencer)
    {
        $this->loader = $loader;
        $this->dereferencer = $dereferencer;
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $file): stdClass
    {
        if (isset($this->schemas[$file]) === false) {
            $schema = $this->loader->load($file);
            $dereferencedSchema = $this->dereferencer->dereference($schema);

            $this->schemas[$file] = $dereferencedSchema;
        }

        return $this->schemas[$file];
    }

    /**
     * {@inheritdoc}
     */
    public function getFileResource(string $file): ?ResourceInterface
    {
        if (isset($this->schemas[$file])) {
            return new FileResource($file);
        }

        return null;
    }
}
