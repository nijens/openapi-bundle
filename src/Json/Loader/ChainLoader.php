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
 * This loader calls several loaders in a chain until one loader is able to load the file.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
final class ChainLoader implements LoaderInterface
{
    /**
     * @var LoaderInterface[]
     */
    private $loaders;

    /**
     * Constructs a new {@see ChainLoader} instance.
     *
     * @param LoaderInterface[] $loaders
     */
    public function __construct(iterable $loaders = [])
    {
        $this->loaders = $loaders;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $file): bool
    {
        foreach ($this->loaders as $loader) {
            if ($loader->supports($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $file): stdClass
    {
        foreach ($this->loaders as $loader) {
            if ($loader->supports($file)) {
                return $loader->load($file);
            }
        }

        throw new LoaderLoadException(sprintf('No loader available to load "%s".', $file));
    }
}
