<?php

namespace Netgen\Bundle\ContentBrowserBundle\Tests\EventListener;

use Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentConfigListener;
use Netgen\Bundle\ContentBrowserBundle\Config\ConfigLoaderInterface;
use Netgen\Bundle\ContentBrowserBundle\EventListener\SetIsApiRequestListener;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\TestCase;

class SetCurrentConfigListenerTest extends TestCase
{
    /**
     * @var \Netgen\Bundle\ContentBrowserBundle\Config\ConfigLoaderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configLoaderMock;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $containerMock;

    /**
     * @var \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentConfigListener
     */
    protected $eventListener;

    public function setUp()
    {
        $this->configLoaderMock = $this->createMock(ConfigLoaderInterface::class);
        $this->containerMock = $this->createMock(ContainerInterface::class);

        $this->eventListener = new SetCurrentConfigListener($this->configLoaderMock);
        $this->eventListener->setContainer($this->containerMock);
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentConfigListener::__construct
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentConfigListener::getSubscribedEvents
     */
    public function testGetSubscribedEvents()
    {
        self::assertEquals(
            array(KernelEvents::REQUEST => 'onKernelRequest'),
            $this->eventListener->getSubscribedEvents()
        );
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentConfigListener::onKernelRequest
     */
    public function testOnKernelRequest()
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);
        $request->attributes->set('config', 'config_name');

        $event = new GetResponseEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $config = array(
            'value_type' => 'value',
        );

        $this->configLoaderMock
            ->expects($this->at(0))
            ->method('loadConfig')
            ->with($this->equalTo('config_name'))
            ->will($this->returnValue($config));

        $this->containerMock
            ->expects($this->at(0))
            ->method('set')
            ->with(
                $this->equalTo('netgen_content_browser.current_config'),
                $this->equalTo($config)
            );

        $this->eventListener->onKernelRequest($event);

        self::assertEquals('value', $request->attributes->get('valueType'));
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentConfigListener::onKernelRequest
     */
    public function testOnKernelRequestInSubRequest()
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);
        $request->attributes->set('config', 'config_name');

        $event = new GetResponseEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->configLoaderMock
            ->expects($this->never())
            ->method('loadConfig');

        $this->containerMock
            ->expects($this->never())
            ->method('set');

        self::assertFalse($request->attributes->has('valueType'));

        $this->eventListener->onKernelRequest($event);
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentConfigListener::onKernelRequest
     */
    public function testOnKernelRequestWithNoConfig()
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, true);

        $event = new GetResponseEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->configLoaderMock
            ->expects($this->never())
            ->method('loadConfig');

        $this->containerMock
            ->expects($this->never())
            ->method('set');

        self::assertFalse($request->attributes->has('valueType'));

        $this->eventListener->onKernelRequest($event);
    }

    /**
     * @covers \Netgen\Bundle\ContentBrowserBundle\EventListener\SetCurrentConfigListener::onKernelRequest
     */
    public function testOnKernelRequestWithNoContentBrowserRequest()
    {
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/');
        $request->attributes->set(SetIsApiRequestListener::API_FLAG_NAME, false);

        $event = new GetResponseEvent(
            $kernelMock,
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->configLoaderMock
            ->expects($this->never())
            ->method('loadConfig');

        $this->containerMock
            ->expects($this->never())
            ->method('set');

        self::assertFalse($request->attributes->has('valueType'));

        $this->eventListener->onKernelRequest($event);
    }
}
