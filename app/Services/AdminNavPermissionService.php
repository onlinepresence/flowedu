<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Hides admin sidebar links unless the user passes the linked {@see config('college.admin_permissions')} gate.
 * Coarse legacy slugs (e.g. student_management) can grant several nav slugs via
 * {@see config('college.nav_coarse_permission_grants')}.
 */
final class AdminNavPermissionService
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function filterItemsForUser(User $user, array $items): array
    {
        if ($user->type === 'admin' && ($user->isAdminOwner() || $user->adminRoleSlug() === 'system_admin')) {
            return $items;
        }

        $out = [];
        foreach ($items as $item) {
            if (isset($item['children']) && is_array($item['children'])) {
                $children = $this->filterItemsForUser($user, $item['children']);
                if ($children === []) {
                    continue;
                }
                $item['children'] = $children;
                $out[] = $item;

                continue;
            }

            if (! $this->userMaySeeLeaf($user, $item)) {
                continue;
            }

            $out[] = $item;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function userMaySeeLeaf(User $user, array $item): bool
    {
        $permission = $item['permission'] ?? null;
        if (! is_string($permission) || $permission === '') {
            return true;
        }

        if (Gate::forUser($user)->allows('admin.'.$permission)) {
            return true;
        }

        /** @var array<string, list<string>> $grants */
        $grants = config('college.nav_coarse_permission_grants', []);

        foreach ($grants as $coarseSlug => $navSlugs) {
            if (! in_array($permission, $navSlugs, true)) {
                continue;
            }
            if (Gate::forUser($user)->allows('admin.'.$coarseSlug)) {
                return true;
            }
        }

        return false;
    }
}
