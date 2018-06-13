<?php

declare(strict_types=1);

namespace Netgen\ContentBrowser\Tests\Registry;

use ArrayIterator;
use Netgen\ContentBrowser\Backend\BackendInterface;
use Netgen\ContentBrowser\Registry\BackendRegistry;
use PHPUnit\Framework\TestCase;

final class BackendRegistryTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $backendMock;

    /**
     * @var \Netgen\ContentBrowser\Registry\BackendRegistry
     */
    private $registry;

    public function setUp(): void
    {
        $this->registry = new BackendRegistry();

        $this->backendMock = $this->createMock(BackendInterface::class);
        $this->registry->addBackend('value', $this->backendMock);
    }

    /**
     * @covers \Netgen\ContentBrowser\Registry\BackendRegistry::addBackend
     * @covers \Netgen\ContentBrowser\Registry\BackendRegistry::getBackends
     */
    public function testGetBackends(): void
    {
        $this->assertEquals(['value' => $this->backendMock], $this->registry->getBackends());
    }

    /**
     * @covers \Netgen\ContentBrowser\Registry\BackendRegistry::getBackend
     */
    public function testGetBackend(): void
    {
        $this->assertEquals($this->backendMock, $this->registry->getBackend('value'));
    }

    /**
     * @covers \Netgen\ContentBrowser\Registry\BackendRegistry::getBackend
     * @expectedException \Netgen\ContentBrowser\Exceptions\InvalidArgumentException
     * @expectedExceptionMessage Backend for "other_value" item type does not exist.
     */
    public function testGetBackendThrowsInvalidArgumentException(): void
    {
        $this->registry->getBackend('other_value');
    }

    /**
     * @covers \Netgen\ContentBrowser\Registry\BackendRegistry::hasBackend
     */
    public function testHasBackend(): void
    {
        $this->assertTrue($this->registry->hasBackend('value'));
    }

    /**
     * @covers \Netgen\ContentBrowser\Registry\BackendRegistry::hasBackend
     */
    public function testHasBackendWithNoBackend(): void
    {
        $this->assertFalse($this->registry->hasBackend('other_value'));
    }

    /**
     * @covers \Netgen\ContentBrowser\Registry\BackendRegistry::getIterator
     */
    public function testGetIterator(): void
    {
        $this->assertInstanceOf(ArrayIterator::class, $this->registry->getIterator());

        $backends = [];
        foreach ($this->registry as $identifier => $backend) {
            $backends[$identifier] = $backend;
        }

        $this->assertEquals($this->registry->getBackends(), $backends);
    }

    /**
     * @covers \Netgen\ContentBrowser\Registry\BackendRegistry::count
     */
    public function testCount(): void
    {
        $this->assertCount(1, $this->registry);
    }

    /**
     * @covers \Netgen\ContentBrowser\Registry\BackendRegistry::offsetExists
     */
    public function testOffsetExists(): void
    {
        $this->assertArrayHasKey('value', $this->registry);
        $this->assertArrayNotHasKey('other', $this->registry);
    }

    /**
     * @covers \Netgen\ContentBrowser\Registry\BackendRegistry::offsetGet
     */
    public function testOffsetGet(): void
    {
        $this->assertEquals($this->backendMock, $this->registry['value']);
    }

    /**
     * @covers \Netgen\ContentBrowser\Registry\BackendRegistry::offsetSet
     * @expectedException \Netgen\ContentBrowser\Exceptions\RuntimeException
     * @expectedExceptionMessage Method call not supported.
     */
    public function testOffsetSet(): void
    {
        $this->registry['value'] = $this->backendMock;
    }

    /**
     * @covers \Netgen\ContentBrowser\Registry\BackendRegistry::offsetUnset
     * @expectedException \Netgen\ContentBrowser\Exceptions\RuntimeException
     * @expectedExceptionMessage Method call not supported.
     */
    public function testOffsetUnset(): void
    {
        unset($this->registry['value']);
    }
}
