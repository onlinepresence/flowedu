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

    public function test_manual_forwarding_is_disabled_for_public_memos(): void
    {
        $sender = $this->createStaffMember('owner');
        $recipient1 = $this->createStaffMember('dean_of_students', ['nav_memos', 'forward_memo']);
        $recipient2 = $this->createStaffMember('librarian', ['nav_memos']);

        // Create a sent memo with public confidentiality
        $memo = Memo::query()->create([
            'title' => 'Public Policy Document',
            'content' => 'Public policy updates.',
            'sender_id' => $sender->id,
            'recipient_type' => 'user',
            'recipient_entity_id' => $recipient1->id,
            'status' => 'sent',
            'confidentiality_level' => 'public',
        ]);

        $this->withoutExceptionHandling();

        // Attempting to forward a public memo should fail
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        Livewire::actingAs($recipient1)
            ->test(MemoDetailPage::class, ['memo' => $memo])
            ->call('openForward');
    }

    public function test_sequential_signatory_enforcement_and_progression(): void
    {
        $secretary = $this->createStaffMember('secretary');
        $signatory1 = $this->createStaffMember('hod1', ['nav_memos']);
        $signatory2 = $this->createStaffMember('hod2', ['nav_memos']);

        // Create a memo with 2 signatories in order
        $memo = Memo::query()->create([
            'title' => 'Sequential Approval Test',
            'content' => 'Content here.',
            'sender_id' => $secretary->id,
            'recipient_type' => 'user',
            'recipient_entity_id' => $signatory2->id,
            'status' => 'pending_signature',
            'signing_user_id' => $signatory1->id,
            'confidentiality_level' => 'internal',
        ]);

        $memo->signatories()->create([
            'user_id' => $signatory1->id,
            'step_number' => 1,
            'status' => 'pending',
        ]);

        $memo->signatories()->create([
            'user_id' => $signatory2->id,
            'step_number' => 2,
            'status' => 'pending',
        ]);

        // Attempt sign by signatory 2 (should fail - not their turn)
        Livewire::actingAs($signatory2)
            ->test(MemoDetailPage::class, ['memo' => $memo])
            ->call('signMemo')
            ->assertHasErrors(['signature_remarks']);

        $this->assertEquals('pending_signature', $memo->fresh()->status);

        // Sign by signatory 1 (should succeed and advance signing_user_id to signatory 2)
        Livewire::actingAs($signatory1)
            ->test(MemoDetailPage::class, ['memo' => $memo])
            ->call('signMemo')
            ->assertRedirect(route('admin.memos.show', $memo->id));

        $memo->refresh();
        $this->assertEquals('pending_signature', $memo->status);
        $this->assertEquals($signatory2->id, $memo->signing_user_id);

        // Sign by signatory 2 (should succeed and dispatch/send memo)
        Livewire::actingAs($signatory2)
            ->test(MemoDetailPage::class, ['memo' => $memo])
            ->call('signMemo')
            ->assertRedirect(route('admin.memos.show', $memo->id));

        $memo->refresh();
        $this->assertEquals('sent', $memo->status);
        $this->assertNull($memo->signing_user_id);
    }

    public function test_memo_department_isolation_blocks_unauthorized_users(): void
    {
        $secretary = $this->createStaffMember('secretary');
        
        // Enable department isolation setting
        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'memo_settings.department_isolation'],
            [
                'setting_value' => '1',
                'category' => 'memo_settings',
                'data_type' => 'boolean',
            ]
        );

        $faculty = \App\Models\Faculty::create(['name' => 'Sciences']);
        $dept1 = \App\Models\Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);
        $dept2 = \App\Models\Department::create(['name' => 'Mechanical Eng', 'faculty_id' => $faculty->id]);

        $sender = $this->createStaffMember('sender');
        $sender->admin->update(['department_id' => $dept1->id]);

        $recipient = $this->createStaffMember('recipient');
        $recipient->admin->update(['department_id' => $dept1->id]);

        $unauthorized = $this->createStaffMember('unauthorized');
        $unauthorized->admin->update(['department_id' => $dept2->id]);

        $memo = Memo::query()->create([
            'title' => 'Isolated Department Memo',
            'content' => 'Sensitive dept content.',
            'sender_id' => $sender->id,
            'sender_entity_type' => 'department',
            'sender_entity_id' => $dept1->id,
            'recipient_type' => 'user',
            'recipient_entity_id' => $unauthorized->id,
            'status' => 'sent',
            'confidentiality_level' => 'internal',
        ]);

        // Sender (dept 1) can view
        $this->assertTrue($memo->canBeViewedBy($sender));

        // Unauthorized user (recipient, but in different department dept 2) cannot view when isolation is active
        $this->assertFalse($memo->canBeViewedBy($unauthorized));

        // Turn off isolation setting
        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'memo_settings.department_isolation'],
            ['setting_value' => '0']
        );

        // Unauthorized user can view now
        $this->assertTrue($memo->canBeViewedBy($unauthorized));
    }

    public function test_memo_resubmission_and_cc_features(): void
    {
        $secretary = $this->createStaffMember('secretary');
        $sig1 = $this->createStaffMember('sig1', ['nav_memos']);
        $sig2 = $this->createStaffMember('sig2', ['nav_memos']);
        $ccUser = $this->createStaffMember('cc_staff', ['nav_memos']);

        // Enable multiple signatories setting
        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'system_preferences.memos_multiple_signatories'],
            [
                'setting_value' => '1',
                'category' => 'system_preferences',
                'data_type' => 'boolean',
            ]
        );

        // Create a draft memo first with CC configurations
        $memo = Memo::query()->create([
            'title' => 'Resubmission and CC Test',
            'content' => 'Original Draft content.',
            'sender_id' => $secretary->id,
            'recipient_type' => 'user',
            'recipient_entity_id' => $sig2->id,
            'status' => 'draft',
            'confidentiality_level' => 'internal',
            'cc_recipients' => [
                'users' => [$ccUser->id],
                'departments' => [],
                'roles' => [],
            ],
        ]);

        $memo->signatories()->create([
            'user_id' => $sig1->id,
            'step_number' => 1,
            'status' => 'signed',
            'signed_at' => now(),
        ]);

        $memo->signatories()->create([
            'user_id' => $sig2->id,
            'step_number' => 2,
            'status' => 'rejected',
            'remarks' => 'Typo in title.',
        ]);

        // Verify that the CC recipient is authorized to view
        $this->assertTrue($memo->canBeViewedBy($ccUser));

        // Test resubmission choice: resume (keeps sig1 as signed, sets sig2 to pending)
        Livewire::actingAs($secretary)
            ->test(MemoDetailPage::class, ['memo' => $memo])
            ->set('isEditing', true)
            ->set('editTitle', 'Resubmission and CC Test Fixed')
            ->set('editContent', 'Updated Draft content.')
            ->set('resubmissionChoice', 'resume')
            ->call('resubmitMemo', 'send');

        $memo->refresh();
        $this->assertSame('pending_signature', $memo->status);
        $this->assertSame('Resubmission and CC Test Fixed', $memo->title);
        // Verify signatories status: sig1 should remain signed, sig2 should be pending
        $this->assertSame('signed', $memo->signatories()->where('user_id', $sig1->id)->first()->status);
        $this->assertSame('pending', $memo->signatories()->where('user_id', $sig2->id)->first()->status);

        // Reset to rejected/draft status for restart test
        $memo->update(['status' => 'draft']);
        $memo->signatories()->where('user_id', $sig2->id)->update(['status' => 'rejected']);

        // Test resubmission choice: restart (resets both to pending)
        Livewire::actingAs($secretary)
            ->test(MemoDetailPage::class, ['memo' => $memo])
            ->set('isEditing', true)
            ->set('editTitle', 'Resubmission Restarted')
            ->set('editContent', 'Updated Draft content again.')
            ->set('editSelectedSignatories', [$sig1->id, $sig2->id])
            ->set('resubmissionChoice', 'restart')
            ->call('resubmitMemo', 'send');

        $memo->refresh();
        $this->assertSame('pending_signature', $memo->status);
        $this->assertSame('Resubmission Restarted', $memo->title);
        // Both signatories must now be pending
        $this->assertSame('pending', $memo->signatories()->where('user_id', $sig1->id)->first()->status);
        $this->assertSame('pending', $memo->signatories()->where('user_id', $sig2->id)->first()->status);
    }

    public function test_memo_edit_attachments_and_signatories_persistence(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $secretary = $this->createStaffMember('secretary');
        $sig1 = $this->createStaffMember('sig1', ['nav_memos']);
        $ccUser = $this->createStaffMember('cc_staff', ['nav_memos']);

        // Create a draft memo first
        $memo = Memo::query()->create([
            'title' => 'Initial Title',
            'content' => 'Initial Content',
            'sender_id' => $secretary->id,
            'recipient_type' => 'user',
            'recipient_entity_id' => $sig1->id,
            'status' => 'draft',
            'confidentiality_level' => 'internal',
            'cc_recipients' => [
                'users' => [$ccUser->id],
                'departments' => [],
                'roles' => [],
            ],
        ]);

        // Add an existing attachment to the memo
        $existingAttachment = $memo->attachments()->create([
            'file_path' => 'memos/old_file.txt',
            'file_name' => 'old_file.txt',
            'file_size' => 1024,
        ]);
        \Illuminate\Support\Facades\Storage::disk('local')->put('memos/old_file.txt', 'old content');

        // Create a signatory
        $memo->signatories()->create([
            'user_id' => $sig1->id,
            'step_number' => 1,
            'status' => 'pending',
        ]);

        // Verify setup
        $this->assertCount(1, $memo->attachments);
        $this->assertCount(1, $memo->signatories);

        $newFile = \Illuminate\Http\UploadedFile::fake()->create('new_file.png', 500);

        // Edit via Livewire
        Livewire::actingAs($secretary)
            ->test(MemoDetailPage::class, ['memo' => $memo])
            ->assertSet('editSelfSign', false)
            ->assertSet('editSelectedSignatories', [$sig1->id])
            ->set('isEditing', true)
            ->set('editTitle', 'Updated Title')
            ->set('editContent', 'Updated Content')
            ->upload('attachments', [$newFile])
            ->call('stageAttachmentDeletion', $existingAttachment->id)
            ->call('resubmitMemo', 'draft');

        $memo->refresh();
        // Title and content should be updated
        $this->assertSame('Updated Title', $memo->title);
        $this->assertSame('Updated Content', $memo->content);

        // Signatories should be persistent (still present)
        $this->assertCount(1, $memo->signatories);
        $this->assertSame($sig1->id, $memo->signatories()->first()->user_id);

        // Old attachment should be deleted and new one added
        $this->assertCount(1, $memo->attachments);
        $newAttachment = $memo->attachments()->first();
        $this->assertSame('new_file.png', $newAttachment->file_name);
        $this->assertNotEquals($existingAttachment->id, $newAttachment->id);

        // Verify files in storage
        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing('memos/old_file.txt');
        \Illuminate\Support\Facades\Storage::disk('tmp-for-tests')->assertExists($newAttachment->file_path);
    }
}
