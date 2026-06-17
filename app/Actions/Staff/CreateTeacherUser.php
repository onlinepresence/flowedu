<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CreateTeacherUser
{
    /**
     * @param  array{name: string, username: string, email: string, password: string, lastname: string, othernames?: string|null, staff_id?: string|null, department_id?: int|null, phone_number?: string|null, active?: bool}  $data
     * @return array{user: User, teacher: Teacher}
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->where('username', $data['username'])->first();

            if ($user !== null) {
                $user->forceFill([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'active' => (bool) ($data['active'] ?? true),
                ]);
                if (isset($data['password']) && $data['password'] !== '') {
                    $user->password = Hash::make($data['password']);
                }
                $user->save();

                $teacher = Teacher::withTrashed()->where('user_id', $user->id)->first();
                if ($teacher === null) {
                    $teacher = Teacher::query()->create([
                        'user_id' => $user->id,
                        'lastname' => $data['lastname'],
                        'othernames' => $data['othernames'] ?? null,
                        'staff_id' => $data['staff_id'] ?? $data['username'],
                        'department_id' => $data['department_id'] ?? null,
                        'phone_number' => $data['phone_number'] ?? null,
                        'password_reset_required' => true,
                        'is_onboarded' => false,
                    ]);
                } else {
                    if ($teacher->trashed()) {
                        $teacher->restore();
                    }
                    $teacher->forceFill([
                        'lastname' => $data['lastname'],
                        'othernames' => $data['othernames'] ?? null,
                        'staff_id' => $data['staff_id'] ?? $data['username'],
                        'department_id' => $data['department_id'] ?? null,
                        'phone_number' => $data['phone_number'] ?? null,
                    ])->save();
                }
            } else {
                $user = User::query()->create([
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'type' => 'teacher',
                    'user_secret' => Str::random(64),
                    'active' => (bool) ($data['active'] ?? true),
                ]);

                $teacher = Teacher::query()->create([
                    'user_id' => $user->id,
                    'lastname' => $data['lastname'],
                    'othernames' => $data['othernames'] ?? null,
                    'staff_id' => $data['staff_id'] ?? $data['username'],
                    'department_id' => $data['department_id'] ?? null,
                    'phone_number' => $data['phone_number'] ?? null,
                    'password_reset_required' => true,
                    'is_onboarded' => false,
                ]);
            }

            return ['user' => $user, 'teacher' => $teacher];
        });
    }
}
