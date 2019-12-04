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
use Symfony\Component\Config\Exception\FileLoaderLoadException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Yaml\Yaml;

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
    private $schemas = [];

    /**
     * Constructs a new SchemaLoader instance.
     */
    public function __construct(FileLocatorInterface $fileLocator, DereferencerInterface $dereferencer)
    {
        $this->fileLocator = $fileLocator;
        $this->dereferencer = $dereferencer;
    }

    /**
     * {@inheritdoc}
     *
     * @throws FileLoaderLoadException when given file does not have a valid JSON or YAML extension
     */
    public function load(string $file): stdClass
    {
        $locatedFile = $this->fileLocator->locate($file);

        if (isset($this->schemas[$locatedFile]) === false) {
            switch (pathinfo($locatedFile, PATHINFO_EXTENSION)) {
                case 'yml':
                case 'yaml':
                    $dereference = Yaml::parseFile($locatedFile, Yaml::PARSE_OBJECT_FOR_MAP);
                    break;

                case 'json':
                    $dereference = "file://{$locatedFile}";
                    break;

                default:
                    throw new FileLoaderLoadException($locatedFile);
            }

            $schema = $this->dereferencer->dereference($dereference);
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
