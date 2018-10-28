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
use stdClass;
use Symfony\Component\Config\FileLocatorInterface;

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
     * @var DereferencerInterface
     */
    private $dereferencer;

    /**
     * The loaded JSON schemas.
     *
     * @var array
     */
    private $schemas = array();

    /**
     * Constructs a new SchemaLoader instance.
     *
     * @param FileLocatorInterface  $fileLocator
     * @param DereferencerInterface $dereferencer
     */
    public function __construct(FileLocatorInterface $fileLocator, DereferencerInterface $dereferencer)
    {
        $this->fileLocator = $fileLocator;
        $this->dereferencer = $dereferencer;
    }

    /**
     * @param string $file
     *
     * @return stdClass
     */
    public function load(string $file): stdClass
    {
        $locatedFile = $this->fileLocator->locate($file);

        if (isset($this->schemas[$locatedFile]) === false) {
            $schema = $this->dereferencer->dereference('file://'.$locatedFile);
            $dereferencedSchema = json_decode(json_encode($schema));

            $this->schemas[$locatedFile] = $dereferencedSchema;
        }

        return $this->schemas[$locatedFile];
    }
}
