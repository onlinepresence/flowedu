<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Mirrors legacy {@see \filter_admin_nav_for_licence} / {@see \filter_student_nav_for_licence}
 * in includes/licence.php (URL path → feature key, then SchoolLicenceService::can).
 *
 * Note: Legacy {@see \licence_feature_for_admin_url} does not map `/tools/*`; those routes
 * remain protected by `college.licence:system_admin` middleware only.
 */
final class NavigationLicenceService
{
    public function __construct(
        private readonly SchoolLicenceService $licence,
    ) {}

    public function featureForAdminPath(string $path): ?string
    {
        $path = '/'.ltrim($path, '/');

        if (str_starts_with($path, '/admin/memos')) {
            return 'memos';
        }
        if (str_starts_with($path, '/admin/practicum')) {
            return 'practicum';
        }
        if (str_starts_with($path, '/admin/finance')) {
            return 'finance';
        }
        if (str_starts_with($path, '/admin/reports')) {
            return 'reports';
        }
        if (str_contains($path, '/admin/staff/evaluation')) {
            return 'evaluations';
        }
        if (str_starts_with($path, '/admin/staff')) {
            return 'staff_hr';
        }
        if (preg_match('#/admin/students/(promotion|graduation)#', $path) === 1) {
            return 'progression';
        }
        if (preg_match('#/admin/students/(medical|discipline)#', $path) === 1) {
            return 'student_welfare';
        }
        if (str_starts_with($path, '/admin/academic/timetable')) {
            return 'timetable';
        }
        if (preg_match('#^/admin/settings/(roles|users|image-validation|backup)$#', $path) === 1) {
            return 'system_admin';
        }
        if (str_starts_with($path, '/admin/audit-logs')) {
            return 'system_admin';
        }
        if ($path === '/env-generator') {
            return 'system_admin';
        }

        return null;
    }

    public function featureForStudentPath(string $path): ?string
    {
        $path = '/'.ltrim($path, '/');

        if (str_starts_with($path, '/student/evaluation')) {
            return 'evaluations';
        }
        if (str_starts_with($path, '/student/practicum')) {
            return 'practicum';
        }
        if (str_starts_with($path, '/student/fees')
            || str_starts_with($path, '/student/payment-history')
            || str_starts_with($path, '/student/allowance')) {
            return 'finance';
        }
        if ($path === '/student/clearance') {
            return 'student_welfare';
        }
        if (in_array($path, ['/student/medical', '/student/discipline'], true)) {
            return 'student_welfare';
        }
        if ($path === '/student/timetable') {
            return 'timetable';
        }
        if ($path === '/student/attendance') {
            return 'attendance';
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function filterAdminNavItems(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (isset($item['children']) && is_array($item['children'])) {
                $children = $this->filterAdminNavItems($item['children']);
                if ($children === []) {
                    continue;
                }
                $item['children'] = $children;
                $out[] = $item;

                continue;
            }

            $path = $this->pathFromNavItem($item);
            if ($path === null) {
                $out[] = $item;

                continue;
            }

            $feat = $this->featureForAdminPath($path);
            if ($feat === null || $this->licence->can($feat)) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function filterStudentNavItems(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (isset($item['children']) && is_array($item['children'])) {
                $children = $this->filterStudentNavItems($item['children']);
                if ($children === []) {
                    continue;
                }
                $item['children'] = $children;
                $out[] = $item;

                continue;
            }

            $path = $this->pathFromNavItem($item);
            if ($path === null) {
                $out[] = $item;

                continue;
            }

            $feat = $this->featureForStudentPath($path);
            if ($feat === null || $this->licence->can($feat)) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function pathFromNavItem(array $item): ?string
    {
        if (isset($item['href']) && is_string($item['href'])) {
            $path = parse_url($item['href'], PHP_URL_PATH);

            return is_string($path) ? $path : $item['href'];
        }

        if (isset($item['route']) && is_string($item['route'])) {
            try {
                $url = route($item['route'], $item['route_params'] ?? [], absolute: false);
                $path = parse_url($url, PHP_URL_PATH);

                return is_string($path) ? $path : '/'.ltrim($url, '/');
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
