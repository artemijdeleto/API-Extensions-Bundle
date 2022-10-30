<?php

namespace Deleto\VersioningBundle;

use Deleto\VersioningBundle\Service\FallbackService;
use Symfony\Component\Routing\RouteCollection;

class Versioner
{
    public function __construct(protected string $versionMin, protected string $versionMax)
    {
    }

    public function run(RouteCollection $collection): void
    {
        $versionMax = $this->versionMax;
        $service = new FallbackService($versionMax, $collection);

        $apiRoutes = $service->extractVersionedRoutes();
        $groupedRoutes = $service->getGroupedRoutes($apiRoutes); // Group routes by name, then index by version

        $service->createFallbacks($groupedRoutes);
    }
}
