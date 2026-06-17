<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Models\UserRole;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class InstitutionRolesPage extends Component
{
    public function render(): View
    {
        $roles = UserRole::query()->orderBy('display_name')->orderBy('name')->get();

        return view('livewire.admin.staff.institution-roles-page', [
            'roles' => $roles,
        ])->layout('components.layouts.admin', [
            'title' => __('Roles & Permissions'),
            'headerTitle' => __('Roles & Permissions'),
            'headerDescription' => __('View system permissions and access levels mapped to administrative roles.'),
        ]);
    }
}
