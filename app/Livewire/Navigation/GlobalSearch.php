<?php

declare(strict_types=1);

namespace App\Livewire\Navigation;

use App\Models\User;
use App\Services\AdminNavPermissionService;
use App\Services\NavigationLicenceService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class GlobalSearch extends Component
{
    public function render(
        AdminNavPermissionService $navPermissions,
        NavigationLicenceService $navLicence
    ): View {
        // Compute all available pages once and pass to Alpine for client-side filtering.
        $allPages = $this->getAvailablePages($navPermissions, $navLicence);

        return view('livewire.navigation.global-search', [
            'allPages' => $allPages,
        ]);
    }

    /**
     * Get all flattened pages currently available to the user based on role, setup mode, permissions, and license.
     *
     * @return array<int, array{label: string, url: string, icon: string}>
     */
    private function getAvailablePages(
        AdminNavPermissionService $navPermissions,
        NavigationLicenceService $navLicence
    ): array {
        /** @var User|null $user */
        $user = auth()->user();
        if ($user === null) {
            return [];
        }

        $items = [];
        $role = $user->type;

        if ($role === 'admin') {
            $setupMode = $this->isAdminSetupMode();

            $items = $setupMode
                ? config('sidebar.admin.setup', [])
                : config('sidebar.admin.main', []);

            if (! $setupMode) {
                $items = $navPermissions->filterItemsForUser($user, $items);
                $items = $navLicence->filterAdminNavItems($items);
            }
        } elseif ($role === 'teacher') {
            $needsProfile = $user->username === null || $user->username === '';
            $items = $needsProfile
                ? config('sidebar.teacher.setup', [])
                : config('sidebar.teacher.main', []);
        } elseif ($role === 'student') {
            $user->loadMissing('student');
            $student = $user->student;

            $setupMode = $student === null
                || ! $student->approved
                || $student->is_new;

            $items = $setupMode
                ? config('sidebar.student.setup', [])
                : $navLicence->filterStudentNavItems(config('sidebar.student.main', []));
        }

        $items = $this->translateItems($items);

        return $this->flattenItems($items);
    }

    /**
     * Determine if the admin is currently in setup mode.
     */
    private function isAdminSetupMode(): bool
    {
        if (request()->is('admin-setup') || request()->is('admin-setup/*')) {
            return true;
        }

        $referer = request()->headers->get('referer');
        if ($referer) {
            $path = parse_url($referer, PHP_URL_PATH);
            $path = ltrim($path ?: '', '/');
            if (str_starts_with($path, 'admin-setup')) {
                return true;
            }
        }

        return (bool) session('admin_register', false);
    }

    /**
     * Translate the labels of navigation items recursively.
     *
     * @param  array<string, mixed>  $items
     * @return array<string, mixed>
     */
    private function translateItems(array $items): array
    {
        foreach ($items as &$item) {
            if (isset($item['label'])) {
                $item['label'] = __($item['label']);
            }
            if (isset($item['children']) && is_array($item['children'])) {
                $item['children'] = $this->translateItems($item['children']);
            }
        }

        return $items;
    }

    /**
     * Flatten hierarchical menu items into leaf pages with breadcrumb labels.
     * Priority for icon: own config icon → label-based lookup → inherited parent icon → fallback.
     *
     * @param  array<string, mixed>  $items
     * @param  string  $parentLabel
     * @param  string  $parentIcon  Heroicon name inherited from the parent group
     * @return array<int, array{label: string, url: string, icon: string}>
     */
    private function flattenItems(array $items, string $parentLabel = '', string $parentIcon = ''): array
    {
        $pages = [];

        foreach ($items as $item) {
            $label = $parentLabel !== ''
                ? $parentLabel . ' › ' . $item['label']
                : $item['label'];

            // Prefer own icon, otherwise inherit parent's for recursion
            $configIcon = $item['icon'] ?? $parentIcon;

            if (isset($item['children']) && is_array($item['children'])) {
                $pages = array_merge($pages, $this->flattenItems($item['children'], $label, $configIcon));
            } else {
                $url = isset($item['route'])
                    ? route($item['route'])
                    : (isset($item['href']) ? url($item['href']) : '#');

                // 1. If the item has its own config icon, use it
                // 2. Otherwise try to match by this item's label
                // 3. Fall back to resolved parent icon
                $faIcon = isset($item['icon'])
                    ? $this->resolveIcon($item['icon'])
                    : ($this->resolveIconByLabel($item['label'] ?? '') ?: $this->resolveIcon($configIcon));

                $pages[] = [
                    'label' => $label,
                    'url'   => $url,
                    'icon'  => $faIcon,
                ];
            }
        }

        return $pages;
    }

    /**
     * Map Heroicon-style names (used in config/sidebar.php) to Font Awesome icon names
     * for display in the global search palette only.
     */
    private function resolveIcon(string $heroIcon): string
    {
        $map = [
            // Dashboard / layout
            'squares-2x2'             => 'table-cells-large',
            // People
            'user'                    => 'user',
            'user-group'              => 'users',
            'users'                   => 'users',
            // Buildings / academic
            'academic-cap'            => 'graduation-cap',
            'building-office'         => 'building',
            'building-office-2'       => 'building',
            'building-library'        => 'landmark',
            // Communication
            'envelope'                => 'envelope',
            // Documents / grading
            'pencil-square'           => 'pencil',
            'book-open'               => 'book-open',
            'clipboard-document-check'=> 'clipboard-check',
            'clipboard-document-list' => 'clipboard-list',
            'document-text'           => 'file-lines',
            // Finance
            'currency-dollar'         => 'dollar-sign',
            // Reports
            'chart-bar'               => 'chart-bar',
            // Settings / tools
            'cog-6-tooth'             => 'gear',
            'wrench-screwdriver'      => 'wrench',
            'identification'          => 'id-card',
            'briefcase'               => 'briefcase',
            // Setup
            'home-modern'             => 'house',
            'power'                   => 'power-off',
            // Student-specific
            'shield-check'            => 'shield-halved',
            'trash'                   => 'trash',
            'calendar-days'           => 'calendar-days',
            'heart'                   => 'heart',
            'exclamation-triangle'    => 'triangle-exclamation',
        ];

        return $map[$heroIcon] ?? 'file';
    }

    /**
     * Derive a Font Awesome icon name directly from a menu item's label.
     * Returns an empty string if no match, so the caller can fall back gracefully.
     */
    private function resolveIconByLabel(string $label): string
    {
        $map = [
            // ── Admin: top-level pages with their own icon ──────────────────
            'Dashboard'                    => 'table-cells-large',
            'My Dashboard'                 => 'table-cells-large',
            'Memos'                        => 'envelope',

            // ── Students section ────────────────────────────────────────────
            'All Students'                 => 'users',
            'Student Promotion'            => 'arrow-up-right-dots',
            'Graduation Management'        => 'graduation-cap',
            'Medical Info'                 => 'heart-pulse',
            'Disciplinary Records'         => 'gavel',

            // ── Academic section ─────────────────────────────────────────────
            'Faculties'                    => 'landmark',
            'Departments'                  => 'briefcase',
            'Programs'                     => 'book',
            'Academic Sessions / Terms'    => 'calendar-alt',
            'Timetable'                    => 'calendar-check',
            'Class Timetable'              => 'calendar-check',

            // ── Grading section ──────────────────────────────────────────────
            'Grade Points'                 => 'star',
            'Enter Results'                => 'pen-to-square',
            'Upload Results'               => 'file-arrow-up',
            'Results Approval'             => 'circle-check',
            'Transcripts'                  => 'scroll',
            'My Transcript'                => 'scroll',
            'Grade Submissions'            => 'check-double',

            // ── Administration section ───────────────────────────────────────
            'Admin Staff'                  => 'user-tie',
            'Non-Teaching Staff'           => 'people-group',
            'Staff Assignments'            => 'list-check',
            'Staff Roles'                  => 'shield',

            // ── Teachers / Lecturers section ─────────────────────────────────
            'All Teachers'                 => 'chalkboard-user',
            'Teacher Assignments'          => 'clipboard-list',
            'Teacher Roles'                => 'id-badge',
            'Teacher Evaluations'          => 'star-half-stroke',
            'Course Materials Review'      => 'folder-open',
            'Teacher Announcements'        => 'bullhorn',
            'Course Materials'             => 'folder',
            'Courses Assigned'             => 'book-open',

            // ── Finance section ──────────────────────────────────────────────
            'Fee Structure'                => 'money-bill-wave',
            'Payments'                     => 'credit-card',
            'Outstanding Fees'             => 'circle-exclamation',
            'Scholarships / Grants'        => 'award',
            'Fee Details'                  => 'money-bill',
            'Payment History'              => 'receipt',
            'Scholarships'                 => 'award',

            // ── Reports section ──────────────────────────────────────────────
            'Academic Reports'             => 'chart-line',
            'Payment Reports'              => 'receipt',
            'Attendance Reports'           => 'calendar-check',

            // ── System Settings section ──────────────────────────────────────
            'Licence & subscription'       => 'key',
            'Roles & Permissions'          => 'lock',
            'Image Validation'             => 'image',
            'User Accounts'                => 'users-gear',
            'School Profile'               => 'school',
            'Backup & Restore'             => 'hard-drive',
            'System Variables'             => 'terminal',

            // ── Tools section ────────────────────────────────────────────────
            'Passport validator'           => 'id-card',

            // ── Teacher pages ────────────────────────────────────────────────
            'My Profile'                   => 'user',
            'Student List'                 => 'list',
            'Attendance'                   => 'calendar-check',
            'Performance'                  => 'chart-line',
            'Announcements'                => 'bullhorn',
            'Messages'                     => 'comment',

            // ── Student pages ────────────────────────────────────────────────
            'My Courses'                   => 'book',
            'My Timetable'                 => 'calendar',
            'My Results'                   => 'chart-bar',
            'Clearance Request'            => 'file-signature',
            'Evaluation'                   => 'clipboard-list',

            // ── Setup wizards ────────────────────────────────────────────────
            'Personal Information'         => 'user-pen',
            'Parent/Guardian Information'  => 'shield-halved',
            'Admission Status'             => 'clipboard-check',
            'Cancel Registration'          => 'trash',
            'Setup Profile'                => 'user-pen',
            'Setup School'                 => 'school',
            'Package & licence'            => 'key',
            'Setup Faculties'              => 'landmark',
            'Setup Departments'            => 'briefcase',
            'Setup Programs'               => 'book',
            'Setup Halls'                  => 'house',
            'Activate System'              => 'power-off',
        ];

        return $map[$label] ?? '';
    }
}
