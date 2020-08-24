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

use League\JsonReference\DereferencerInterface;
use Nijens\OpenapiBundle\Json\Loader\LoaderInterface;
use stdClass;
use Symfony\Component\Config\FileLocatorInterface;
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
     * @var FileLocatorInterface
     */
    private $fileLocator;

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
    public function __construct(
        FileLocatorInterface $fileLocator,
        LoaderInterface $loader,
        DereferencerInterface $dereferencer
    ) {
        $this->fileLocator = $fileLocator;
        $this->loader = $loader;
        $this->dereferencer = $dereferencer;
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $file): stdClass
    {
        $locatedFile = $this->fileLocator->locate($file);

        if (isset($this->schemas[$locatedFile]) === false) {
            $schema = $this->loader->load($locatedFile);

            $schema = $this->dereferencer->dereference($schema);
            $dereferencedSchema = json_decode(json_encode($schema));

            $this->schemas[$locatedFile] = $dereferencedSchema;
        }

        return $this->schemas[$locatedFile];
    }

    /**
     * {@inheritdoc}
     */
    public function getFileResource(string $file): ?ResourceInterface
    {
        $locatedFile = $this->fileLocator->locate($file);
        if (isset($this->schemas[$locatedFile])) {
            return new FileResource($locatedFile);
        }

        return null;
    }
}
