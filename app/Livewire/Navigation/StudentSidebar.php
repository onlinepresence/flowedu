<?php

declare(strict_types=1);

namespace App\Livewire\Navigation;

use App\Models\User;
use App\Services\NavigationLicenceService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentSidebar extends Component
{
    public function render(NavigationLicenceService $navLicence): View
    {
        /** @var User $user */
        $user = auth()->user();
        $user->loadMissing('student');
        $student = $user->student;

        $setupMode = $student === null
            || ! $student->approved
            || $student->is_new;

        $items = $setupMode
            ? config('sidebar.student.setup', [])
            : $navLicence->filterStudentNavItems(config('sidebar.student.main', []));

        $items = $this->translateItems($items);

        return view('livewire.navigation.student-sidebar', [
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

