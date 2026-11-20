<?php

namespace App\Services;

use Illuminate\Routing\Route;

/**
 * Reads server-owned protected route metadata. It never accepts request input.
 */
class ProtectedRoutePolicy
{
    /** @param list<array{method:string,uri:string,name:string,action:string,key:string}>|null $routeMetadata */
    public function __construct(private readonly ?array $routeMetadata = null)
    {
    }

    /** @return list<array{method:string,uri:string,name:string,action:string,key:string}> */
    private function metadata(): array
    {
        return $this->routeMetadata ?? config('permission_protected_routes.routes', []);
    }

    public function inspect(Route $route): ProtectedRouteDecision
    {
        $identity = $this->identity($route);
        $matches = array_values(array_filter(
            $this->metadata(),
            fn (array $item): bool => $item['method'] === $identity['method']
                && $item['uri'] === $identity['uri']
                && $item['name'] === $identity['name']
                && $this->sameAction($item['action'], $identity['action']),
        ));

        // ViewController and invokable controller representations can differ
        // between framework versions; route identity remains unique by the
        // server-owned method/URI/name tuple.
        if ($matches === []) {
            $matches = array_values(array_filter(
                $this->metadata(),
                fn (array $item): bool => $item['method'] === $identity['method']
                    && $item['uri'] === $identity['uri']
                    && $item['name'] === $identity['name'],
            ));
        }

        if (count($matches) === 0) {
            return new ProtectedRouteDecision(
                ProtectedRouteDecision::METADATA_MISSING,
                reason: 'protected_route_metadata_missing',
            );
        }

        if (count($matches) !== 1) {
            return new ProtectedRouteDecision(
                ProtectedRouteDecision::METADATA_CONFLICT,
                reason: 'protected_route_metadata_conflict',
            );
        }

        return new ProtectedRouteDecision(
            ProtectedRouteDecision::PROTECTED,
            $matches[0]['key'] !== '-' ? $matches[0]['key'] : null,
            'protected_route_metadata_match',
        );
    }

    public function isProtectedCapability(string $permissionKey): bool
    {
        foreach ($this->metadata() as $item) {
            if ($item['key'] === $permissionKey) {
                return true;
            }
        }

        return false;
    }

    public function isSystemProtectedCapability(string $permissionKey): bool
    {
        return in_array(
            $permissionKey,
            config('admin_control_center.system_protected_permissions', []),
            true,
        );
    }

    /** @return array{method:string,uri:string,name:string,action:string} */
    private function identity(Route $route): array
    {
        return [
            'method' => implode(',', $route->methods()),
            'uri' => ltrim($route->uri(), '/'),
            'name' => (string) $route->getName(),
            'action' => $route->getActionName(),
        ];
    }

    private function sameAction(string $expected, string $actual): bool
    {
        return $expected === $actual
            || str_replace('@__invoke', '', $expected) === str_replace('@__invoke', '', $actual);
    }
}
