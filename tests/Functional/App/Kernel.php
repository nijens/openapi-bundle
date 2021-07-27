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

namespace Nijens\OpenapiBundle\Tests\Functional\App;

use Nijens\OpenapiBundle\NijensOpenapiBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Micro Symfony application for functional testing of the OpenAPI bundle.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * File extensions used to load configuration files.
     */
    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return $this->getTemporaryDirectory().'/var/cache/'.$this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir(): string
    {
        return $this->getTemporaryDirectory().'/var/log';
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectDir(): string
    {
        return __DIR__;
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new NijensOpenapiBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config.yaml');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes($routes)
    {
        $routes->import(__DIR__.'/routing.yaml');
    }

    /**
     * Returns a temporary directory to store the log and cache files.
     */
    private function getTemporaryDirectory(): string
    {
        return sys_get_temp_dir().'/OpenapiBundle';
    }
}
