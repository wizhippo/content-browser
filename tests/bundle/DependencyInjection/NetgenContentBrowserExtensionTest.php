<?php

declare(strict_types=1);

namespace Netgen\Bundle\ContentBrowserBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Netgen\Bundle\ContentBrowserBundle\DependencyInjection\Configuration;
use Netgen\Bundle\ContentBrowserBundle\DependencyInjection\NetgenContentBrowserExtension;

final class NetgenContentBrowserExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @var \Netgen\Bundle\ContentBrowserBundle\DependencyInjection\NetgenContentBrowserExtension
     */
    private $extension;

    public function setUp(): void
    {
        parent::setUp();

        /** @var \Netgen\Bundle\ContentBrowserBundle\DependencyInjection\NetgenContentBrowserExtension $extension */
        $extension = $this->container->getExtension('netgen_content_browser');

        $this->extension = $extension;
    }

    /**
     * We test for existence of one service from each of the config files.
     *
     * @covers \Netgen\Bundle\ContentBrowserBundle\DependencyInjection\NetgenContentBrowserExtension::load
     */
    public function testServices(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'EzPublishCoreBundle' => 'EzPublishCoreBundle',
                'NetgenTagsBundle' => 'NetgenTagsBundle',
                'SyliusCoreBundle' => 'SyliusCoreBundle',
            ]
        );

        $this->load(
            [
                'item_types' => [
                    'ezcontent' => [
                        'name' => 'item_types.ezcontent',
                        'preview' => [
                            'template' => 'template.html.twig',
                        ],
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasParameter(
            'netgen_content_browser.item_types',
            [
                'ezcontent' => 'item_types.ezcontent',
            ]
        );

        $this->assertContainerBuilderHasService('netgen_content_browser.backend.ezlocation');
        $this->assertContainerBuilderHasService('netgen_content_browser.backend.ezcontent');
        $this->assertContainerBuilderHasService('netgen_content_browser.backend.eztags');
        $this->assertContainerBuilderHasService('netgen_content_browser.backend.sylius_product');

        $this->assertContainerBuilderHasSyntheticService('netgen_content_browser.current_config');
    }

    /**
     * We test for existence of one service from each of the config files.
     *
     * @covers \Netgen\Bundle\ContentBrowserBundle\DependencyInjection\NetgenContentBrowserExtension::load
     */
    public function testServicesWithoutBundles(): void
    {
        $this->container->setParameter('kernel.bundles', []);

        $this->load();

        $this->assertContainerBuilderNotHasService('netgen_content_browser.backend.ezlocation');
        $this->assertContainerBuilderNotHasService('netgen_content_browser.backend.ezcontent');
        $this->assertContainerBuilderNotHasService('netgen_content_browser.backend.eztags');
        $this->assertContainerBuilderNotHasService('netgen_content_browser.backend.sylius_product');

        $this->assertContainerBuilderHasSyntheticService('netgen_content_browser.current_config');
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\DependencyInjection\NetgenContentBrowserExtension::load
     * @expectedException \Netgen\ContentBrowser\Exceptions\RuntimeException
     * @expectedExceptionMessage Item type must begin with a letter and be followed by any combination of letters, digits and underscore.
     */
    public function testLoadThrowsRuntimeExceptionOnInvalidItemType(): void
    {
        $this->container->setParameter('kernel.bundles', []);

        $this->load(
            [
                'item_types' => [
                    'Item type' => [
                        'name' => 'item_types.ezcontent',
                        'preview' => [
                            'template' => 'template.html.twig',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\DependencyInjection\NetgenContentBrowserExtension::getConfiguration
     */
    public function testGetConfiguration(): void
    {
        $configuration = $this->extension->getConfiguration([], $this->container);
        $this->assertInstanceOf(Configuration::class, $configuration);
    }

    /**
     * We test for existence of one config value from each of the config files.
     *
     * @covers \Netgen\Bundle\ContentBrowserBundle\DependencyInjection\NetgenContentBrowserExtension::doPrepend
     * @covers \Netgen\Bundle\ContentBrowserBundle\DependencyInjection\NetgenContentBrowserExtension::prepend
     */
    public function testPrepend(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'EzPublishCoreBundle' => 'EzPublishCoreBundle',
                'NetgenTagsBundle' => 'NetgenTagsBundle',
                'SyliusCoreBundle' => 'SyliusCoreBundle',
            ]
        );

        $this->extension->prepend($this->container);

        $config = call_user_func_array(
            'array_merge_recursive',
            $this->container->getExtensionConfig('netgen_content_browser')
        );

        $this->assertInternalType('array', $config);
        $this->assertArrayHasKey('item_types', $config);

        $this->assertArrayHasKey('ezcontent', $config['item_types']);
        $this->assertArrayHasKey('ezlocation', $config['item_types']);
        $this->assertArrayHasKey('eztags', $config['item_types']);
        $this->assertArrayHasKey('sylius_product', $config['item_types']);
    }

    protected function getContainerExtensions(): array
    {
        return [
            new NetgenContentBrowserExtension(),
        ];
    }
}
