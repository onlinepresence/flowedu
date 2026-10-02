<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\MailSetup;
use Tests\TestCase;

class MailSetupTest extends TestCase
{
    public function test_detects_configured_smtp(): void
    {
        config([
            'college.demo_mode' => false,
            'mail.default' => 'smtp',
            'mail.from.address' => 'school@example.test',
            'mail.mailers.smtp.host' => 'smtp.example.test',
            'mail.mailers.smtp.username' => 'user',
            'mail.mailers.smtp.password' => 'secret',
        ]);

        $this->assertTrue(MailSetup::isConfigured());
    }

    public function test_rejects_log_and_array_mailers(): void
    {
        config(['college.demo_mode' => false, 'mail.from.address' => 'school@example.test']);

        config(['mail.default' => 'log']);
        $this->assertFalse(MailSetup::isConfigured());

        config(['mail.default' => 'array']);
        $this->assertFalse(MailSetup::isConfigured());
    }

    public function test_rejects_missing_smtp_credentials(): void
    {
        config([
            'college.demo_mode' => false,
            'mail.default' => 'smtp',
            'mail.from.address' => 'school@example.test',
            'mail.mailers.smtp.host' => 'smtp.example.test',
            'mail.mailers.smtp.username' => 'user',
            'mail.mailers.smtp.password' => '',
        ]);

        $this->assertFalse(MailSetup::isConfigured());
    }

    public function test_rejects_demo_mode_even_with_credentials(): void
    {
        config([
            'college.demo_mode' => true,
            'mail.default' => 'smtp',
            'mail.from.address' => 'school@example.test',
            'mail.mailers.smtp.host' => 'smtp.example.test',
            'mail.mailers.smtp.username' => 'user',
            'mail.mailers.smtp.password' => 'secret',
        ]);

        $this->assertFalse(MailSetup::isConfigured());
    }
}
