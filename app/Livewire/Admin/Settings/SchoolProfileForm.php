<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\School;
use App\Support\CollegeFlash;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SchoolProfileForm extends Component
{
    public bool $isSetupFlow = false;

    public ?int $school_id = null;

    public string $name = '';

    public string $address = '';

    public string $email = '';

    public string $phone = '';

    public string $website = '';

    public string $description = '';

    public string $motto = '';

    public ?int $established_year = null;

    public string $principal_name = '';

    public string $facebook_url = '';

    public string $twitter_url = '';

    public string $linkedin_url = '';

    public string $instagram_url = '';

    public bool $is_admit = true;

    public ?string $logoFilepondPath = null;

    public function mount(): void
    {
        $this->isSetupFlow = request()->routeIs('admin.setup.*');

        $school = School::current();
        if ($school !== null) {
            $this->school_id = $school->id;
            $this->name = (string) $school->name;
            $this->address = (string) $school->address;
            $this->email = (string) ($school->email ?? '');
            $this->phone = (string) ($school->phone ?? '');
            $this->website = (string) ($school->website ?? '');
            $this->description = (string) ($school->description ?? '');
            $this->motto = (string) ($school->motto ?? '');
            $this->established_year = $school->established_year;
            $this->principal_name = (string) ($school->principal_name ?? '');
            $this->facebook_url = (string) ($school->facebook_url ?? '');
            $this->twitter_url = (string) ($school->twitter_url ?? '');
            $this->linkedin_url = (string) ($school->linkedin_url ?? '');
            $this->instagram_url = (string) ($school->instagram_url ?? '');
            $this->is_admit = (bool) $school->is_admit;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'logoFilepondPath' => ['nullable', 'string', 'max:500'],
            'motto' => ['nullable', 'string', 'max:255'],
            'established_year' => ['nullable', 'integer', 'min:1800', 'max:'.(int) date('Y')],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'string', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'string', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'string', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'string', 'url', 'max:255'],
            'is_admit' => ['boolean'],
        ]);

        $data = [
            'name' => $this->name,
            'address' => $this->address,
            'email' => $this->email === '' ? null : $this->email,
            'phone' => $this->phone === '' ? null : $this->phone,
            'website' => $this->website === '' ? null : $this->website,
            'description' => $this->description === '' ? null : $this->description,
            'motto' => $this->motto === '' ? null : $this->motto,
            'established_year' => $this->established_year,
            'principal_name' => $this->principal_name === '' ? null : $this->principal_name,
            'facebook_url' => $this->facebook_url === '' ? null : $this->facebook_url,
            'twitter_url' => $this->twitter_url === '' ? null : $this->twitter_url,
            'linkedin_url' => $this->linkedin_url === '' ? null : $this->linkedin_url,
            'instagram_url' => $this->instagram_url === '' ? null : $this->instagram_url,
            'is_admit' => $this->is_admit,
        ];

        $userId = Auth::id();
        if ($userId !== null && $this->logoFilepondPath !== null && $this->logoFilepondPath !== '') {
            $stored = FilepondPendingFile::moveToPublicDisk(
                $this->logoFilepondPath,
                $userId,
                'school'
            );
            if ($stored !== null) {
                $data['logo'] = $stored;
            }
            $this->logoFilepondPath = null;
        }

        if ($this->school_id !== null) {
            School::query()->whereKey($this->school_id)->update($data);
        } else {
            $school = School::query()->create($data);
            $this->school_id = $school->id;
        }

        CollegeFlash::forNextRequestToo('status', __('School details have been saved.'));

        if ($this->isSetupFlow && session()->has('admin_register')) {
            $this->redirect(route('admin.setup.licence'), navigate: true);

            return;
        }
    }

    public function render(): View
    {
        $school = School::current();
        $logoUrl = ($school !== null && $school->logo) ? asset('storage/'.$school->logo) : null;
        $title = request()->routeIs('admin.setup.school')
            ? __('Setup school')
            : __('School profile');

        return view('livewire.admin.settings.school-profile-form', [
            'logoUrl' => $logoUrl,
        ])->layout('components.layouts.admin', [
            'title' => $title,
            'headerTitle' => $title,
            'headerDescription' => __('Update your school\'s identity, motto, contact information, social links, and registration settings.'),
        ]);
    }
}
