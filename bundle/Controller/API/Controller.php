<?php

declare(strict_types=1);

namespace Netgen\Bundle\ContentBrowserBundle\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class Controller extends BaseController
{
    /**
     * Initializes the controller by setting the container and performing basic access checks.
     */
    public function initialize(ContainerInterface $container): void
    {
        $this->setContainer($container);
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
    }
}
