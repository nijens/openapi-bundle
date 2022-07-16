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
use Nijens\OpenapiBundle\ExceptionHandling\EventSubscriber\ProblemExceptionToJsonResponseSubscriber;
use Nijens\OpenapiBundle\ExceptionHandling\EventSubscriber\ThrowableToProblemExceptionSubscriber;
use Nijens\OpenapiBundle\ExceptionHandling\ThrowableToProblemExceptionTransformer;
use Nijens\OpenapiBundle\Routing\RouteLoader;
use Nijens\OpenapiBundle\Validation\EventSubscriber\RequestValidationSubscriber;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Kernel;

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

        $this->loadDeprecatedServices($loader);

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerRoutingConfiguration($config['routing'], $container);
        $this->registerValidationConfiguration($config['validation'], $container);
        $this->registerExceptionHandlingConfiguration($config['exception_handling'], $container);
    }

    /**
     * Loads the deprecated services file with backwards compatibility for XML Schema.
     */
    private function loadDeprecatedServices(XmlFileLoader $loader): void
    {
        $deprecatedServicesFileSuffix = '';
        if ($this->getSymfonyVersion() >= 50100) {
            $deprecatedServicesFileSuffix = '_5.1';
        }
        $loader->load(sprintf('services_deprecated%s.xml', $deprecatedServicesFileSuffix));
    }

    private function registerRoutingConfiguration(array $config, ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(RouteLoader::class);
        $definition->replaceArgument(2, $config['operation_id_as_route_name']);
    }

    private function registerValidationConfiguration(array $config, ContainerBuilder $container): void
    {
        if ($config['enabled'] !== true) {
            $container->removeDefinition(RequestValidationSubscriber::class);
        }

        if ($config['enabled'] !== null) {
            $container->removeDefinition('nijens_openapi.event_subscriber.json_request_body_validation');
        }
    }

    private function registerExceptionHandlingConfiguration(array $config, ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(ThrowableToProblemExceptionTransformer::class);
        $definition->replaceArgument(
            0,
            array_replace_recursive(Configuration::DEFAULT_EXCEPTION_HANDLING_EXCEPTIONS, $config['exceptions'])
        );

        if ($config['enabled'] !== true) {
            $container->removeDefinition(ThrowableToProblemExceptionSubscriber::class);
            $container->removeDefinition(ProblemExceptionToJsonResponseSubscriber::class);
        }

        if ($config['enabled'] !== null) {
            $container->removeDefinition(JsonResponseExceptionSubscriber::class);
            $container->removeDefinition('nijens_openapi.service.exception_json_response_builder');
        }
    }

    private function getSymfonyVersion(): int
    {
        $kernel = new class('symfony_version', false) extends Kernel {
            public function registerBundles(): iterable
            {
                return [];
            }

            public function registerContainerConfiguration(LoaderInterface $loader)
            {
            }
        };

        return $kernel::VERSION_ID;
    }
}
