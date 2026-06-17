<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\NonTeachingStaff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CreateNonTeachingStaffUser
{
    /**
     * @param  array{name: string, username: string, email: string, password: string, position: string, department_id: int, phone_number: string, status?: string, active?: bool}  $data
     * @return array{user: User, nonTeaching: NonTeachingStaff}
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'type' => 'staff',
                'user_secret' => Str::random(64),
                'active' => (bool) ($data['active'] ?? true),
            ]);

            $nonTeaching = NonTeachingStaff::query()->create([
                'user_id' => $user->id,
                'position' => $data['position'],
                'department_id' => $data['department_id'],
                'phone_number' => $data['phone_number'],
                'status' => $data['status'] ?? 'active',
            ]);

            return ['user' => $user, 'nonTeaching' => $nonTeaching];
        });
    }
}
