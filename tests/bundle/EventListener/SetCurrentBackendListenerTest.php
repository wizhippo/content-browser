<?php

declare(strict_types=1);

namespace Netgen\Bundle\ContentBrowserBundle\Tests\EventListener;

use Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentBackendListener;
use Netgen\Bundle\ContentBrowserBundle\EventListener\SetIsApiRequestListener;
use Netgen\ContentBrowser\Backend\BackendInterface;
use Netgen\ContentBrowser\Registry\BackendRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class SetCurrentBackendListenerTest extends TestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Netgen\ContentBrowser\Registry\BackendRegistryInterface
     */
    private $backendRegistry;

    /**
     * @var \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentBackendListener
     */
    private $eventListener;

    public function setUp(): void
    {
        $this->container = new Container();
        $this->backendRegistry = new BackendRegistry();

        $this->eventListener = new SetCurrentBackendListener(
            $this->container,
            $this->backendRegistry
        );
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentBackendListener::__construct
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentBackendListener::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [KernelEvents::REQUEST => 'onKernelRequest'],
            $this->eventListener::getSubscribedEvents()
        );
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentBackendListener::onKernelRequest
     */
    public function testOnKernelRequest(): void
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);
        $request->attributes->set('itemType', 'item_type');

        $event = new GetResponseEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $backendMock = $this->createMock(BackendInterface::class);
        $this->backendRegistry->addBackend('item_type', $backendMock);

        $this->eventListener->onKernelRequest($event);

        $this->assertTrue($this->container->has('netgen_content_browser.current_backend'));
        $this->assertSame($backendMock, $this->container->get('netgen_content_browser.current_backend'));
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentBackendListener::onKernelRequest
     */
    public function testOnKernelRequestInSubRequest(): void
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);
        $request->attributes->set('itemType', 'item_type');

        $event = new GetResponseEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->eventListener->onKernelRequest($event);

        $this->assertFalse($this->container->has('netgen_content_browser.current_backend'));
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentBackendListener::onKernelRequest
     */
    public function testOnKernelRequestWithNoItemType(): void
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);

        $event = new GetResponseEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->eventListener->onKernelRequest($event);

        $this->assertFalse($this->container->has('netgen_content_browser.current_backend'));
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentBackendListener::onKernelRequest
     */
    public function testOnKernelRequestWithNoContentBrowserRequest(): void
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, false);

        $event = new GetResponseEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->eventListener->onKernelRequest($event);

        $this->assertFalse($this->container->has('netgen_content_browser.current_backend'));
    }
}
