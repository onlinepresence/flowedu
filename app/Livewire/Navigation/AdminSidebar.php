<?php

declare(strict_types=1);

namespace App\Livewire\Navigation;

use App\Models\User;
use App\Services\AdminNavPermissionService;
use App\Services\NavigationLicenceService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminSidebar extends Component
{
    public function render(
        NavigationLicenceService $navLicence,
        AdminNavPermissionService $navPermissions,
    ): View {
        $setupMode = request()->is('admin-setup') || request()->is('admin-setup/*')
            || (bool) session('admin_register', false);

        $items = $setupMode 
            ? config('sidebar.admin.setup', []) 
            : config('sidebar.admin.main', []);

        $items = $this->translateItems($items);

        if (! $setupMode) {
            $user = auth()->user();
            if ($user instanceof User) {
                $items = $navPermissions->filterItemsForUser($user, $items);
            }
            $items = $navLicence->filterAdminNavItems($items);
        }

        return view('livewire.navigation.admin-sidebar', [
            'items' => $items,
        ]);
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
}

