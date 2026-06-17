<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\NavigationLicenceService;
use App\Services\SchoolLicenceService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NavigationLicenceServiceTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string|null}>
     */
    public static function adminPathFeatureProvider(): iterable
    {
        yield 'finance prefix' => ['/admin/finance/fees', 'finance'];
        yield 'reports prefix' => ['/admin/reports/payments', 'reports'];
        yield 'evaluations path' => ['/admin/staff/evaluations', 'evaluations'];
        yield 'staff prefix' => ['/admin/staff/teachers', 'staff_hr'];
        yield 'student professional promotion' => ['/admin/students/promotion', 'progression'];
        yield 'settings roles' => ['/admin/settings/roles', 'system_admin'];
        yield 'settings backup' => ['/admin/settings/backup', 'system_admin'];
        yield 'env generator' => ['/env-generator', 'system_admin'];
        yield 'dashboard' => ['/admin/dashboard', null];
        yield 'tools path legacy parity' => ['/tools/passport-validator', null];
    }

    /**
     * @return iterable<string, array{0: string, 1: string|null}>
     */
    public static function studentPathFeatureProvider(): iterable
    {
        yield 'evaluation' => ['/student/evaluation', 'evaluations'];
        yield 'fees' => ['/student/fees', 'finance'];
        yield 'payment history' => ['/student/payment-history', 'finance'];
        yield 'allowance' => ['/student/allowance', 'finance'];
        yield 'clearance' => ['/student/clearance', 'student_welfare'];
        yield 'medical' => ['/student/medical', 'student_welfare'];
        yield 'discipline' => ['/student/discipline', 'student_welfare'];
        yield 'dashboard' => ['/student/dashboard', null];
    }

    #[DataProvider('adminPathFeatureProvider')]
    public function test_feature_for_admin_path_matches_legacy_rules(string $path, ?string $expectedFeature): void
    {
        $licence = $this->createStub(SchoolLicenceService::class);
        $nav = new NavigationLicenceService($licence);

        $this->assertSame($expectedFeature, $nav->featureForAdminPath($path));
    }

    #[DataProvider('studentPathFeatureProvider')]
    public function test_feature_for_student_path_matches_legacy_rules(string $path, ?string $expectedFeature): void
    {
        $licence = $this->createStub(SchoolLicenceService::class);
        $nav = new NavigationLicenceService($licence);

        $this->assertSame($expectedFeature, $nav->featureForStudentPath($path));
    }

    public function test_filter_admin_nav_hides_finance_when_licence_denies_finance(): void
    {
        $licence = $this->createMock(SchoolLicenceService::class);
        $licence->method('can')->willReturnCallback(fn (string $f): bool => $f !== 'finance');

        $nav = new NavigationLicenceService($licence);

        $items = $nav->filterAdminNavItems([
            [
                'label' => 'Finance',
                'children' => [
                    ['label' => 'Fees', 'route' => 'admin.finance.fees'],
                    ['label' => 'Payments', 'route' => 'admin.finance.payments'],
                ],
            ],
            ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
        ]);

        $this->assertCount(1, $items);
        $this->assertSame('Dashboard', $items[0]['label'] ?? null);
    }

    public function test_filter_student_nav_hides_finance_when_licence_denies_finance(): void
    {
        $licence = $this->createMock(SchoolLicenceService::class);
        $licence->method('can')->willReturnCallback(fn (string $f): bool => $f !== 'finance');

        $nav = new NavigationLicenceService($licence);

        $items = $nav->filterStudentNavItems([
            [
                'label' => 'Fees',
                'children' => [
                    ['label' => 'Fee Details', 'route' => 'student.fees.index'],
                ],
            ],
            ['label' => 'Dashboard', 'route' => 'student.dashboard'],
        ]);

        $this->assertCount(1, $items);
        $this->assertSame('Dashboard', $items[0]['label'] ?? null);
    }
}
