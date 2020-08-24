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
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads JSON schema files in YAML format.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
final class YamlLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $file): bool
    {
        return in_array(pathinfo($file, PATHINFO_EXTENSION), ['yaml', 'yml']);
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $file): stdClass
    {
        try {
            return Yaml::parseFile($file, Yaml::PARSE_OBJECT_FOR_MAP | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
        } catch (ParseException $exception) {
            throw new LoaderLoadException($exception->getMessage(), 0, $exception);
        }
    }
}
