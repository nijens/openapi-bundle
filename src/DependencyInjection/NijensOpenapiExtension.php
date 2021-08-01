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

namespace Nijens\OpenapiBundle\DependencyInjection;

use Nijens\OpenapiBundle\EventListener\JsonResponseExceptionSubscriber;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Loads and manages the bundle configuration and services.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class NijensOpenapiExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerExceptionHandlingConfiguration($config, $container);
    }

    private function registerExceptionHandlingConfiguration(array $config, ContainerBuilder $container): void
    {
        if ($config['enabled'] === false) {
            $container->removeDefinition(JsonResponseExceptionSubscriber::class);
            $container->removeDefinition('nijens_openapi.service.exception_json_response_builder');

            return;
        }
    }
}
