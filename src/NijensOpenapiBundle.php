<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Serializer\DependencyInjection\SerializerPass;

/**
 * OpenapiBundle.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class NijensOpenapiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(
            new SerializerPass('nijens_openapi.serializer', 'nijens_openapi.serializer.normalizer')
        );
    }

    public static function getSymfonyVersion(): int
    {
        $kernel = new class('symfony_version', false) extends Kernel {
            public function registerBundles(): iterable
            {
                return [];
            }

            public function registerContainerConfiguration(LoaderInterface $loader): void
            {
            }
        };

        return $kernel::VERSION_ID;
    }
}
