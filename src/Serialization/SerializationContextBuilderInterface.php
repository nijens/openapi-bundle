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

namespace Nijens\OpenapiBundle\Serialization;

/**
 * Interface for creating a serialization context for {@see SerializerInterface::serialize}.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
interface SerializationContextBuilderInterface
{
    public function getContextForSchemaObject(string $schemaObjectName, string $openApiSpecificationFile): array;
}
