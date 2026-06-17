<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Memos\MemoDetailPage;
use App\Livewire\Admin\Memos\MemoIndexPage;
use App\Livewire\Admin\Settings\SettingsUserRolesPage;
use App\Models\Admin;
use App\Models\Memo;
use App\Models\MemoTracking;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class MemosModuleTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private function createStaffMember(string $roleName, array $permissions = []): User
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        // Ensure roles & permissions are registered
        UserRole::ensureSystemRoles();
        
        $role = UserRole::query()->where('name', $roleName)->first();
        if (! $role) {
            $role = UserRole::query()->create([
                'name' => $roleName,
                'display_name' => ucfirst($roleName),
                'role_name' => 'other',
                'permissions' => $permissions,
            ]);
        } else {
            $role->update(['permissions' => array_merge($role->permissions ?? [], $permissions)]);
        }

        $user = User::factory()->create([
            'type' => 'admin',
        ]);

        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = $role->id;
        $admin->save();

        return $user;
    }

    public function test_sync_roles_command_adds_all_ghanaian_coe_roles(): void
    {
        $this->createTestSchool();
        
        // Run sync-roles
        Artisan::call('college:sync-roles');

        $expectedRoles = [
            'principal',
            'vice_principal',
            'finance_officer',
            'dean_of_students',
            'librarian',
            'internal_auditor',
            'secretary',
        ];

        foreach ($expectedRoles as $role) {
            $this->assertTrue(
                UserRole::query()->where('name', $role)->exists(),
                "Role '{$role}' was not seeded."
            );
        }
    }

    public function test_owner_can_sync_roles_from_roles_page_button(): void
    {
        $owner = $this->createStaffMember('owner');

        Livewire::actingAs($owner)
            ->test(SettingsUserRolesPage::class)
            ->call('syncRoles')
            ->assertRedirect(route('admin.settings.roles'));
    }

    public function test_secretary_can_draft_memo_and_submit_to_hod_for_signature(): void
    {
        Notification::fake();

        $secretary = $this->createStaffMember('secretary', ['nav_memos', 'create_memo']);
        $hod = $this->createStaffMember('hod', ['nav_memos']);

        // Start Livewire memo test as Secretary
        Livewire::actingAs($secretary)
            ->test(MemoIndexPage::class)
            ->call('openCreate')
            ->set('title', 'Annual Financial Audit')
            ->set('content', 'Please review the audit reports.')
            ->set('recipient_type', 'user')
            ->set('recipient_entity_id', $hod->id)
            ->set('signing_user_id', $hod->id)
            ->call('saveMemo', 'send');

        // Assert memo exists with status 'pending_signature'
        $memo = Memo::query()->first();
        $this->assertNotNull($memo);
        $this->assertSame('Annual Financial Audit', $memo->title);
        $this->assertSame('pending_signature', $memo->status);
        $this->assertEquals($hod->id, $memo->signing_user_id);

        // Assert HOD received signature request notification
        Notification::assertSentTo(
            $hod,
            \App\Notifications\CollegeNotification::class,
            fn ($notification) => str_contains($notification->toArray($hod)['title'], 'Signature Request')
        );
    }

    public function test_hod_can_sign_and_dispatch_memo(): void
    {
        Notification::fake();

        $secretary = $this->createStaffMember('secretary');
        $hod = $this->createStaffMember('hod', ['nav_memos']);

        // Create a memo waiting for HOD signature
        $memo = Memo::query()->create([
            'title' => 'Important Faculty Memo',
            'content' => 'Content goes here.',
            'sender_id' => $secretary->id,
            'recipient_type' => 'user',
            'recipient_entity_id' => $hod->id,
            'status' => 'pending_signature',
            'signing_user_id' => $hod->id,
            'confidentiality_level' => 'internal',
        ]);

        // HOD views detail page and signs it
        Livewire::actingAs($hod)
            ->test(MemoDetailPage::class, ['memo' => $memo])
            ->call('signMemo')
            ->assertRedirect(route('admin.memos.show', $memo->id));

        $memo->refresh();
        $this->assertSame('sent', $memo->status);

        // Assert tracking log created for the signature action
        $log = MemoTracking::query()->where('memo_id', $memo->id)->where('action', 'signed')->first();
        $this->assertNotNull($log);
        $this->assertEquals($hod->id, $log->forwarded_by);

        // Assert Secretary received approval notification
        Notification::assertSentTo(
            $secretary,
            \App\Notifications\CollegeNotification::class,
            fn ($notification) => str_contains($notification->toArray($secretary)['title'], 'Memo Signed')
        );
    }

    public function test_manual_forwarding_and_remarks(): void
    {
        $sender = $this->createStaffMember('owner');
        $recipient1 = $this->createStaffMember('dean_of_students', ['nav_memos', 'forward_memo']);
        $recipient2 = $this->createStaffMember('librarian', ['nav_memos']);

        // Create a sent memo
        $memo = Memo::query()->create([
            'title' => 'Policy Document',
            'content' => 'Policy updates.',
            'sender_id' => $sender->id,
            'recipient_type' => 'user',
            'recipient_entity_id' => $recipient1->id,
            'status' => 'sent',
            'confidentiality_level' => 'internal',
        ]);

        // Recipient1 forwards to Recipient2
        Livewire::actingAs($recipient1)
            ->test(MemoDetailPage::class, ['memo' => $memo])
            ->call('openForward')
            ->set('forward_recipient_type', 'user')
            ->set('forward_recipient_entity_id', $recipient2->id)
            ->set('forward_remarks', 'Please display in library.')
            ->call('forwardMemo')
            ->assertRedirect(route('admin.memos.show', $memo->id));

        $memo->refresh();
        $this->assertSame('user', $memo->recipient_type);
        $this->assertEquals($recipient2->id, $memo->recipient_entity_id);

        // Verify log remarks
        $log = MemoTracking::query()->where('memo_id', $memo->id)->where('action', 'forwarded')->first();
        $this->assertNotNull($log);
        $this->assertSame('Please display in library.', $log->remarks);
    }
}
