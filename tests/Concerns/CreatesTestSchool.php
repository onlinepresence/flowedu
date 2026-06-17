<?php

namespace Tests\Concerns;

use App\Models\School;

trait CreatesTestSchool
{
    protected function createTestSchool(array $overrides = []): School
    {
        return School::create(array_merge([
            'name' => 'Test School',
            'address' => '123 Test Street',
            'is_admit' => true,
            'ready' => true,
        ], $overrides));
    }
}
