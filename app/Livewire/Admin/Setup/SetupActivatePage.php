<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Setup;

use App\Jobs\SendCollegeNotificationMailJob;
use App\Models\Department;
use App\Models\Hall;
use App\Models\Program;
use App\Models\School;
use App\Services\SchoolLicenceService;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SetupActivatePage extends Component
{
    public function prerequisitesMet(): bool
    {
        $school = School::current();

        return $school !== null
            && Department::query()->exists()
            && Program::query()->exists()
            && Hall::query()->exists();
    }

    public function checklist(): array
    {
        $school = School::current();

        return [
            'school' => $school !== null,
            'departments' => Department::query()->exists(),
            'programs' => Program::query()->exists(),
            'halls' => Hall::query()->exists(),
        ];
    }

    public function setReady(bool $ready, SchoolLicenceService $licenceService): void
    {
        if (! $this->prerequisitesMet()) {
            return;
        }

        $school = School::current();
        if ($school === null) {
            return;
        }

        $school->ready = $ready;
        $school->save();

        $licenceService->refresh();

        session()->forget('admin_register');

        $user = Auth::user();
        if ($user !== null) {
            $message = $ready
                ? __('Your school account has been activated.')
                : __('Your school account has been deactivated.');
            SendCollegeNotificationMailJob::dispatch(
                $user->email,
                (string) __('School status change'),
                '<p>'.e($message).'</p>',
            );
        }

        CollegeFlash::forNextRequestToo('status', __('Settings have been updated.'));
    }

    public function render(): View
    {
        $school = School::current();
        $met = $this->prerequisitesMet();
        $ready = (bool) ($school?->ready);

        return view('livewire.admin.setup.setup-activate-page', [
            'checklist' => $this->checklist(),
            'prerequisitesMet' => $met,
            'schoolReady' => $ready,
        ])->layout('components.layouts.admin', ['title' => __('Activate system')]);
    }
}
