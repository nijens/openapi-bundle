<?php

/*
 * This file is part of the OpenapiBundle package.
 *
 * (c) Niels Nijens <nijens.niels@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nijens\OpenapiBundle\Tests\DependencyInjection;

use JsonSchema\Validator;
use League\JsonReference\Dereferencer;
use League\JsonReference\ReferenceSerializer\InlineReferenceSerializer;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Nijens\OpenapiBundle\Controller\CatchAllController;
use Nijens\OpenapiBundle\DependencyInjection\NijensOpenapiExtension;
use Nijens\OpenapiBundle\EventListener\JsonRequestBodyValidationSubscriber;
use Nijens\OpenapiBundle\EventListener\JsonResponseExceptionSubscriber;
use Nijens\OpenapiBundle\Json\SchemaLoader;
use Nijens\OpenapiBundle\Routing\RouteLoader;
use Nijens\OpenapiBundle\Service\ExceptionJsonResponseBuilder;
use Seld\JsonLint\JsonParser;
use Symfony\Component\DependencyInjection\Reference;

/**
 * NijensOpenapiExtensionTest.
 *
 * @author David Cochrum <dcochrum@sonnysdirect.com>
 */
class NijensOpenapiExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new NijensOpenapiExtension(),
        ];
    }

    public function testXmlParsedCorrectly()
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('nijens_openapi.controller.catch_all.class', CatchAllController::class);
        $this->assertContainerBuilderHasParameter('nijens_openapi.routing.loader.class', RouteLoader::class);
        $this->assertContainerBuilderHasParameter('nijens_openapi.json.parser.class', JsonParser::class);
        $this->assertContainerBuilderHasParameter('nijens_openapi.json.dereferencer.class', Dereferencer::class);
        $this->assertContainerBuilderHasParameter('nijens_openapi.json.dereferencer.serializer.class', InlineReferenceSerializer::class);
        $this->assertContainerBuilderHasParameter('nijens_openapi.json.schema_loader.class', SchemaLoader::class);
        $this->assertContainerBuilderHasParameter('nijens_openapi.json.validator.class', Validator::class);
        $this->assertContainerBuilderHasParameter('nijens_openapi.event_subscriber.json_request_body_validation.class', JsonRequestBodyValidationSubscriber::class);
        $this->assertContainerBuilderHasParameter('nijens_openapi.event_subscriber.json_response_exception.class', JsonResponseExceptionSubscriber::class);
        $this->assertContainerBuilderHasParameter('nijens_openapi.service.exception_json_response_builder.class', ExceptionJsonResponseBuilder::class);

        $this->assertContainerBuilderHasService('nijens_openapi.controller.catch_all', CatchAllController::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag('nijens_openapi.controller.catch_all', 'controller.service_arguments');

        $this->assertContainerBuilderHasService('nijens_openapi.routing.loader', RouteLoader::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('nijens_openapi.routing.loader', 0, new Reference('nijens_openapi.json.schema_loader'));
        $this->assertContainerBuilderHasServiceDefinitionWithTag('nijens_openapi.routing.loader', 'routing.loader');

        $this->assertContainerBuilderHasService('nijens_openapi.json.parser', JsonParser::class);

        $this->assertContainerBuilderHasService('nijens_openapi.json.dereferencer', Dereferencer::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('nijens_openapi.json.dereferencer', 0, null);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('nijens_openapi.json.dereferencer', 1, new Reference('nijens_openapi.json.dereferencer.serializer'));

        $this->assertContainerBuilderHasService('nijens_openapi.json.dereferencer.serializer', InlineReferenceSerializer::class);

        $this->assertContainerBuilderHasService('nijens_openapi.json.schema_loader', SchemaLoader::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('nijens_openapi.json.schema_loader', 0, new Reference('file_locator'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('nijens_openapi.json.schema_loader', 1, new Reference('nijens_openapi.json.dereferencer'));

        $this->assertContainerBuilderHasService('nijens_openapi.json.validator', Validator::class);

        $this->assertContainerBuilderHasService('nijens_openapi.event_subscriber.json_request_body_validation', JsonRequestBodyValidationSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('nijens_openapi.event_subscriber.json_request_body_validation', 0, new Reference('nijens_openapi.json.parser'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('nijens_openapi.event_subscriber.json_request_body_validation', 1, new Reference('nijens_openapi.json.schema_loader'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('nijens_openapi.event_subscriber.json_request_body_validation', 2, new Reference('nijens_openapi.json.validator'));
        $this->assertContainerBuilderHasServiceDefinitionWithTag('nijens_openapi.event_subscriber.json_request_body_validation', 'kernel.event_subscriber');

        $this->assertContainerBuilderHasService('nijens_openapi.service.exception_json_response_builder', ExceptionJsonResponseBuilder::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('nijens_openapi.service.exception_json_response_builder', 0, '%kernel.debug%');

        $this->assertContainerBuilderHasService('nijens_openapi.event_subscriber.json_response_exception');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('nijens_openapi.event_subscriber.json_response_exception', 0, new Reference('nijens_openapi.service.exception_json_response_builder'));
        $this->assertContainerBuilderHasServiceDefinitionWithTag('nijens_openapi.event_subscriber.json_response_exception', 'kernel.event_subscriber');
    }
}
