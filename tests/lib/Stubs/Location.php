<?php

namespace Netgen\ContentBrowser\Tests\Stubs;

use Netgen\ContentBrowser\Item\LocationInterface;

final class Location implements LocationInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $parentId;

    /**
     * Constructor.
     *
     * @param int $id
     * @param int $parentId
     */
    public function __construct($id, $parentId = null)
    {
        $this->id = $id;
        $this->parentId = $parentId;
    }

    /**
     * Returns the location ID.
     *
     * @return int|string
     */
    public function getLocationId()
    {
        return $this->id;
    }

    /**
     * Returns the item name.
     *
     * @return string
     */
    public function getName()
    {
        return 'This is a name';
    }

    /**
     * Returns the item parent ID.
     *
     * @return int|string
     */
    public function getParentId()
    {
        return $this->parentId;
    }
}
