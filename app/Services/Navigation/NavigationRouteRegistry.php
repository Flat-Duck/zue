<?php

namespace App\Services\Navigation;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

class NavigationRouteRegistry
{
    /**
     * @return list<array{name: string, uri: string, action: string, middleware: list<string>, parameters: list<string>, navigable: bool, reason: string|null}>
     */
    public function namedGetRoutes(): array
    {
        return collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => $this->isNamedGetRoute($route))
            ->map(fn (Route $route): array => $this->describe($route))
            ->sortBy('name')
            ->values()
            ->all();
    }

    public function exists(string $routeName): bool
    {
        return RouteFacade::has($routeName) && collect($this->namedGetRoutes())->contains('name', $routeName);
    }

    /**
     * @param  array<string, mixed>|null  $parameters
     */
    public function canRender(string $routeName, ?array $parameters = null): bool
    {
        $route = RouteFacade::getRoutes()->getByName($routeName);

        if (! $route || ! $this->isNamedGetRoute($route)) {
            return false;
        }

        $requiredParameters = $this->requiredParameters($route);

        if ($requiredParameters === []) {
            return true;
        }

        $parameters ??= [];

        foreach ($requiredParameters as $requiredParameter) {
            if (! array_key_exists($requiredParameter, $parameters) || $parameters[$requiredParameter] === null || $parameters[$requiredParameter] === '') {
                return false;
            }
        }

        return true;
    }

    private function isNamedGetRoute(Route $route): bool
    {
        $name = $route->getName();

        if (! $name || ! in_array('GET', $route->methods(), true)) {
            return false;
        }

        return ! $this->isInternalRoute($name, $route->uri());
    }

    private function isInternalRoute(string $name, string $uri): bool
    {
        return Str::startsWith($name, [
            'dusk.',
            'ignition.',
            'livewire.',
            'password.',
            'verification.',
        ]) || Str::startsWith($uri, [
            '_dusk',
            '_ignition',
            'livewire/',
        ]) || in_array($name, [
            'login',
            'logout',
            'register',
            'password.request',
            'password.reset',
        ], true);
    }

    /**
     * @return array{name: string, uri: string, action: string, middleware: list<string>, parameters: list<string>, navigable: bool, reason: string|null}
     */
    private function describe(Route $route): array
    {
        $parameters = $this->requiredParameters($route);

        return [
            'name' => (string) $route->getName(),
            'uri' => $route->uri(),
            'action' => $route->getActionName(),
            'middleware' => array_values($route->gatherMiddleware()),
            'parameters' => $parameters,
            'navigable' => $parameters === [],
            'reason' => $parameters === [] ? null : 'Requires route parameters: '.implode(', ', $parameters),
        ];
    }

    /**
     * @return list<string>
     */
    private function requiredParameters(Route $route): array
    {
        preg_match_all('/\{([^}?]+)\??\}/', $route->uri(), $matches);

        return $matches[1];
    }
}
