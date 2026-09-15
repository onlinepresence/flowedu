<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class School extends Model
{
    protected $fillable = [
        'name',
        'address',
        'email',
        'phone',
        'website',
        'description',
        'logo',
        'ready',
        'is_admit',
        'motto',
        'established_year',
        'principal_name',
        'facebook_url',
        'twitter_url',
        'linkedin_url',
        'instagram_url',
    ];

    protected function casts(): array
    {
        return [
            'ready' => 'boolean',
            'is_admit' => 'boolean',
        ];
    }

    /**
     * Single-school deployment: at most one row.
     *
     * Memoized per request: layout + middleware + licence service call this
     * 3-4x (up to 100x+ with nav filtering) per page. Reset per test via
     * Tests\TestCase setUp/tearDown; in Octane call forgetCurrentMemo() per request.
     */
    protected static ?self $currentMemo = null;

    protected static bool $currentMemoResolved = false;

    protected static function booted(): void
    {
        // Keep per-request memo coherent when school is created/updated mid-request
        // (e.g. setup wizard: empty DB -> created). Otherwise memoized null/old row
        // would stick for the rest of the request.
        static::saved(function (): void {
            static::forgetCurrentMemo();
        });
        static::deleted(function (): void {
            static::forgetCurrentMemo();
        });
    }

    public static function current(): ?self
    {
        if (static::$currentMemoResolved) {
            return static::$currentMemo;
        }

        $current = static::query()->first();

        // Only memoize a real row. Memoizing null would pin "no school" for the
        // rest of the request and break the setup wizard (empty DB -> created).
        if ($current !== null) {
            static::$currentMemo = $current;
            static::$currentMemoResolved = true;
        }

        return $current;
    }

    public static function forgetCurrentMemo(): void
    {
        static::$currentMemo = null;
        static::$currentMemoResolved = false;
    }

    public function licence(): HasOne
    {
        return $this->hasOne(SchoolLicence::class);
    }
}
