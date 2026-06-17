<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Setup;

use App\Models\Admin;
use App\Models\NonTeachingStaff;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\School;
use App\Models\User;
use App\Models\UserRole;
use App\Support\CollegeFlash;
use App\Support\FilepondPendingFile;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AdminSetupPersonalPage extends Component
{
    use DispatchesCollegeToasts;

    public bool $isSetupFlow = false;

    public string $username = '';

    public string $lastname = '';

    public string $othernames = '';

    public string $ghana_card = '';

    public string $gender = '';

    public string $phone_number = '';

    public string $position_title = '';

    public ?string $department_id = null;

    public ?string $faculty_id = null;

    public ?string $date_of_appointment = null;

    public ?string $profilePhotoPond = null;

    public bool $schoolReady = false;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->type === 'admin' || $user->type === 'staff', 403);

        $this->isSetupFlow = request()->routeIs('admin.setup.*');

        $school = School::current();
        $this->schoolReady = (bool) ($school?->ready);

        $this->username = (string) ($user->username ?? '');

        if ($user->type === 'admin') {
            $admin = $user->admin;
            if ($admin !== null) {
                $this->lastname = (string) ($admin->lastname ?? '');
                $this->othernames = (string) ($admin->othernames ?? '');
                $this->ghana_card = (string) ($admin->ghana_card ?? '');
                $this->gender = (string) ($admin->gender ?? '');
                $this->phone_number = (string) ($admin->phone_number ?? '');
                $this->position_title = (string) ($admin->position_title ?? '');
                $this->department_id = $admin->department_id !== null ? (string) $admin->department_id : null;
                $this->faculty_id = $admin->faculty_id !== null ? (string) $admin->faculty_id : null;
                $this->date_of_appointment = $admin->date_of_appointment?->format('Y-m-d');
            }
        } elseif ($user->type === 'staff') {
            $staff = $user->nonTeachingStaff;
            
            $nameParts = explode(' ', trim($user->name ?? ''));
            if (count($nameParts) > 1) {
                $this->lastname = (string) array_pop($nameParts);
                $this->othernames = (string) implode(' ', $nameParts);
            } else {
                $this->lastname = (string) ($user->name ?? '');
                $this->othernames = '';
            }

            if ($staff !== null) {
                $this->phone_number = (string) ($staff->phone_number ?? '');
                $this->position_title = (string) ($staff->position ?? '');
                $this->department_id = $staff->department_id !== null ? (string) $staff->department_id : null;
            }
        }
    }

    public function saveProfilePhoto(): void
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->type === 'admin' || $user->type === 'staff', 403);

        $this->validate([
            'profilePhotoPond' => ['required', 'string', 'max:500'],
        ]);

        if ($user->type === 'admin') {
            $admin = $user->admin;
            if ($admin === null) {
                $this->addError('profilePhotoPond', __('Please save your profile details first.'));
                return;
            }

            $moved = FilepondPendingFile::moveToPublicDisk(
                $this->profilePhotoPond,
                $user->id,
                'college-uploads/admins/profiles'
            );
            if ($moved !== null) {
                $admin->update(['profile_pic' => $moved]);
            }
        } elseif ($user->type === 'staff') {
            $staff = $user->nonTeachingStaff;
            if ($staff === null) {
                $this->addError('profilePhotoPond', __('Please save your profile details first.'));
                return;
            }

            $moved = FilepondPendingFile::moveToPublicDisk(
                $this->profilePhotoPond,
                $user->id,
                'college-uploads/staff/profiles'
            );
            if ($moved !== null) {
                $staff->update(['profile_pic' => $moved]);
            }
        }

        $this->profilePhotoPond = null;
        $this->collegeToast(__('Profile photo updated successfully.'));
        $this->redirect(request()->header('Referer'), navigate: true);
    }

    public function save(): void
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->type === 'admin' || $user->type === 'staff', 403);

        $isOwner = $user->isAdminOwner() || $this->isSetupFlow;

        $usernameRules = [
            'required',
            'string',
            'max:64',
            Rule::unique('users', 'username')->ignore($user->id),
        ];

        if ($user->type === 'admin') {
            $admin = $user->admin;
            $adminId = $admin?->id;

            if (! $this->schoolReady) {
                $this->validate([
                    'username' => $usernameRules,
                    'lastname' => ['required', 'string', 'max:120'],
                    'othernames' => ['required', 'string', 'max:120'],
                    'ghana_card' => [
                        'required',
                        'string',
                        'max:32',
                        Rule::unique('admins', 'ghana_card')->ignore($adminId),
                    ],
                ]);
            } else {
                $slug = $user->adminRoleSlug();
                $rules = [
                    'username' => $usernameRules,
                    'lastname' => ['required', 'string', 'max:120'],
                    'othernames' => ['required', 'string', 'max:120'],
                    'ghana_card' => [
                        'required',
                        'string',
                        'max:32',
                        Rule::unique('admins', 'ghana_card')->ignore($adminId),
                    ],
                    'gender' => ['required', 'in:male,female,other'],
                    'phone_number' => ['required', 'string', 'max:20'],
                ];

                if (! $isOwner) {
                    $rules['position_title'] = ['nullable', 'string', 'max:120'];
                    $rules['department_id'] = ['nullable', 'exists:departments,id'];
                    $rules['faculty_id'] = ['nullable', 'exists:faculties,id'];
                    $rules['date_of_appointment'] = ['nullable', 'date'];

                    if ($slug === 'hod') {
                        $rules['department_id'] = ['required', 'exists:departments,id'];
                    }
                    if ($slug === 'dean') {
                        $rules['faculty_id'] = ['required', 'exists:faculties,id'];
                    }
                }

                $this->validate($rules);
            }

            $user->username = $this->username;
            $user->name = trim($this->othernames.' '.$this->lastname);
            $user->save();

            UserRole::ensureSystemRoles();
            $ownerRoleId = UserRole::query()->where('name', 'owner')->value('id');

            $payload = [
                'lastname' => $this->lastname,
                'othernames' => $this->othernames,
                'ghana_card' => $this->ghana_card,
            ];

            if ($this->schoolReady) {
                $payload['gender'] = $this->gender;
                $payload['phone_number'] = $this->phone_number;
                
                if (! $isOwner) {
                    $payload['position_title'] = $this->position_title === '' ? null : $this->position_title;
                    $payload['department_id'] = $this->department_id !== null && $this->department_id !== '' ? (int) $this->department_id : null;
                    $payload['faculty_id'] = $this->faculty_id !== null && $this->faculty_id !== '' ? (int) $this->faculty_id : null;
                    $payload['date_of_appointment'] = $this->date_of_appointment !== null && $this->date_of_appointment !== ''
                        ? $this->date_of_appointment
                        : null;
                }
            }

            if ($admin === null) {
                $payload['user_id'] = $user->id;
                $payload['type'] = $ownerRoleId;
                $admin = Admin::query()->create($payload);
            } else {
                if ($admin->type === null && $ownerRoleId !== null) {
                    $payload['type'] = $ownerRoleId;
                }
                $admin->update($payload);
            }

            if ($this->schoolReady && $this->profilePhotoPond !== null && $this->profilePhotoPond !== '') {
                $moved = FilepondPendingFile::moveToPublicDisk(
                    $this->profilePhotoPond,
                    $user->id,
                    'college-uploads/admins/profiles'
                );
                if ($moved !== null) {
                    $admin->update(['profile_pic' => $moved]);
                }
                $this->profilePhotoPond = null;
            }
        } elseif ($user->type === 'staff') {
            $staff = $user->nonTeachingStaff;

            $rules = [
                'username' => $usernameRules,
                'lastname' => ['required', 'string', 'max:120'],
                'othernames' => ['required', 'string', 'max:120'],
                'phone_number' => ['required', 'string', 'max:20'],
                'department_id' => ['required', 'exists:departments,id'],
                'position_title' => ['nullable', 'string', 'max:120'],
            ];

            $this->validate($rules);

            $user->username = $this->username;
            $user->name = trim($this->othernames.' '.$this->lastname);
            $user->save();

            $payload = [
                'phone_number' => $this->phone_number,
                'position' => $this->position_title === '' ? 'Staff' : $this->position_title,
                'department_id' => (int) $this->department_id,
            ];

            if ($staff === null) {
                $payload['user_id'] = $user->id;
                $payload['status'] = 'active';
                $staff = NonTeachingStaff::query()->create($payload);
            } else {
                $staff->update($payload);
            }

            if ($this->profilePhotoPond !== null && $this->profilePhotoPond !== '') {
                $moved = FilepondPendingFile::moveToPublicDisk(
                    $this->profilePhotoPond,
                    $user->id,
                    'college-uploads/staff/profiles'
                );
                if ($moved !== null) {
                    $staff->update(['profile_pic' => $moved]);
                }
                $this->profilePhotoPond = null;
            }
        }

        CollegeFlash::forNextRequestToo('status', __('Your profile has been saved.'));

        if ($this->isSetupFlow) {
            $this->redirect(route('admin.setup.school'), navigate: true);
            return;
        }

        $this->redirect(route('admin.dashboard'), navigate: true);
    }

    public function render(): View
    {
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        $faculties = Faculty::query()->orderBy('name')->get(['id', 'name']);
        
        /** @var User $user */
        $user = Auth::user();
        $roleSlug = $user->adminRoleSlug();
        
        $existingProfileUrl = null;
        $admin = $user->admin;
        $staff = $user->nonTeachingStaff;

        if ($user->type === 'admin' && $admin !== null && $admin->profile_pic) {
            $existingProfileUrl = asset('storage/'.$admin->profile_pic);
        } elseif ($user->type === 'staff' && $staff !== null && $staff->profile_pic) {
            $existingProfileUrl = asset('storage/'.$staff->profile_pic);
        }

        $title = request()->routeIs('admin.profile')
            ? __('Admin profile')
            : __('Admin setup: personal');

        return view('livewire.admin.setup.admin-setup-personal-page', [
            'departments' => $departments,
            'faculties' => $faculties,
            'roleSlug' => $roleSlug,
            'existingProfileUrl' => $existingProfileUrl,
            'userType' => $user->type,
            'isOwner' => $user->isAdminOwner() || $this->isSetupFlow,
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
