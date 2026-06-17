<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\GhanaCardNumber;
use App\Rules\GhanaMobilePhone;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GhanaValidationRulesTest extends TestCase
{
    public static function validGhanaCards(): array
    {
        return [
            ['GHA-123456789-1'],
        ];
    }

    #[DataProvider('validGhanaCards')]
    public function test_ghana_card_accepts_valid_format(string $value): void
    {
        $v = Validator::make(['c' => $value], ['c' => [new GhanaCardNumber]]);

        $this->assertTrue($v->passes());
    }

    public function test_ghana_card_rejects_invalid(): void
    {
        $v = Validator::make(['c' => 'GHA-123-4'], ['c' => [new GhanaCardNumber]]);

        $this->assertFalse($v->passes());
    }

    public function test_ghana_mobile_accepts_known_prefix(): void
    {
        $v = Validator::make(['p' => '0241234567'], ['p' => [new GhanaMobilePhone]]);

        $this->assertTrue($v->passes());
    }

    public function test_ghana_mobile_rejects_bad_prefix(): void
    {
        $v = Validator::make(['p' => '0111234567'], ['p' => [new GhanaMobilePhone]]);

        $this->assertFalse($v->passes());
    }

    public function test_ghana_mobile_rejects_wrong_length(): void
    {
        $v = Validator::make(['p' => '02412345'], ['p' => [new GhanaMobilePhone]]);

        $this->assertFalse($v->passes());
    }
}
