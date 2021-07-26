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

namespace Nijens\OpenapiBundle\Tests\Json;

use Nijens\OpenapiBundle\Json\SchemaLoaderInterface;
use stdClass;
use Symfony\Component\Config\Resource\ResourceInterface;

class SchemaLoaderMock implements SchemaLoaderInterface
{
    private $schema;

    public function setSchema(stdClass $schema): void
    {
        $this->schema = $schema;
    }

    public function load(string $file): stdClass
    {
        return $this->schema;
    }

    public function getFileResource(string $file): ?ResourceInterface
    {
        return null;
    }
}
