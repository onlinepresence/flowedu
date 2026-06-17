<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Settings\BackupIndex;
use App\Models\Admin;
use App\Models\Backup;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Backup\DatabaseBackupService;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\Support\FakeDatabaseBackupService;
use Tests\TestCase;

class BackupIndexPageTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private function actingOwnerAdmin(): User
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $roleId = UserRole::query()->where('name', 'owner')->value('id');
        $user = User::factory()->create([
            'type' => 'admin',
            'username' => 'backupadmin',
        ]);

        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = $roleId;
        $admin->save();

        return $user;
    }

    public function test_owner_admin_can_render_backup_livewire(): void
    {
        $user = $this->actingOwnerAdmin();

        Livewire::actingAs($user)
            ->test(BackupIndex::class)
            ->assertStatus(200)
            ->assertSee(__('Backup history'), false);
    }

    public function test_create_backup_uses_service_and_records_row(): void
    {
        $this->app->instance(DatabaseBackupService::class, new FakeDatabaseBackupService);

        $user = $this->actingOwnerAdmin();

        Livewire::actingAs($user)
            ->test(BackupIndex::class)
            ->call('createBackup');

        $this->assertSame(1, Backup::query()->count());
    }

    public function test_owner_can_download_backup_file(): void
    {
        $this->app->instance(DatabaseBackupService::class, new FakeDatabaseBackupService);

        $user = $this->actingOwnerAdmin();

        Livewire::actingAs($user)
            ->test(BackupIndex::class)
            ->call('createBackup');

        $backup = Backup::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.settings.backup.download', $backup))
            ->assertOk()
            ->assertHeader('content-disposition');
    }
}
