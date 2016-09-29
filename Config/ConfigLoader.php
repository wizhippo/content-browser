<?php

namespace Netgen\Bundle\ContentBrowserBundle\Config;

use Netgen\Bundle\ContentBrowserBundle\Exceptions\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ConfigLoader implements ConfigLoaderInterface
{
    use ContainerAwareTrait;

    /**
     * @var \Netgen\Bundle\ContentBrowserBundle\Config\ConfigProcessorInterface[]
     */
    protected $configProcessors = array();

    /**
     * Constructor.
     *
     * @param \Netgen\Bundle\ContentBrowserBundle\Config\ConfigProcessorInterface[] $configProcessors
     */
    public function __construct(array $configProcessors = array())
    {
        $this->configProcessors = $configProcessors;
    }

    /**
     * Loads the configuration by its name.
     *
     * @param string $configName
     *
     * @throws \Netgen\Bundle\ContentBrowserBundle\Exceptions\InvalidArgumentException If config could not be found
     *
     * @return \Netgen\Bundle\ContentBrowserBundle\Config\ConfigurationInterface
     */
    public function loadConfig($configName)
    {
        foreach ($this->configProcessors as $configProcessor) {
            if (!$configProcessor->supports($configName)) {
                continue;
            }

            $config = $this->loadDefaultConfig($configProcessor->getItemType());
            $configProcessor->processConfig($configName, $config);

            return $config;
        }

        return $this->loadDefaultConfig($configName);
    }

    /**
     * Loads the default configuration by its name.
     *
     * @param string $configName
     *
     * @throws \Netgen\Bundle\ContentBrowserBundle\Exceptions\InvalidArgumentException If config could not be found
     *
     * @return \Netgen\Bundle\ContentBrowserBundle\Config\ConfigurationInterface
     */
    protected function loadDefaultConfig($configName)
    {
        $service = 'netgen_content_browser.config.' . $configName;

        if (!$this->container->has($service)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Configuration for "%s" item type does not exist.',
                    $configName
                )
            );
        }

        return $this->container->get($service);
    }
}
