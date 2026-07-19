<?php

declare(strict_types=1);

namespace App\Livewire\Navigation;

use Livewire\Component;
use Illuminate\Contracts\View\View;

class PortalSwitcher extends Component
{
    public string $activeRole = '';

    public function mount(): void
    {
        $this->activeRole = (string) session('active_portal_role', 'admin');
    }

    public function switchPortal(string $role): mixed
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Only allow switching if the user has both profiles
        $hasAdmin = $user->admin()->exists();
        $hasTeacher = $user->teacher()->exists();

        if ($hasAdmin && $hasTeacher) {
            if (in_array($role, ['admin', 'teacher'], true)) {
                session(['active_portal_role' => $role]);
                $this->activeRole = $role;

                if ($role === 'teacher') {
                    return redirect()->route('teacher.dashboard');
                } else {
                    return redirect()->route('admin.dashboard');
                }
            }
        }

        return null;
    }

    public function render(): View
    {
        $user = auth()->user();
        $showSwitcher = false;

        if ($user) {
            $hasAdmin = $user->admin()->exists();
            $hasTeacher = $user->teacher()->exists();
            $showSwitcher = $hasAdmin && $hasTeacher;
        }

        return view('livewire.navigation.portal-switcher', [
            'showSwitcher' => $showSwitcher,
        ]);
    }
}
