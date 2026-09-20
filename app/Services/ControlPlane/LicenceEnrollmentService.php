<?php

declare(strict_types=1);

namespace App\Services\ControlPlane;

use App\Models\School;
use App\Models\SchoolLicence;
use App\Models\Setting;
use App\Support\EnvWriter;
use Illuminate\Support\Facades\Log;

/**
 * Licence enrollment for the setup wizard gate + daily redemption retry.
 *
 * Three exits: redeem a code against ControlDesk, continue offline
 * (provisional core-only row, optional pending code for later retry), or
 * import a signed licence file. Snapshots (from redeem, import, or a
 * heartbeat that carries one) seed school_licences with
 * external_ref = deployment UUID.
 */
final class LicenceEnrollmentService
{
    public const PENDING_CODE_KEY = 'controlplane.pending_code';

    public function __construct(
        private readonly ControlPlaneClient $client,
        private readonly LicenceFileVerifier $verifier,
    ) {}

    /**
     * Linked = a deployment UUID is configured AND the licence row carries it.
     * Existing installed schools (neither) behave exactly as today.
     */
    public function choiceState(?School $school = null): string
    {
        $school ??= School::current();

        if ($this->isLinked($school)) {
            return 'linked';
        }

        $row = $school?->licence()->first();
        if ($row !== null && (bool) $row->provisional) {
            return 'provisional';
        }

        if ($school !== null && $school->ready) {
            return 'legacy';
        }

        return 'pending';
    }

    /**
     * Trial-with-real-data detection for the elevation screen: a school row
     * exists plus real records beyond the installing owner account (extra
     * users or any students).
     */
    public function hasExistingData(?School $school = null): bool
    {
        $school ??= School::current();
        if ($school === null) {
            return false;
        }

        if (\App\Models\Student::query()->exists()) {
            return true;
        }

        return \App\Models\User::query()->count() > 1;
    }

    public function isLinked(?School $school = null): bool
    {
        $uuid = trim((string) config('controlplane.deployment_uuid'));
        if ($uuid === '') {
            return false;
        }

        $school ??= School::current();
        $ref = $school?->licence()->value('external_ref');

        return is_string($ref) && trim($ref) === $uuid;
    }

    /**
     * Exit (a): redeem a code. Returns ['ok'=>true] or
     * ['ok'=>false,'message'=>..., 'manual_lines'=>[...]?].
     */
    public function redeem(string $code, ?School $school = null): array
    {
        $school ??= School::current();
        if ($school === null) {
            return ['ok' => false, 'message' => __('Set up the school profile first, then redeem the code.')];
        }

        $code = trim($code);
        if ($code === '') {
            return ['ok' => false, 'message' => __('Enter the enrollment code ops gave you.')];
        }

        $result = $this->client->enroll($code);
        if (($result['status'] ?? null) !== 'ok') {
            return ['ok' => false, 'message' => (string) ($result['message'] ?? __('Enrollment failed.'))];
        }

        $this->applySnapshot($school, $result['snapshot']);

        $env = EnvWriter::write([
            'DEPLOYMENT_UUID' => (string) $result['snapshot']['deployment_uuid'],
            'CONTROL_PLANE_TOKEN' => (string) ($result['snapshot']['control_plane_token'] ?? ''),
        ]);

        $this->clearPendingCode();

        if (! $env['ok']) {
            // Row is seeded; only the .env persistence failed — show the
            // exact lines so ops can paste them manually. Never a dead end.
            return ['ok' => true, 'manual_lines' => $env['lines'], 'path' => $env['path']];
        }

        return ['ok' => true];
    }

    /**
     * Exit (b): continue offline with a provisional core-only row. An
     * optional code is parked for the daily job to redeem silently later.
     */
    public function continueOffline(?School $school = null, string $code = ''): array
    {
        $school ??= School::current();
        if ($school === null) {
            return ['ok' => false, 'message' => __('Set up the school profile first.')];
        }

        $fields = [
            'max_active_students' => null,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => 'Provisional offline licence — pending ControlDesk redemption.',
            'external_ref' => null,
            'licence_key' => null,
            'provisional' => true,
        ];

        foreach (config('licence.core_features', []) as $feat) {
            if (! ($feat['locked'] ?? false) && isset($feat['db_column'])) {
                $fields[$feat['db_column']] = true;
            }
        }

        foreach (config('licence.modules', []) as $feat) {
            if (isset($feat['db_column'])) {
                $fields[$feat['db_column']] = false;
            }
        }

        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], $fields);

        $code = trim($code);
        if ($code !== '') {
            Setting::query()->updateOrCreate(
                ['setting_key' => self::PENDING_CODE_KEY],
                ['setting_value' => $code, 'data_type' => 'string', 'category' => 'licence',
                    'description' => 'Pending ControlDesk enrollment code for silent retry.'],
            );
        }

        app(\App\Services\SchoolLicenceService::class)->refresh();

        return ['ok' => true, 'pending' => $code !== ''];
    }

    /**
     * Exit (c): import a signed licence file blob, then seed like redeem.
     */
    public function importFile(string $blob, ?School $school = null): array
    {
        $school ??= School::current();
        if ($school === null) {
            return ['ok' => false, 'message' => __('Set up the school profile first, then import the file.')];
        }

        $checked = $this->verifier->verify($blob);
        if (! ($checked['ok'] ?? false)) {
            return ['ok' => false, 'message' => (string) ($checked['error'] ?? __('That file could not be verified.'))];
        }

        $payload = $checked['payload'];
        $this->applySnapshot($school, $payload);

        $env = EnvWriter::write([
            'DEPLOYMENT_UUID' => (string) ($payload['deployment_uuid'] ?? ''),
            'CONTROL_PLANE_TOKEN' => (string) ($payload['control_plane_token'] ?? ''),
        ]);

        if (! $env['ok']) {
            return ['ok' => true, 'manual_lines' => $env['lines'], 'path' => $env['path']];
        }

        return ['ok' => true];
    }

    /**
     * Seed (or overwrite, on heartbeat) the licence row FROM a snapshot.
     * external_ref is always the deployment UUID; starts_at/expires_at map
     * to licence_start/support_until (and licence_end).
     */
    public function applySnapshot(School $school, array $snapshot): void
    {
        $lic = is_array($snapshot['licence'] ?? null) ? $snapshot['licence'] : [];

        $fields = [
            'max_active_students' => isset($lic['max_active_students']) ? (int) $lic['max_active_students'] : null,
            'licence_start' => $this->cleanDate($lic['starts_at'] ?? null) ?? now()->toDateString(),
            'licence_end' => $this->cleanDate($lic['expires_at'] ?? null),
            'support_until' => $this->cleanDate($lic['expires_at'] ?? null),
            'notes' => 'Enrolled via ControlDesk on '.now()->toDateString().'.',
            'external_ref' => (string) ($snapshot['deployment_uuid'] ?? ''),
            'licence_key' => null,
            'provisional' => false,
        ];

        $tier = (string) ($lic['package_tier'] ?? 'complete');
        if (! in_array($tier, ['core', 'professional', 'complete'], true)) {
            $tier = 'complete';
        }
        // NOTE: school_licences has no package_tier column (dropped when the
        // per-module flags landed), so the tier lives in notes. Never add a
        // 'package_tier' key to $fields.
        $fields['notes'] = 'Enrolled via ControlDesk on '.now()->toDateString().'. Package tier: '.$tier.'.';

        $coreFlags = is_array($lic['core'] ?? null) ? $lic['core'] : [];
        $moduleFlags = is_array($lic['modules'] ?? null) ? $lic['modules'] : [];

        foreach (config('licence.core_features', []) as $key => $feat) {
            if (($feat['locked'] ?? false) || ! isset($feat['db_column'])) {
                continue;
            }
            $fields[$feat['db_column']] = array_key_exists($key, $coreFlags)
                ? (bool) $coreFlags[$key]
                : (bool) ($feat['default'] ?? true);
        }

        foreach (config('licence.modules', []) as $key => $feat) {
            if (! isset($feat['db_column'])) {
                continue;
            }
            $fields[$feat['db_column']] = array_key_exists($key, $moduleFlags)
                ? (bool) $moduleFlags[$key]
                : (bool) ($feat['default'] ?? false);
        }

        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], $fields);

        app(\App\Services\SchoolLicenceService::class)->refresh();
    }

    /**
     * Daily silent retry: redeem the parked code whenever network appears.
     * No pending code (or no school) = quiet no-op, no network touched.
     */
    public function retryPending(): bool
    {
        $code = trim((string) Setting::query()->where('setting_key', self::PENDING_CODE_KEY)->value('setting_value'));
        if ($code === '') {
            return true;
        }

        $school = School::current();
        if ($school === null) {
            return true;
        }

        $result = $this->client->enroll($code);
        if (($result['status'] ?? null) !== 'ok') {
            Log::info('controlplane.pending-retry-deferred', ['status' => $result['status'] ?? 'unknown']);

            return true;
        }

        $this->applySnapshot($school, $result['snapshot']);

        EnvWriter::write([
            'DEPLOYMENT_UUID' => (string) $result['snapshot']['deployment_uuid'],
            'CONTROL_PLANE_TOKEN' => (string) ($result['snapshot']['control_plane_token'] ?? ''),
        ]);

        $this->clearPendingCode();

        Log::info('controlplane.pending-retry-redeemed', ['school_id' => $school->id]);

        return true;
    }

    public function clearPendingCode(): void
    {
        Setting::query()->where('setting_key', self::PENDING_CODE_KEY)->delete();
    }

    private function cleanDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
