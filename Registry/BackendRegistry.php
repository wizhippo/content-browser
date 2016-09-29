<?php

namespace Netgen\Bundle\ContentBrowserBundle\Registry;

use Netgen\Bundle\ContentBrowserBundle\Backend\BackendInterface;
use Netgen\Bundle\ContentBrowserBundle\Exceptions\InvalidArgumentException;

class BackendRegistry implements BackendRegistryInterface
{
    /**
     * @var \Netgen\Bundle\ContentBrowserBundle\Backend\BackendInterface[]
     */
    protected $backends = array();

    /**
     * Adds a backend to registry.
     *
     * @param string $itemType
     * @param \Netgen\Bundle\ContentBrowserBundle\Backend\BackendInterface $backend
     */
    public function addBackend($itemType, BackendInterface $backend)
    {
        $this->backends[$itemType] = $backend;
    }

    /**
     * Returns if registry has a backend.
     *
     * @param string $itemType
     *
     * @return bool
     */
    public function hasBackend($itemType)
    {
        return isset($this->backends[$itemType]);
    }

    /**
     * Returns a backend for provided item type.
     *
     * @param string $itemType
     *
     * @throws \Netgen\Bundle\ContentBrowserBundle\Exceptions\InvalidArgumentException If backend does not exist
     *
     * @return \Netgen\Bundle\ContentBrowserBundle\Backend\BackendInterface
     */
    public function getBackend($itemType)
    {
        if (!$this->hasBackend($itemType)) {
            throw new InvalidArgumentException(
                sprintf('Backend for "%s" item type does not exist.', $itemType)
            );
        }

        return $this->backends[$itemType];
    }

    /**
     * Returns all backends.
     *
     * @return \Netgen\Bundle\ContentBrowserBundle\Backend\BackendInterface[]
     */
    public function getBackends()
    {
        return $this->backends;
    }
}
