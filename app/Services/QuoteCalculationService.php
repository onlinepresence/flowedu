<?php

declare(strict_types=1);

namespace App\Services;

class QuoteCalculationService
{
    /**
     * Calculate the full quote details matching the landing page frontend calculator.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function calculate(array $data): array
    {
        $bandKey = $data['student_band'] ?? '1-500';
        $bands = config('licence.core_pricing', []);
        $band = $bands[$bandKey] ?? null;

        if (!$band || !empty($band['custom'])) {
            return [
                'is_custom' => true,
                'band_label' => '3,500+ Students',
                'currency' => config('licence.pricing.currency', 'GHS'),
            ];
        }

        $multipliers = config('licence.module_pricing.multipliers', []);
        $multiplier = (float) ($multipliers[$bandKey] ?? 1.0);

        $coreUpfront = (float) ($band['core_upfront'] ?? 0.0);
        $coreRenewal = (float) ($band['core_renewal'] ?? 0.0);

        // Founding client discount (15% off core)
        $applyFounding = !empty($data['founding_client']);
        $foundingDiscountRate = (float) config('licence.founding_client_discount', 0.15);
        $foundingDiscountUpfront = 0.0;
        $foundingDiscountRenew = 0.0;

        if ($applyFounding) {
            $foundingDiscountUpfront = $coreUpfront * $foundingDiscountRate;
            $foundingDiscountRenew = $coreRenewal * $foundingDiscountRate;
        }

        $coreUpfrontFinal = $coreUpfront - $foundingDiscountUpfront;
        $coreRenewalFinal = $coreRenewal - $foundingDiscountRenew;

        // Selected modules
        $modulesConfig = config('licence.modules', []);
        $selectedModulesKeys = $data['modules'] ?? [];
        $modulesOnetimeSum = 0.0;
        $modulesRenewSum = 0.0;
        $modulesDetails = [];

        foreach ($selectedModulesKeys as $key) {
            if (isset($modulesConfig[$key])) {
                $mConfig = $modulesConfig[$key];
                $basePrice = (float) ($mConfig['base_price'] ?? 0.0);
                $baseRenew = (float) ($mConfig['renewal_base'] ?? 0.0);

                $onetime = $basePrice * $multiplier;
                $renew = $baseRenew * $multiplier;

                $modulesOnetimeSum += $onetime;
                $modulesRenewSum += $renew;

                $modulesDetails[] = [
                    'key' => $key,
                    'label' => $mConfig['label'] ?? ucfirst($key),
                    'onetime' => $onetime,
                    'renew' => $renew,
                ];
            }
        }

        // Bundle discount (12% off when 4 or more modules selected)
        $bundleDiscountRate = (float) config('licence.bundle_discount', 0.12);
        $discountAmtOnetime = 0.0;
        $discountAmtRenew = 0.0;
        $applyBundle = count($selectedModulesKeys) >= 4;

        if ($applyBundle) {
            $discountAmtOnetime = $modulesOnetimeSum * $bundleDiscountRate;
            $discountAmtRenew = $modulesRenewSum * $bundleDiscountRate;
        }

        $modulesOnetimeFinal = $modulesOnetimeSum - $discountAmtOnetime;
        $modulesRenewFinal = $modulesRenewSum - $discountAmtRenew;

        // Server & Hosting Setup Fee
        $hostingRadio = $data['hosting_setup'] ?? 'self_hosted';
        $hostingSetupFee = 0.0;
        $hostingLabel = 'Self-Install (None)';
        if ($hostingRadio === 'self_hosted') {
            $hostingSetupFee = 1200.00;
            $hostingLabel = 'Self-Hosted Server Setup';
        } elseif ($hostingRadio === 'managed') {
            $hostingSetupFee = 1600.00;
            $hostingLabel = 'Managed Cloud Setup';
        }

        // Implementation configuration and data entry
        $configurationFee = 0.0;
        $addons = [];
        if (!empty($data['config_setup'])) {
            $configurationFee += 800.00;
            $addons[] = [
                'label' => 'System Configuration & Data Entry',
                'price' => 800.00,
            ];
        }
        if (!empty($data['migration'])) {
            $configurationFee += 2000.00;
            $addons[] = [
                'label' => 'Legacy Data Migration',
                'price' => 2000.00,
            ];
        }

        // Training
        $adminTrainQty = (int) ($data['admin_training'] ?? 0);
        $teacherTrainQty = (int) ($data['teacher_training'] ?? 0);
        $onsiteTrainQty = (int) ($data['onsite_training'] ?? 0);

        $trainingDetails = [];
        if ($adminTrainQty > 0) {
            $trainingDetails[] = [
                'label' => "Remote Admin Training ($adminTrainQty sessions)",
                'price' => $adminTrainQty * 600.00,
            ];
        }
        if ($teacherTrainQty > 0) {
            $trainingDetails[] = [
                'label' => "Remote Lecturer Training ($teacherTrainQty sessions)",
                'price' => $teacherTrainQty * 500.00,
            ];
        }
        if ($onsiteTrainQty > 0) {
            $trainingDetails[] = [
                'label' => "On-Site Training Days ($onsiteTrainQty days)",
                'price' => $onsiteTrainQty * 1500.00,
            ];
        }

        $trainingFeeSum = ($adminTrainQty * 600.00) + ($teacherTrainQty * 500.00) + ($onsiteTrainQty * 1500.00);

        // Final totals
        $upfrontTotal = $coreUpfrontFinal + $modulesOnetimeFinal + $hostingSetupFee + $configurationFee + $trainingFeeSum;
        $renewTotal = $coreRenewalFinal + $modulesRenewFinal;

        return [
            'is_custom' => false,
            'band_key' => $bandKey,
            'band_label' => $band['label'] ?? '',
            'multiplier' => $multiplier,
            
            // Core
            'core_upfront' => $coreUpfront,
            'core_renewal' => $coreRenewal,
            'core_upfront_final' => $coreUpfrontFinal,
            'core_renewal_final' => $coreRenewalFinal,
            'apply_founding' => $applyFounding,
            'founding_discount_upfront' => $foundingDiscountUpfront,
            'founding_discount_renew' => $foundingDiscountRenew,

            // Modules
            'modules' => $modulesDetails,
            'modules_onetime_sum' => $modulesOnetimeSum,
            'modules_renew_sum' => $modulesRenewSum,
            'modules_onetime_final' => $modulesOnetimeFinal,
            'modules_renew_final' => $modulesRenewFinal,
            'apply_bundle' => $applyBundle,
            'bundle_discount_onetime' => $discountAmtOnetime,
            'bundle_discount_renew' => $discountAmtRenew,

            // Server & Hosting
            'hosting_setup' => $hostingRadio,
            'hosting_label' => $hostingLabel,
            'hosting_setup_fee' => $hostingSetupFee,

            // Implementation & Addons
            'addons' => $addons,
            'configuration_fee' => $configurationFee,

            // Training
            'trainings' => $trainingDetails,
            'training_fee' => $trainingFeeSum,
            'admin_training_qty' => $adminTrainQty,
            'teacher_training_qty' => $teacherTrainQty,
            'onsite_training_qty' => $onsiteTrainQty,

            // Totals
            'upfront_total' => $upfrontTotal,
            'renew_total' => $renewTotal,
            'currency' => config('licence.pricing.currency', 'GHS'),
        ];
    }
}
