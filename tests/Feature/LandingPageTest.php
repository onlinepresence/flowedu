<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\QuoteRequest;
use App\Mail\QuoteReceipt;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Set up the school context so EnsureSchoolBootstrap middleware passes
        $this->createTestSchool();
    }

    public function test_root_redirects_to_login_when_landing_is_disabled(): void
    {
        Config::set('landing.enabled', false);

        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_root_renders_landing_page_when_landing_is_enabled(): void
    {
        Config::set('landing.enabled', true);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('landing');
        $response->assertSee('Ghanaian colleges');
        $response->assertSee('Core Academic Licence');
    }

    public function test_quote_request_validation_fails_with_missing_fields(): void
    {
        $response = $this->postJson(route('quote-request'), []);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'errors' => [
                'college_name',
                'name',
                'role',
                'phone',
                'email',
                'student_band',
                'hosting_setup',
                'config_setup',
                'migration',
                'admin_training',
                'teacher_training',
                'onsite_training',
                'founding_client',
                'send_client_receipt',
            ],
        ]);
    }

    public function test_quote_request_sends_email_on_valid_submission(): void
    {
        Mail::fake();

        $payload = [
            'college_name'      => 'Accra College of Education',
            'name'              => 'Ebenezer Boateng',
            'role'              => 'Registrar',
            'phone'             => '0249100268',
            'email'             => 'ebenezer@accra.edu.gh',
            'student_band'      => '1001-2000',
            'modules'           => ['finance', 'evaluations'],
            'message'           => 'Please include local training details.',
            'hosting_setup'     => 'managed',
            'config_setup'      => '1',
            'migration'         => '0',
            'admin_training'    => 2,
            'teacher_training'  => 3,
            'onsite_training'   => 1,
            'founding_client'   => '1',
            'send_client_receipt' => '1',
        ];

        $response = $this->postJson(route('quote-request'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Thank you. We have sent a copy of your Proforma Invoice to your email.',
            'download_url' => route('quote.download-pdf'),
        ]);

        // Assert session has the data saved
        $this->assertTrue(session()->has('last_quote'));
        $quoteSession = session()->get('last_quote');
        $this->assertEquals($payload['college_name'], $quoteSession['contact']['college_name']);

        // Assert QuoteRequest (admin notification) was sent with correct details
        Mail::assertSent(QuoteRequest::class, function (QuoteRequest $mail) use ($payload) {
            $adminEmail = config('mail.from.address', 'successinnovativehub@gmail.com');
            return $mail->hasTo($adminEmail) &&
                   $mail->data['college_name'] === $payload['college_name'] &&
                   $mail->data['name'] === $payload['name'] &&
                   !empty($mail->pdfData);
        });

        // Assert QuoteReceipt (client confirmation) was sent with correct details
        Mail::assertSent(QuoteReceipt::class, function (QuoteReceipt $mail) use ($payload) {
            return $mail->hasTo($payload['email']) &&
                   $mail->data['college_name'] === $payload['college_name'] &&
                   !empty($mail->pdfData);
        });
    }

    public function test_quote_request_sends_admin_email_only_when_receipt_flag_is_off(): void
    {
        Mail::fake();

        $payload = [
            'college_name'        => 'Cape Coast Technical University',
            'name'                => 'Kwame Asante',
            'role'                => 'ICT Director',
            'phone'               => '0243001234',
            'email'               => 'kwame@cctu.edu.gh',
            'student_band'        => '501-1000',
            'modules'             => ['finance'],
            'message'             => '',
            'hosting_setup'       => 'self_hosted',
            'config_setup'        => '0',
            'migration'           => '0',
            'admin_training'      => 0,
            'teacher_training'    => 0,
            'onsite_training'     => 0,
            'founding_client'     => '0',
            'send_client_receipt' => '0',
        ];

        $response = $this->postJson(route('quote-request'), $payload);

        $response->assertStatus(200)->assertJson(['success' => true]);

        // Admin notification MUST be sent
        Mail::assertSent(QuoteRequest::class);

        // Client receipt MUST NOT be sent
        Mail::assertNotSent(QuoteReceipt::class);

        // No download_url returned
        $this->assertNull($response->json('download_url'));
    }

    public function test_quote_request_dispatches_control_desk_lead(): void
    {
        config(['controlplane.url' => 'https://control.test']);
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Http::fake();

        $payload = [
            'college_name'      => 'Accra College of Education',
            'name'              => 'Ebenezer Boateng',
            'role'              => 'Registrar',
            'phone'             => '0249100268',
            'email'             => 'ebenezer@accra.edu.gh',
            'student_band'      => '501-1000',
            'modules'           => ['finance'],
            'hosting_setup'     => 'self_hosted',
            'config_setup'      => '0',
            'migration'         => '0',
            'admin_training'    => 0,
            'teacher_training'  => 0,
            'onsite_training'   => 0,
            'founding_client'   => '0',
            'send_client_receipt' => '0',
        ];

        $this->postJson(route('quote-request'), $payload)->assertStatus(200)->assertJson(['success' => true]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\PostLeadToControlDeskJob::class, function ($job) {
            return $job->lead['product_slug'] === 'flowedu'
                && $job->lead['contact']['email'] === 'ebenezer@accra.edu.gh'
                && $job->lead['contact']['college'] === 'Accra College of Education'
                && $job->lead['band'] === '501-1000'
                && $job->lead['modules'] === ['finance']
                && $job->lead['quote']['upfront'] > 0
                && $job->lead['quote']['renewal'] > 0
                && count($job->lead['quote']['lines']) > 0;
        });
    }

    public function test_quote_request_succeeds_when_lead_endpoint_404s(): void
    {
        config(['controlplane.url' => 'https://control.test']);
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Http::fake([
            '*/api/v1/leads' => \Illuminate\Support\Facades\Http::response([], 404),
        ]);

        $payload = [
            'college_name'      => 'Ho Technical University',
            'name'              => 'Ama Serwaa',
            'role'              => 'Registrar',
            'phone'             => '0249100268',
            'email'             => 'ama@htu.edu.gh',
            'student_band'      => '1-500',
            'hosting_setup'     => 'self_hosted',
            'config_setup'      => '0',
            'migration'         => '0',
            'admin_training'    => 0,
            'teacher_training'  => 0,
            'onsite_training'   => 0,
            'founding_client'   => '0',
            'send_client_receipt' => '0',
        ];

        // Sync driver runs the job inline; the 404 fast-fails inside it.
        $this->postJson(route('quote-request'), $payload)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Admin email fallback still fires.
        \Illuminate\Support\Facades\Mail::assertSent(QuoteRequest::class);
    }

    public function test_quote_request_rejects_failed_turnstile_when_configured(): void
    {
        config(['captcha.turnstile_site_key' => 'site-key']);
        config(['captcha.turnstile_secret_key' => 'secret-key']);
        \Illuminate\Support\Facades\Http::fake([
            'challenges.cloudflare.com/*' => \Illuminate\Support\Facades\Http::response(['success' => false], 200),
        ]);

        $this->postJson(route('quote-request'), ['cf-turnstile-response' => 'bogus'])
            ->assertStatus(422)
            ->assertJsonPath('errors.cf-turnstile-response.0', 'Spam check failed. Please confirm you are human and try again.');
    }

    public function test_quote_request_passes_turnstile_with_valid_token(): void
    {
        config(['captcha.turnstile_site_key' => 'site-key']);
        config(['captcha.turnstile_secret_key' => 'secret-key']);
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Http::fake([
            'challenges.cloudflare.com/*' => \Illuminate\Support\Facades\Http::response(['success' => true], 200),
        ]);

        $payload = [
            'college_name'      => 'Accra College of Education',
            'name'              => 'Ebenezer Boateng',
            'role'              => 'Registrar',
            'phone'             => '0249100268',
            'email'             => 'ebenezer@accra.edu.gh',
            'student_band'      => '1-500',
            'hosting_setup'     => 'self_hosted',
            'config_setup'      => '0',
            'migration'         => '0',
            'admin_training'    => 0,
            'teacher_training'  => 0,
            'onsite_training'   => 0,
            'founding_client'   => '0',
            'send_client_receipt' => '0',
            'cf-turnstile-response' => 'valid-token',
        ];

        $this->postJson(route('quote-request'), $payload)->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_download_pdf_redirects_when_session_empty(): void
    {
        $response = $this->get(route('quote.download-pdf'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }

    public function test_download_pdf_success_when_session_populated(): void
    {
        $payload = [
            'college_name'      => 'Accra College of Education',
            'name'              => 'Ebenezer Boateng',
            'role'              => 'Registrar',
            'phone'             => '0249100268',
            'email'             => 'ebenezer@accra.edu.gh',
            'student_band'      => '1001-2000',
            'modules'           => ['finance', 'evaluations'],
            'message'           => 'Please include local training details.',
            'hosting_setup'     => 'managed',
            'config_setup'      => '1',
            'migration'         => '0',
            'admin_training'    => 2,
            'teacher_training'  => 3,
            'onsite_training'   => 1,
            'founding_client'   => '1',
            'send_client_receipt' => '1',
        ];

        // Populate session
        $pricing = \App\Services\QuoteCalculationService::calculate($payload);
        session()->put('last_quote', [
            'contact' => $payload,
            'pricing' => $pricing,
        ]);

        $response = $this->get(route('quote.download-pdf'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename=Proforma_Invoice_Accra_College_of_Education.pdf');
    }
}
