<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SchoolLicence;
use App\Models\Setting;
use App\Support\EnvWriter;
use Illuminate\Console\Command;

/**
 * Reset licence state back to pre-activation: unlinks the deployment,
 * voids any demo key, clears cached/pending licence material, and (full
 * reset) drops the licence rows so setup shows activation again.
 *
 * Business data is NEVER touched — only licence identity and key
 * material. Consumed Desk codes stay consumed: re-enrollment needs a
 * fresh code, and the old Desk deployment should be revoked if it must
 * not linger (it otherwise goes stale on its own).
 */
final class LicenceResetCommand extends Command
{
    protected $signature = 'licence:reset
        {--demo-only : Void only the demo key, keep any deployment link}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Reset licence state to pre-activation (unlink + void demo). Business data untouched.';

    /**
     * Licence-related settings rows cleared on reset.
     *
     * @var list<string>
     */
    public const RESET_SETTINGS = [
        'controlplane.pending_code',
        'demo.cached_key_document',
        'demo.last_online_check',
        'demo.last_status',
        'demo.revoked_key_hash',
        'licence.last_central_grant',
    ];

    public function handle(): int
    {
        $demoOnly = (bool) $this->option('demo-only');

        if (! (bool) $this->option('force') && ! $this->confirm(
            $demoOnly
                ? 'Void the demo key on this install? The deployment link stays.'
                : 'Reset ALL licence state (unlink deployment, void demo, drop licence rows)? Business data stays, but re-enrollment needs a fresh code.'
        )) {
            $this->line('Aborted — nothing was changed.');

            return self::SUCCESS;
        }

        $envKeys = $demoOnly
            ? ['DEMO_KEY']
            : ['DEMO_KEY', 'DEPLOYMENT_UUID', 'CONTROL_PLANE_TOKEN'];

        $removed = EnvWriter::remove($envKeys);
        if (! ($removed['ok'] ?? false)) {
            $this->error('Could not edit '.($removed['path'] ?? '.env').' — remove '.implode(', ', $envKeys).' by hand, then re-run.');

            return self::FAILURE;
        }

        foreach ($envKeys as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
        }
        // Mirror into runtime config so later calls in this process see the
        // reset (.env files are only read at boot).
        foreach (['DEMO_KEY' => 'college.demo_key', 'DEPLOYMENT_UUID' => 'controlplane.deployment_uuid', 'CONTROL_PLANE_TOKEN' => 'controlplane.token'] as $env => $cfg) {
            if (in_array($env, $envKeys, true)) {
                config([$cfg => null]);
            }
        }

        $settingsCleared = Setting::query()->whereIn('setting_key', self::RESET_SETTINGS)->delete();

        $rowsDropped = 0;
        if (! $demoOnly) {
            $rowsDropped = SchoolLicence::query()->delete();
        }

        $this->info($demoOnly ? 'Demo key voided.' : 'Licence state reset to pre-activation.');
        $this->line('Removed .env keys: '.implode(', ', $envKeys).'.');
        $this->line("Cleared {$settingsCleared} licence setting(s).");
        if (! $demoOnly) {
            $this->line("Dropped {$rowsDropped} licence row(s).");
        }
        $this->warn('Heads up: consumed Desk codes stay consumed — re-enroll with a fresh code, and revoke the old Desk deployment if it must not linger. Sign out/in to drop any demo browser session.');

        return self::SUCCESS;
    }
}
