<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

// TEMPORARY: covers the SMTP smoke-test page; delete with MailTestController.

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class MailTestPageTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_guests_are_redirected(): void
    {
        $this->get(route('testing.mail'))->assertRedirect(route('login'));
    }

    public function test_non_admins_are_forbidden(): void
    {
        $user = User::factory()->create(['type' => 'student']);

        $this->actingAs($user)->get(route('testing.mail'))->assertForbidden();
    }

    public function test_admin_sees_form_and_can_send(): void
    {
        $owner = $this->actingOwnerAdmin();

        $this->actingAs($owner)
            ->get(route('testing.mail'))
            ->assertOk()
            ->assertSee('Recipient');

        $this->actingAs($owner)
            ->get(route('testing.mail', ['to' => 'someone@example.test', 'message' => 'hello']))
            ->assertOk()
            ->assertSee('Sent via');
    }

    public function test_invalid_recipient_is_rejected(): void
    {
        $owner = $this->actingOwnerAdmin();

        $this->actingAs($owner)
            ->get(route('testing.mail', ['to' => 'not-an-email', 'message' => 'hello']))
            ->assertRedirect()
            ->assertSessionHasErrors('to');
    }
}
