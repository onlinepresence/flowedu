<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\School;
use App\Services\ControlPlane\ControlPlaneClient;
use App\Services\ControlPlane\LicenceEnrollmentService;
use Illuminate\Console\Command;

final class ControlPlanePingCommand extends Command
{
    protected $signature = 'controlplane:ping {--redeem= : Enrollment code to redeem without the wizard}';

    protected $description = 'Heartbeat the ControlDesk control plane (and optionally redeem a code).';

    public function handle(ControlPlaneClient $client, LicenceEnrollmentService $enrollment): int
    {
        $redeem = trim((string) $this->option('redeem'));
        if ($redeem !== '') {
            $result = $enrollment->redeem($redeem);

            if (! ($result['ok'] ?? false)) {
                $this->error((string) ($result['message'] ?? __('Redemption failed.')));

                return self::FAILURE;
            }

            $this->info(__('Code redeemed and licence seeded.'));

            if (! empty($result['manual_lines'])) {
                $this->warn(__('Settings file could not be written. Paste these exact lines into :path:', ['path' => $result['path'] ?? \base_path('.env')]));
                foreach ($result['manual_lines'] as $line) {
                    $this->line($line);
                }
            }

            return self::SUCCESS;
        }

        $uuid = trim((string) config('controlplane.deployment_uuid'));
        $token = (string) config('controlplane.token', '');

        if ($uuid === '') {
            $this->error(__('No DEPLOYMENT_UUID configured. Redeem a code first (--redeem CODE).'));

            return self::FAILURE;
        }

        $result = $client->ping($uuid, $token);

        if (($result['status'] ?? null) !== 'ok') {
            $this->error((string) ($result['message'] ?? __('Heartbeat failed.')));

            return self::FAILURE;
        }

        $this->info(__('Heartbeat ok.'));

        // A heartbeat carrying a snapshot is central truth: provisional rows
        // are overwritten on first success.
        if (isset($result['snapshot']) && is_array($result['snapshot'])) {
            $school = School::current();
            if ($school !== null) {
                $enrollment->applySnapshot($school, $result['snapshot']);
                $enrollment->clearPendingCode();
                $this->info(__('Licence row synced from central truth.'));
            }
        }

        return self::SUCCESS;
    }
}
