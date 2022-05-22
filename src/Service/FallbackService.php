<?php

namespace Deleto\VersioningBundle\Service;

use Symfony\Component\Routing\RouteCollection;

class FallbackService
{
    public function __construct(
        protected int $versionMax,
        protected RouteCollection $collection
    ) {
    }

    public function extractVersionedRoutes(): array
    {
        $routes = [];

        foreach ($this->collection->all() as $name => $route) {
            if (str_contains($name, 'app_') && str_contains($name, '_api_v')) {
                $routes[$name] = $route;
            }
        }

        return $routes;
    }

    public function getGroupedRoutes($routes): array
    {
        $routesByTemplatedName = [];

        foreach ($routes as $name => $route) {
            $templateName = preg_replace('/_api_v[\d]+/', '_api_v{version}', $name);
            preg_match('/_api_v([\d]+)/', $name, $matches);
            $version = (string) $matches[1];
            $routesByTemplatedName[$templateName][$version] = $route;
        }

        return $routesByTemplatedName;
    }

    public function createFallbacks(array &$routesByTemplatedName): void
    {
        foreach ($routesByTemplatedName as $nameTemplate => &$routeVersions) {
            $minSupportedVersion = array_key_first($routeVersions);

            for ($i = $minSupportedVersion + 1; $i <= $this->versionMax; $i++) {
                // When this route version overrides previous
                if (array_key_exists($i, $routeVersions)) {
                    continue;
                }

                // This route version has no overrides, so fall back to previous version
                $newRoute = clone $routeVersions[$i - 1];
                $newRoute->setPath(preg_replace('/\/v([\d]+)\//', "/v$i/", $newRoute->getPath()));

                $newRouteName = str_replace('{version}', $i, $nameTemplate);

                $this->collection->add($newRouteName, $newRoute);

                $routeVersions[$i] = $newRoute; // Does not affect functionality, just for debugging. Can be removed
            }
        }
    }
}