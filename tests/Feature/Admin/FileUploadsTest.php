<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserFileCategory;
use App\Models\UserUploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FileUploadsTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithPermission(bool $hasPermission): User
    {
        $permissions = $hasPermission ? ['manage_file_uploads'] : [];

        $role = UserRole::updateOrCreate(
            ['name' => 'guest_staff'],
            [
                'role_name' => 'staff',
                'display_name' => 'Guest Staff',
                'permissions' => $permissions,
            ]
        );

        $user = User::factory()->create(['type' => 'admin', 'username' => 'testuser']);
        Admin::forceCreate([
            'user_id' => $user->id,
            'type' => $role->id,
            'lastname' => 'Test',
            'status' => 'active',
        ]);

        return $user;
    }

    public function test_page_is_forbidden_for_user_without_permission(): void
    {
        $user = $this->createAdminWithPermission(false);

        $response = $this->actingAs($user)->get(route('admin.file-uploads'));
        $response->assertStatus(403);
    }

    public function test_page_is_accessible_for_user_with_permission(): void
    {
        $user = $this->createAdminWithPermission(true);

        $response = $this->actingAs($user)->get(route('admin.file-uploads'));
        $response->assertStatus(200);
    }

    public function test_user_can_manage_categories(): void
    {
        $user = $this->createAdminWithPermission(true);

        // 1. Create category
        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\FileUploadsPage::class)
            ->set('categoryName', 'Financial Documents')
            ->set('categoryDescription', 'Files related to financial transactions')
            ->call('saveCategory');

        $this->assertDatabaseHas('user_file_categories', [
            'user_id' => $user->id,
            'name' => 'Financial Documents',
            'description' => 'Files related to financial transactions',
        ]);

        $category = UserFileCategory::query()->where('user_id', $user->id)->first();

        // 2. Edit category
        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\FileUploadsPage::class)
            ->call('openEditCategory', $category->id)
            ->set('categoryName', 'Updated Financial Docs')
            ->call('saveCategory');

        $this->assertDatabaseHas('user_file_categories', [
            'id' => $category->id,
            'name' => 'Updated Financial Docs',
        ]);

        // 3. Delete category
        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\FileUploadsPage::class)
            ->call('confirmDeleteCategory', $category->id)
            ->call('deleteCategory');

        $this->assertDatabaseMissing('user_file_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_user_can_upload_files(): void
    {
        Storage::fake('college_uploads');
        $user = $this->createAdminWithPermission(true);

        $category = UserFileCategory::create([
            'user_id' => $user->id,
            'name' => 'Report Files',
        ]);

        $file = UploadedFile::fake()->create('report.pdf', 100);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\FileUploadsPage::class)
            ->set('fileTitle', 'Annual Audit Report')
            ->set('fileCategory', (string) $category->id)
            ->set('fileDescription', 'Audited files for 2026')
            ->set('fileUpload', $file)
            ->call('saveFile');

        $uploadedFile = UserUploadedFile::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($uploadedFile);
        $this->assertEquals('Annual Audit Report', $uploadedFile->title);
        $this->assertEquals('report.pdf', $uploadedFile->original_filename);
        
        Storage::disk('college_uploads')->assertExists($uploadedFile->file_path);

        // Test secure download
        $response = $this->actingAs($user)->get(route('admin.file-uploads.download', $uploadedFile->id));
        $response->assertStatus(200);
        $response->assertDownload('report.pdf');

        // Test delete file
        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\FileUploadsPage::class)
            ->call('confirmDeleteFile', $uploadedFile->id)
            ->call('deleteFile');

        $this->assertDatabaseMissing('user_uploaded_files', [
            'id' => $uploadedFile->id,
        ]);
        Storage::disk('college_uploads')->assertMissing($uploadedFile->file_path);
    }

    public function test_user_cannot_download_other_users_files(): void
    {
        Storage::fake('college_uploads');
        $user1 = $this->createAdminWithPermission(true);
        $user2 = $this->createAdminWithPermission(true);

        $file = UserUploadedFile::create([
            'user_id' => $user1->id,
            'category_id' => null,
            'title' => 'Secret User 1 Doc',
            'original_filename' => 'secret.txt',
            'file_path' => 'user-files/' . $user1->id . '/secret.txt',
            'file_size' => 10,
            'mime_type' => 'text/plain',
        ]);

        Storage::disk('college_uploads')->put($file->file_path, 'secret contents');

        // Try downloading as user 2
        $response = $this->actingAs($user2)->get(route('admin.file-uploads.download', $file->id));
        $response->assertStatus(403);
    }
}
