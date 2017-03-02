<?php

namespace Pimcore\API\Bundle;

use Pimcore\Bundle\PimcoreBundle\Routing\RouteReferenceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PimcoreBundleManager
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return PimcoreBundleInterface[]
     */
    public function getBundles()
    {
        $bundles = [];
        foreach ($this->container->get('kernel')->getBundles() as $bundle) {
            if ($bundle instanceof PimcoreBundleInterface) {
                $bundles[] = $bundle;
            }
        }

        return $bundles;
    }

    /**
     * @return PimcoreBundleInterface[]
     */
    public function getInstalledBundles()
    {
        return array_filter($this->getBundles(), [$this, 'isInstalled']);
    }

    /**
     * @param PimcoreBundleInterface $bundle
     * @return bool
     */
    public function isInstalled(PimcoreBundleInterface $bundle)
    {
        if (null === $installer = $bundle->getInstaller($this->container)) {
            // bundle has no dedicated installed, so we can treat it as installed
            return true;
        }

        return $installer->isInstalled();
    }

    /**
     * Resolves all admin javascripts to load
     *
     * @return array
     */
    public function getJsPaths()
    {
        return $this->resolvePaths('js');
    }

    /**
     * Resolves all admin stylesheets to load
     *
     * @return array
     */
    public function getCssPaths()
    {
        return $this->resolvePaths('css');
    }

    /**
     * Resolves all editmode javascripts to load
     *
     * @return array
     */
    public function getEditmodeJsPaths()
    {
        return $this->resolvePaths('js', 'editmode');
    }

    /**
     * Resolves all editmode stylesheets to load
     *
     * @return array
     */
    public function getEditmodeCssPaths()
    {
        return $this->resolvePaths('css', 'editmode');
    }

    /**
     * Iterates installed bundles and fetches asset paths
     *
     * @param $type
     * @param null $mode
     *
     * @return array
     */
    protected function resolvePaths($type, $mode = null)
    {
        $type = ucfirst($type);

        if (null !== $mode) {
            $mode = ucfirst($mode);
        } else {
            $mode = '';
        }

        // getJsPaths, getEditmodeJsPaths
        $getter = sprintf('get%s%sPaths', $mode, $type);

        $router = $this->container->get('router');

        $result = [];
        foreach ($this->getInstalledBundles() as $bundle) {
            $paths = $bundle->$getter();

            foreach ($paths as $path) {
                if ($path instanceof RouteReferenceInterface) {
                    $result[] = $router->generate($path->getRoute(), $path->getParameters(), $path->getType());
                } else {
                    $result[] = $path;
                }
            }
        }

        return $result;
    }
}