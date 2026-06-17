<?php

declare(strict_types=1);

namespace App\Livewire\Layout;

use App\Livewire\Actions\Logout;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LogoutButton extends Component
{
    /** @var 'sidebar'|'menu'|'toolbar' */
    public string $variant = 'toolbar';

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.layout.logout-button');
    }
}
