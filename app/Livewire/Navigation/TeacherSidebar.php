<?php

declare(strict_types=1);

namespace App\Livewire\Navigation;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TeacherSidebar extends Component
{
    public function render(\App\Services\SchoolLicenceService $licence): View
    {
        /** @var User $user */
        $user = auth()->user();

        $needsProfile = $user->username === null || $user->username === '';

        $items = $needsProfile
            ? config('sidebar.teacher.setup', [])
            : config('sidebar.teacher.main', []);

        if (!$needsProfile) {
            $items = array_values(array_filter($items, function ($item) use ($user, $licence) {
                if (isset($item['licence']) && !$licence->can($item['licence'])) {
                    return false;
                }
                if (isset($item['permission'])) {
                    return $user->hasTeacherPermission($item['permission']);
                }
                return true;
            }));
        }

        $items = $this->translateItems($items);

        return view('livewire.navigation.teacher-sidebar', [
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

