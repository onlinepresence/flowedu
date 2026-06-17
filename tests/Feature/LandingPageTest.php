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
