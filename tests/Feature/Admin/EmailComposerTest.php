<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Tools\EmailComposerPage;
use App\Mail\CollegeNotificationMail;
use App\Models\Admin;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class EmailComposerTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    private function configureMail(): void
    {
        config([
            'college.demo_mode' => false,
            'mail.default' => 'smtp',
            'mail.from.address' => 'school@example.test',
            'mail.mailers.smtp.host' => 'smtp.example.test',
            'mail.mailers.smtp.username' => 'user',
            'mail.mailers.smtp.password' => 'secret',
        ]);
    }

    private function actingSystemAdmin(): User
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $user = User::factory()->create(['type' => 'admin', 'username' => 'sysmail']);
        Admin::query()->create([
            'user_id' => $user->id,
            'lastname' => 'Sys',
            'othernames' => 'Admin',
            'type' => UserRole::query()->where('name', 'system_admin')->value('id'),
            'status' => 'active',
        ]);

        return $user;
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('tools.email'))->assertRedirect(route('login'));
    }

    public function test_page_is_missing_when_mail_not_configured(): void
    {
        $owner = $this->actingOwnerAdmin();

        // Testing env uses the array mailer: treated as unconfigured.
        $this->actingAs($owner)->get(route('tools.email'))->assertNotFound();
    }

    public function test_non_privileged_admin_is_forbidden(): void
    {
        $this->configureMail();
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $user = User::factory()->create(['type' => 'admin', 'username' => 'reg_mail']);
        Admin::query()->create([
            'user_id' => $user->id,
            'lastname' => 'Reg',
            'othernames' => 'User',
            'type' => UserRole::query()->where('name', 'registrar')->value('id'),
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('tools.email'))->assertForbidden();
    }

    public function test_owner_and_system_admin_can_compose_and_send_directly(): void
    {
        $this->configureMail();
        Mail::fake();

        foreach ([$this->actingOwnerAdmin(), $this->actingSystemAdmin()] as $user) {
            $this->actingAs($user)->get(route('tools.email'))->assertOk();

            Livewire::actingAs($user)
                ->test(EmailComposerPage::class)
                ->set('recipients', "one@example.test;\n two@example.test ,one@example.test")
                ->set('subject', 'Hello')
                ->set('htmlBody', '<p>Hi <strong>there</strong></p>')
                ->call('send')
                ->assertHasNoErrors();
        }

        Mail::assertSent(CollegeNotificationMail::class, 4);
        Mail::assertSent(CollegeNotificationMail::class, function (CollegeNotificationMail $mail): bool {
            return $mail->hasTo('one@example.test')
                && $mail->mailSubject === 'Hello'
                && $mail->htmlBody === '<p>Hi <strong>there</strong></p>';
        });
    }

    public function test_composer_fields_are_bound_directly_to_livewire(): void
    {
        $this->configureMail();
        $owner = $this->actingOwnerAdmin();

        $this->actingAs($owner)
            ->get(route('tools.email'))
            ->assertOk()
            ->assertSee('wire:model.live.debounce.400ms="recipients"', false)
            ->assertSee('wire:model.live.debounce.400ms="htmlBody"', false)
            ->assertDontSee("entangle('recipients')", false)
            ->assertDontSee("entangle('htmlBody')", false);
    }

    public function test_delivery_errors_are_shown_and_only_failed_recipients_remain(): void
    {
        $this->configureMail();

        $sent = Mockery::mock(PendingMail::class);
        $sent->shouldReceive('send')->once()->andReturnNull();

        $failed = Mockery::mock(PendingMail::class);
        $failed->shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP rejected the message.'));

        Mail::shouldReceive('to')->once()->with('good@example.test')->andReturn($sent);
        Mail::shouldReceive('to')->once()->with('bad@example.test')->andReturn($failed);

        $owner = $this->actingOwnerAdmin();

        Livewire::actingAs($owner)
            ->test(EmailComposerPage::class)
            ->set('recipients', 'good@example.test, bad@example.test')
            ->set('subject', 'Hello')
            ->set('htmlBody', '<p>Hi</p>')
            ->call('send')
            ->assertHasErrors(['delivery'])
            ->assertSet('deliveredCount', 1)
            ->assertSet('recipients', 'bad@example.test')
            ->assertSet('deliveryFailures', ['bad@example.test' => 'SMTP rejected the message.'])
            ->assertSee('Some emails could not be sent.')
            ->assertSee('SMTP rejected the message.');
    }

    public function test_invalid_and_excessive_recipients_rejected(): void
    {
        $this->configureMail();

        $owner = $this->actingOwnerAdmin();

        Livewire::actingAs($owner)
            ->test(EmailComposerPage::class)
            ->set('recipients', 'not-an-email')
            ->set('subject', 'Hello')
            ->set('htmlBody', '<p>Hi</p>')
            ->call('send')
            ->assertHasErrors(['recipients']);

        $many = implode(',', array_map(fn (int $i): string => "u{$i}@example.test", range(1, 51)));
        Livewire::actingAs($owner)
            ->test(EmailComposerPage::class)
            ->set('recipients', $many)
            ->set('subject', 'Hello')
            ->set('htmlBody', '<p>Hi</p>')
            ->call('send')
            ->assertHasErrors(['recipients']);
    }

    public function test_nav_item_only_shows_when_configured(): void
    {
        $owner = $this->actingOwnerAdmin();

        // Unconfigured (array mailer in tests): hidden.
        $this->actingAs($owner)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Email composer');

        // Configured: visible.
        $this->configureMail();
        $this->actingAs($owner)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Email composer');
    }
}
