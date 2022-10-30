<?php

namespace Deleto\VersioningBundle\Service;
;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class FallbackService
{
    public function __construct(
        protected string $versionMax,
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
            $templateName = preg_replace('/_api_v[\d.]+/', '_api_v{version}', $name);
            preg_match('/_api_v([\d.]+)/', $name, $matches);
            $version = (string) $matches[1];
            $routesByTemplatedName[$templateName][$version] = $route;
        }

        return $routesByTemplatedName;
    }

    #[ArrayShape(['major' => "int", 'minor' => "int", 'patch' => "int"])]
    protected function splitToSemantic(string $version): array
    {
        $components = explode('.', $version);
        $major = $components[0];
        $minor = $components[1] ?? 0;

        return [
            'major' => $major,
            'minor' => $minor
        ];
    }

    public function getVersions(): array
    {
        $versions = [];

        foreach ($this->collection->all() as $name => $route) {
            if (!str_contains($name, 'app_')) {
                continue;
            }

            preg_match('/_api_v([\d.]+)/', $name, $matches);
            $version = $matches[1] ?? null;
            if (!$version) {
                continue;
            }

            if (!in_array($version, $versions, true)) {
                $versions[] = $version;
            }
        }

        usort($versions, function (string $a, string $b) {
            $vA = $this->splitToSemantic($a);
            $vB = $this->splitToSemantic($b);

            if ($vA['major'] < $vB['major']) {
                return -1;
            }

            if ($vA['major'] > $vB['major']) {
                return 1;
            }

            // At this point, major versions matches.

            if ($vA['minor'] < $vB['minor']) {
                return -1;
            }

            // They can't be duplicated, so there's no need to verify vA > vB condition.

            return 1;
        });

        return $versions;
    }

    public function createFallbacks(array &$routesByTemplatedName): void
    {
        $versions = $this->getVersions();

        foreach ($routesByTemplatedName as $nameTemplate => &$routeVersions) {
            $minSupportedVersion = array_key_first($routeVersions);
            $versionsToSupport = array_filter($versions, function ($version) use ($minSupportedVersion) {
                $vA = $this->splitToSemantic($version);
                $min = $this->splitToSemantic($minSupportedVersion);

                if ($vA['major'] < $min['major']) {
                    return false;
                }

                if ($vA['major'] > $min['major']) {
                    return true;
                }

                if ($vA['minor'] < $min['minor']) {
                    return false;
                }

                return true;
            });
            $versionsToSupport = array_values($versionsToSupport);

            foreach ($versionsToSupport as $idx => $version) {
                // When this route version overrides previous.
                if (array_key_exists($version, $routeVersions)) {
                    continue;
                }

                // This route version has no overrides, so fall back to previous version.
                $previous = $versionsToSupport[$idx - 1];
                /** @var Route $newRoute */
                $newRoute = clone $routeVersions[$previous];
                $newRoute->setPath(
                    preg_replace('/\/v([\d.]+)\//', "/v$version/", $newRoute->getPath())
                );

                $newRouteName = str_replace('{version}', $version, $nameTemplate);
                $this->collection->add($newRouteName, $newRoute);

                $routeVersions[$version] = $newRoute;
            }
        }
    }
}
