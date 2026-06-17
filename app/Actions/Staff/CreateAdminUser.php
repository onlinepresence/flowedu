<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CreateAdminUser
{
    /**
     * @param  array{
     *     name: string,
     *     username: string,
     *     email: string,
     *     password: string,
     *     lastname: string,
     *     othernames: string,
     *     phone_number?: string|null,
     *     gender?: string|null,
     *     position_title?: string|null,
     *     department_id?: int|null,
     *     faculty_id?: int|null,
     *     date_of_appointment?: string|null,
     *     ghana_card?: string|null,
     *     type?: int|null,
     *     active?: bool
     * }  $data
     * @return array{user: User, admin: Admin}
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'type' => 'admin',
                'user_secret' => Str::random(64),
                'active' => (bool) ($data['active'] ?? true),
            ]);

            $admin = Admin::query()->create([
                'user_id' => $user->id,
                'lastname' => $data['lastname'],
                'othernames' => $data['othernames'],
                'phone_number' => $data['phone_number'] ?? null,
                'gender' => $data['gender'] ?? null,
                'position_title' => $data['position_title'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'faculty_id' => $data['faculty_id'] ?? null,
                'date_of_appointment' => $data['date_of_appointment'] ?? null,
                'ghana_card' => $data['ghana_card'] ?? null,
                'type' => $data['type'] ?? null,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            return ['user' => $user, 'admin' => $admin];
        });
    }
}
