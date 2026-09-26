<?php

declare(strict_types=1);

namespace App\Services\ControlPlane;

use App\Models\School;
use App\Models\SchoolLicence;
use App\Models\Setting;
use App\Support\EnvWriter;
use Illuminate\Support\Facades\Log;

/**
 * Licence enrollment for the setup wizard gate + daily redemption retry +
 * daily heartbeat sync.
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

    public const LAST_GRANT_KEY = 'licence.last_central_grant';

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
        $fields = $this->snapshotFields($snapshot);

        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], $fields);

        $this->recordCentralGrant($snapshot);

        app(\App\Services\SchoolLicenceService::class)->refresh();
    }

    /**
     * Heartbeat merge: central truth with GRANT∧PREFERENCE for unlocked core
     * flags (effective = central AND local), modules frozen to the central
     * grant. Central false always wins; a local core-off survives a central
     * true; a central-off kills a local-on. Absent central core keys default
     * like applySnapshot, so central silence preserves the local value.
     */
    public function applyHeartbeatSnapshot(School $school, array $snapshot): void
    {
        $row = $school->licence()->first();
        $fields = $this->snapshotFields($snapshot);

        if ($row !== null) {
            foreach (config('licence.core_features', []) as $key => $feat) {
                if (($feat['locked'] ?? false) || ! isset($feat['db_column'])) {
                    continue;
                }
                $col = $feat['db_column'];
                $fields[$col] = (bool) ($fields[$col] ?? true) && (bool) $row->$col;
            }
        }

        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], $fields);

        $this->recordCentralGrant($snapshot);

        app(\App\Services\SchoolLicenceService::class)->refresh();
    }

    /**
     * Last known central grant per unlocked core key, for display truth
     * (plan badges). Prefers the recorded heartbeat/enrollment snapshot;
     * falls back to the local row, which linked installs seed from central.
     *
     * @return array<string, bool>
     */
    public function centralCoreGrants(?School $school = null): array
    {
        $grants = null;
        try {
            $raw = Setting::query()->where('setting_key', self::LAST_GRANT_KEY)->value('setting_value');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            $grants = is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            $grants = null;
        }

        $school ??= School::current();
        $row = $school?->licence()->first();

        $out = [];
        foreach (config('licence.core_features', []) as $key => $feat) {
            if (($feat['locked'] ?? false) || ! isset($feat['db_column'])) {
                continue;
            }
            if (is_array($grants) && array_key_exists($key, $grants)) {
                $out[$key] = (bool) $grants[$key];
            } elseif ($row !== null) {
                $col = $feat['db_column'];
                $out[$key] = (bool) $row->$col;
            } else {
                $out[$key] = (bool) ($feat['default'] ?? true);
            }
        }

        return $out;
    }

    /**
     * Daily heartbeat sync (scheduled 04:30, after the retry job): linked
     * installs only (UUID+token present). POSTs the heartbeat via the
     * existing ping path and merges any attached snapshot with
     * GRANT∧PREFERENCE. No-op when unlinked, provisional (the retry job owns
     * those), or file-managed (licence_key set). Failures are silent — info
     * log, cached row keeps serving — and the scheduler keeps running.
     * Always returns true.
     */
    public function syncHeartbeat(): bool
    {
        try {
            $school = School::current();
            if ($school === null) {
                return true;
            }

            $row = $school->licence()->first();
            if ($row === null || (bool) $row->provisional) {
                return true;
            }

            if (trim((string) $row->licence_key) !== '') {
                return true;
            }

            if (! $this->isLinked($school)) {
                return true;
            }

            $uuid = trim((string) config('controlplane.deployment_uuid'));
            $token = (string) config('controlplane.token', '');
            if ($uuid === '' || trim($token) === '') {
                return true;
            }

            $result = $this->client->ping($uuid, $token);
            if (($result['status'] ?? null) !== 'ok') {
                Log::info('controlplane.heartbeat-sync-deferred', ['status' => $result['status'] ?? 'unknown']);

                return true;
            }

            if (isset($result['snapshot']) && is_array($result['snapshot'])) {
                $incoming = trim((string) ($result['snapshot']['deployment_uuid'] ?? ''));
                if ($incoming !== '' && $incoming !== $uuid) {
                    Log::info('controlplane.heartbeat-sync-deployment-mismatch', ['school_id' => $school->id]);

                    return true;
                }

                $this->applyHeartbeatSnapshot($school, $result['snapshot']);
            }

            $counts = [];
            try {
                $payload = $this->client->heartbeatPayload($uuid);
                $counts = is_array($payload['counts'] ?? null) ? $payload['counts'] : [];
            } catch (\Throwable $e) {
                $counts = [];
            }

            Log::info('controlplane.heartbeat-synced', ['school_id' => $school->id, 'counts' => $counts]);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return true;
        }
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

    /**
     * Remember the snapshot's central core grant for display truth. Never
     * throws; a missed recording just leaves the previous grant (or the
     * local-row fallback) in place.
     */
    private function recordCentralGrant(array $snapshot): void
    {
        try {
            $lic = is_array($snapshot['licence'] ?? null) ? $snapshot['licence'] : [];
            $coreFlags = is_array($lic['core'] ?? null) ? $lic['core'] : [];

            $grants = [];
            foreach (config('licence.core_features', []) as $key => $feat) {
                if (($feat['locked'] ?? false) || ! isset($feat['db_column'])) {
                    continue;
                }
                $grants[$key] = array_key_exists($key, $coreFlags)
                    ? (bool) $coreFlags[$key]
                    : (bool) ($feat['default'] ?? true);
            }

            Setting::query()->updateOrCreate(
                ['setting_key' => self::LAST_GRANT_KEY],
                [
                    'setting_value' => json_encode($grants, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'data_type' => 'json',
                    'category' => 'licence',
                    'description' => 'Last known central core-feature grant (display truth for plan badges).',
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Map a snapshot to school_licences fields (central truth, overwriting).
     * Shared by applySnapshot and applyHeartbeatSnapshot so enrollment and
     * heartbeat can never disagree on dates, caps, tier notes or modules.
     *
     * @return array<string, mixed>
     */
    private function snapshotFields(array $snapshot): array
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

        return $fields;
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
