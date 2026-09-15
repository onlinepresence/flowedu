<?php

namespace Tests;

use App\Models\School;
use App\Models\UserRole;
use App\Services\SchoolLicenceService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset per-request memos so RefreshDatabase sees fresh state per test.
        School::forgetCurrentMemo();
        UserRole::forgetSystemRolesEnsured();
        if (app()->bound(SchoolLicenceService::class)) {
            app(SchoolLicenceService::class)->forgetMemo();
        }
    }

    protected function tearDown(): void
    {
        School::forgetCurrentMemo();
        UserRole::forgetSystemRolesEnsured();

        parent::tearDown();
    }
}
