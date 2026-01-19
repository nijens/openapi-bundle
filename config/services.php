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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Constraints\Factory;
use JsonSchema\Validator;
use Nijens\OpenapiBundle\Controller\CatchAllController;
use Nijens\OpenapiBundle\DependencyInjection\ServiceFactory;
use Nijens\OpenapiBundle\Deserialization\ArgumentResolver\DeserializedObjectArgumentResolver;
use Nijens\OpenapiBundle\Deserialization\EventSubscriber\JsonRequestBodyDeserializationSubscriber;
use Nijens\OpenapiBundle\ExceptionHandling\EventSubscriber\ProblemExceptionToJsonResponseSubscriber;
use Nijens\OpenapiBundle\ExceptionHandling\EventSubscriber\ThrowableToProblemExceptionSubscriber;
use Nijens\OpenapiBundle\ExceptionHandling\Normalizer\ProblemExceptionNormalizer;
use Nijens\OpenapiBundle\ExceptionHandling\ThrowableToProblemExceptionTransformer;
use Nijens\OpenapiBundle\ExceptionHandling\ThrowableToProblemExceptionTransformerInterface;
use Nijens\OpenapiBundle\Json\Dereferencer;
use Nijens\OpenapiBundle\Json\DereferencerInterface;
use Nijens\OpenapiBundle\Json\JsonPointer;
use Nijens\OpenapiBundle\Json\JsonPointerInterface;
use Nijens\OpenapiBundle\Json\Loader\ChainLoader;
use Nijens\OpenapiBundle\Json\Loader\JsonLoader;
use Nijens\OpenapiBundle\Json\Loader\LoaderInterface;
use Nijens\OpenapiBundle\Json\Loader\YamlLoader;
use Nijens\OpenapiBundle\Json\Schema\Constraint\TypeConstraint;
use Nijens\OpenapiBundle\Json\SchemaLoader;
use Nijens\OpenapiBundle\Routing\RouteLoader;
use Nijens\OpenapiBundle\Serialization\SerializationContextBuilder;
use Nijens\OpenapiBundle\Serialization\SerializationContextBuilderInterface;
use Nijens\OpenapiBundle\Validation\EventSubscriber\RequestValidationSubscriber;
use Nijens\OpenapiBundle\Validation\RequestValidator\CompositeRequestValidator;
use Nijens\OpenapiBundle\Validation\RequestValidator\RequestBodyValidator;
use Nijens\OpenapiBundle\Validation\RequestValidator\RequestContentTypeValidator;
use Nijens\OpenapiBundle\Validation\RequestValidator\RequestParameterValidator;
use Nijens\OpenapiBundle\Validation\RequestValidator\ValidatorInterface;
use Seld\JsonLint\JsonParser;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('nijens_openapi.controller.catch_all.class', CatchAllController::class)
        ->set('nijens_openapi.json.parser.class', JsonParser::class)
        ->set('nijens_openapi.json.schema_loader.class', SchemaLoader::class)
        ->set('nijens_openapi.json.validator.class', Validator::class)
        ->set('nijens_openapi.json.validator.factory.class', Factory::class);

    $services = $container->services();

    $services->set('nijens_openapi.controller.catch_all', '%nijens_openapi.controller.catch_all.class%')
        ->args([
            service('router'),
        ])
        ->tag('controller.service_arguments');

    $services->set(RouteLoader::class)
        ->args([
            service('file_locator'),
            service('nijens_openapi.json.schema_loader'),
            false,
        ])
        ->tag('routing.loader');

    $services->set('nijens_openapi.json.parser', '%nijens_openapi.json.parser.class%');

    $services->alias(LoaderInterface::class, ChainLoader::class);

    $services->set(ChainLoader::class)
        ->args([
            tagged_iterator('nijens_openapi.json.loader'),
        ]);

    $services->set(JsonLoader::class)
        ->tag('nijens_openapi.json.loader');

    $services->set(YamlLoader::class)
        ->tag('nijens_openapi.json.loader');

    $services->alias(JsonPointerInterface::class, JsonPointer::class);

    $services->set(JsonPointer::class);

    $services->alias(DereferencerInterface::class, Dereferencer::class);

    $services->set(Dereferencer::class)
        ->args([
            service(JsonPointer::class),
            service(LoaderInterface::class),
        ]);

    $services->set('nijens_openapi.json.schema_loader', '%nijens_openapi.json.schema_loader.class%')
        ->args([
            service(LoaderInterface::class),
            service(DereferencerInterface::class),
        ]);

    $services->set('nijens_openapi.json.validator', '%nijens_openapi.json.validator.class%')
        ->args([
            service('nijens_openapi.json.validator.factory'),
        ]);

    $services->set('nijens_openapi.json.validator.factory', '%nijens_openapi.json.validator.factory.class%')
        ->call('setConfig', [Constraint::CHECK_MODE_APPLY_DEFAULTS])
        ->call('setConstraintClass', ['type', TypeConstraint::class]);

    $services->set(JsonRequestBodyDeserializationSubscriber::class)
        ->args([
            service('nijens_openapi.serializer'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(ThrowableToProblemExceptionTransformer::class)
        ->args([
            [],
        ]);

    $services->set(DeserializedObjectArgumentResolver::class)
        ->tag('controller.argument_value_resolver', ['priority' => 100]);

    $services->set('nijens_openapi.serializer', Serializer::class)
        ->factory([ServiceFactory::class, 'createSerializer'])
        ->args([
            tagged_iterator('nijens_openapi.serializer.normalizer'),
            tagged_iterator('serializer.encoder'),
        ]);

    $services->set(ProblemExceptionNormalizer::class)
        ->args([
            '%kernel.debug%',
        ])
        ->tag('nijens_openapi.serializer.normalizer', ['priority' => 64]);

    $services->set('nijens_openapi.serializer.normalizer.json', JsonSerializableNormalizer::class)
        ->tag('nijens_openapi.serializer.normalizer', ['priority' => 32]);

    $services->set('nijens_openapi.serializer.normalizer.array', ArrayDenormalizer::class)
        ->tag('nijens_openapi.serializer.normalizer', ['priority' => 0]);

    $services->set('nijens_openapi.serializer.normalizer.object', ObjectNormalizer::class)
        ->tag('nijens_openapi.serializer.normalizer', ['priority' => 0]);

    $services->alias(ThrowableToProblemExceptionTransformerInterface::class, ThrowableToProblemExceptionTransformer::class);

    $services->set(ThrowableToProblemExceptionSubscriber::class)
        ->args([
            service(ThrowableToProblemExceptionTransformerInterface::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(ProblemExceptionToJsonResponseSubscriber::class)
        ->args([
            service('nijens_openapi.serializer'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set(SerializationContextBuilder::class)
        ->args([
            service('nijens_openapi.json.schema_loader'),
        ]);

    $services->alias(SerializationContextBuilderInterface::class, SerializationContextBuilder::class);

    $services->set(RequestValidationSubscriber::class)
        ->args([
            service(ValidatorInterface::class),
        ])
        ->tag('kernel.event_subscriber');

    $services->alias(ValidatorInterface::class, CompositeRequestValidator::class);

    $services->set(CompositeRequestValidator::class)
        ->args([
            tagged_iterator('nijens_openapi.validation.validator'),
        ]);

    $services->set(RequestBodyValidator::class)
        ->args([
            service('nijens_openapi.json.parser'),
            service('nijens_openapi.json.validator'),
        ])
        ->tag('nijens_openapi.validation.validator', ['priority' => 0]);

    $services->set(RequestContentTypeValidator::class)
        ->tag('nijens_openapi.validation.validator', ['priority' => 16]);

    $services->set(RequestParameterValidator::class)
        ->args([
            service('nijens_openapi.json.validator'),
        ])
        ->tag('nijens_openapi.validation.validator', ['priority' => 32]);
};
