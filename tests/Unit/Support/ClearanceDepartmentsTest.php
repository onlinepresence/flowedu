<?php

namespace Tests\Unit\Support;

use App\Support\ClearanceDepartments;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearanceDepartmentsTest extends TestCase
{
    use RefreshDatabase;
    #[Test]
    public function sports_defaults_to_not_required(): void
    {
        config(['clearance.default_not_required_keys' => ['sports']]);
        $this->assertSame('not_required', ClearanceDepartments::defaultStatusForDepartment('sports'));
    }

    #[Test]
    public function library_defaults_to_pending(): void
    {
        $this->assertSame('pending', ClearanceDepartments::defaultStatusForDepartment('library'));
    }

    #[Test]
    public function allowed_keys_match_definitions(): void
    {
        $this->assertSame(array_keys(ClearanceDepartments::definitions()), ClearanceDepartments::allowedKeys());
    }
}
