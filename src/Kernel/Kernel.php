<?php

namespace Deleto\VersioningBundle\Kernel;

use Deleto\VersioningBundle\Service\FallbackService;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\PhpFileLoader as RoutingPhpFileLoader;
use Symfony\Component\Routing\RouteCollection;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * @internal
     */
    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        // Framework part
        $file = (new \ReflectionObject($this))->getFileName();
        /* @var RoutingPhpFileLoader $kernelLoader */
        $kernelLoader = $loader->getResolver()->resolve($file, 'php');
        $kernelLoader->setCurrentDir(\dirname($file));
        $collection = new RouteCollection();

        $configureRoutes = new \ReflectionMethod($this, 'configureRoutes');
        $configureRoutes->getClosure($this)(new RoutingConfigurator($collection, $kernelLoader, $file, $file, $this->getEnvironment()));

        foreach ($collection as $route) {
            $controller = $route->getDefault('_controller');

            if (\is_array($controller) && [0, 1] === array_keys($controller) && $this === $controller[0]) {
                $route->setDefault('_controller', ['kernel', $controller[1]]);
            }
        }

        // Bundle part
        $versionMax = $this->getContainer()->getParameter('deleto.api.max_version');
        $service = new FallbackService($versionMax, $collection);

        $apiRoutes = $service->extractVersionedRoutes();
        $groupedRoutes = $service->getGroupedRoutes($apiRoutes); // Group routes by name, then index by version

        $service->createFallbacks($groupedRoutes);

        return $collection;
    }
}
