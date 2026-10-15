<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\School;
use App\Services\ControlPlane\ControlPlaneClient;
use App\Services\ControlPlane\LicenceEnrollmentService;
use App\Services\DemoKeyVerifier;
use Illuminate\Console\Command;

final class ControlPlanePingCommand extends Command
{
    protected $signature = 'controlplane:ping {--redeem= : Enrollment code, demo key/document, or licence file to activate without the wizard}';

    protected $description = 'Heartbeat ControlDesk (linked installs), report a demo trial, or activate anything without the wizard.';

    public function handle(ControlPlaneClient $client, LicenceEnrollmentService $enrollment, DemoKeyVerifier $demo): int
    {
        $redeem = trim((string) $this->option('redeem'));
        if ($redeem !== '') {
            // Universal activation: claim codes, demo documents/codes and
            // licence files each route to their own flow.
            $result = $enrollment->activate($redeem);

            if (! ($result['ok'] ?? false)) {
                $this->error((string) ($result['message'] ?? __('Activation failed.')));

                return self::FAILURE;
            }

            $this->info(! empty($result['demo'])
                ? __('Demo accepted — full trial access.')
                : __('Code redeemed and licence seeded.'));

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

        if ($uuid !== '') {
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

        // No deployment link: a held demo trial is a legitimate state, not
        // an error — report it and force an online re-check so revocation
        // bites on demand too.
        if ($demo->isUnlocked()) {
            $reverify = $demo->reverifyIfDue(null, true);

            if (! $demo->isUnlocked()) {
                $this->error(DemoKeyVerifier::errorCopy((string) ($reverify['reason'] ?? DemoKeyVerifier::REASON_EXPIRED)));

                return self::FAILURE;
            }

            $this->info(__('Demo trial active — full access, no ControlDesk link.'));

            $status = $demo->demoStatus();
            if (is_array($status)) {
                if (! empty($status['never'])) {
                    $this->line(__('Valid: never expires.'));
                } elseif (! empty($status['expires_at'])) {
                    $this->line(__('Valid until: :date', ['date' => (string) $status['expires_at']]));
                }
                $host = $status['host'] ?? null;
                $hosts = is_array($host) ? array_values(array_filter(array_map('strval', $host))) : [];
                if (is_string($host) && trim($host) !== '') {
                    $hosts = [trim($host)];
                }
                $this->line($hosts === []
                    ? __('Host lock: none (any host).')
                    : __('Host lock: :hosts', ['hosts' => implode(', ', $hosts)]));
            }

            if (is_array($reverify)) {
                $this->line(($reverify['ok'] ?? false) === true
                    ? __('Online re-check: confirmed with ControlDesk.')
                    : DemoKeyVerifier::errorCopy((string) ($reverify['reason'] ?? '')));
            } else {
                $this->line(__('Online re-check: skipped (document only or offline) — the offline copy governs until expiry.'));
            }

            return self::SUCCESS;
        }

        $this->error(__('No DEPLOYMENT_UUID configured and no demo trial held. Activate first (--redeem CODE, key, or file).'));

        return self::FAILURE;
    }
}
