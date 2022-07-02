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

use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidContentTypeProblemException;
use Nijens\OpenapiBundle\ExceptionHandling\Exception\InvalidRequestBodyProblemException;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates and merges configuration from the configuration files.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    public const BUNDLE_NAME = 'nijens/openapi-bundle';

    public const DEFAULT_EXCEPTION_HANDLING_EXCEPTIONS = [
        InvalidContentTypeProblemException::class => [
            'status_code' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
            'title' => 'The content type is not supported.',
        ],
        InvalidRequestBodyProblemException::class => [
            'status_code' => Response::HTTP_BAD_REQUEST,
            'title' => 'The request body contains errors.',
        ],
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nijens_openapi');
        $rootNode = $treeBuilder->getRootNode();

        $this->addExceptionsSection($rootNode);

        return $treeBuilder;
    }

    private function addExceptionsSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode->children()
            ->arrayNode('exception_handling')
                ->treatTrueLike(['enabled' => true])
                ->treatFalseLike(['enabled' => false])
                ->treatNullLike(['enabled' => null])
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enabled')
                        ->info(
                            'Set to true to enable the new serialization-based exception handling.'.PHP_EOL.
                            'Set to false to disable exception handling provided by this bundle.'.PHP_EOL.
                            'Set to null to keep using the deprecated exception JSON response builder.'
                        )
                        ->defaultNull()
                        ->validate()
                            ->ifNull()
                            ->then(function ($value) {
                                trigger_deprecation(
                                    self::BUNDLE_NAME,
                                    '1.3',
                                    'Setting the "nijens_openapi.exceptions.enabled" option to "null" is deprecated. It will default to "true" as of version 2.0.'
                                );

                                return $value;
                            })
                            ->end()
                        ->end()
                    ->arrayNode('exceptions')
                        ->useAttributeAsKey('class')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('class')
                                    ->info('The fully qualified class name of the exception.')
                                    ->cannotBeEmpty()
                                    ->end()
                                ->integerNode('status_code')
                                    ->info('The HTTP status code that must be sent when this exception occurs.')
                                    ->isRequired()
                                    ->min(100)
                                    ->max(999)
                                    ->end()
                                ->scalarNode('type_uri')
                                    ->info('The RFC 7807 URI reference that identifies the problem type. It will be sent with the response.')
                                    ->cannotBeEmpty()
                                    ->defaultValue('about:blank')
                                    ->end()
                                ->scalarNode('title')
                                    ->info('The RFC 7807 title that summarizes the problem type in human-readable language. It will be sent with the response.')
                                    ->cannotBeEmpty()
                                    ->defaultValue('An error occurred.')
                                    ->end()
                                ->booleanNode('add_instance_uri')
                                    ->defaultFalse()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
