<?php

declare(strict_types=1);

namespace Netgen\Bundle\ContentBrowserBundle\Controller\API;

use Netgen\ContentBrowser\Config\ConfigurationInterface;
use Netgen\ContentBrowser\Item\ItemInterface;
use Netgen\ContentBrowser\Item\Renderer\ItemRendererInterface;
use Symfony\Component\HttpFoundation\Response;

final class RenderItem extends Controller
{
    /**
     * @var \Netgen\ContentBrowser\Config\ConfigurationInterface
     */
    private $config;

    /**
     * @var \Netgen\ContentBrowser\Item\Renderer\ItemRendererInterface
     */
    private $itemRenderer;

    public function __construct(ConfigurationInterface $config, ItemRendererInterface $itemRenderer)
    {
        $this->config = $config;
        $this->itemRenderer = $itemRenderer;
    }

    /**
     * Renders the provided item.
     */
    public function __invoke(ItemInterface $item): Response
    {
        $renderedItem = '';
        if ($this->config->hasPreview() && is_string($this->config->getTemplate())) {
            $renderedItem = $this->itemRenderer->renderItem($item, $this->config->getTemplate());
        }

        return new Response($renderedItem);
    }
}
