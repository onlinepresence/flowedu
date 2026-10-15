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
     * Demo documents (signed payload with kind === 'demo') are accepted here
     * too: they route to the demo verifier and unlock a trial WITHOUT
     * seeding any licence row. The kind is read from inside the signed
     * payload, so it cannot be smuggled onto a licence file without
     * breaking that file's signature.
     */
    public function importFile(string $blob, ?School $school = null): array
    {
        $school ??= School::current();
        if ($school === null) {
            return ['ok' => false, 'message' => __('Set up the school profile first, then import the file.')];
        }

        if ($this->looksLikeDemoDocument($blob) || $this->looksLikeDemoCode($blob)) {
            return $this->acceptDemoDocument($blob);
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
     * Peek inside the signed payload for the demo marker. Licence exports
     * never carry kind, so only genuine Desk demo documents match.
     */
    private function looksLikeDemoDocument(string $blob): bool
    {
        $decoded = json_decode(trim($blob), true);

        return is_array($decoded)
            && is_array($decoded['payload'] ?? null)
            && (($decoded['payload']['kind'] ?? null) === 'demo');
    }

    /**
     * Minted demo codes (demo-XXXXXXXX from ControlDesk) as pasted by hand.
     * The verifier does the real authorization (online fetch + signature);
     * this only routes. Anything else falls through to claim-code redeem,
     * which returns a proper unknown-code error for typos.
     */
    private function looksLikeDemoCode(string $blob): bool
    {
        $trimmed = trim($blob);

        return $trimmed !== ''
            && ! str_starts_with($trimmed, '{')
            && (bool) preg_match('/^demo-[a-z0-9]{4,64}$/i', $trimmed);
    }

    /**
     * Accept a pasted demo document at the import exit: full demo
     * verification (signature, expiry, host, live-install refusal) with the
     * same durability as the /demo/key screen. Seeds NOTHING — no licence
     * row, no deployment link — so a trial can never masquerade as an
     * enrollment.
     *
     * @return array{ok: bool, message?: string, demo?: bool}
     */
    private function acceptDemoDocument(string $blob): array
    {
        $verifier = app(\App\Services\DemoKeyVerifier::class);

        $host = null;
        try {
            $host = request()->getHost();
        } catch (\Throwable) {
        }

        $check = $verifier->check($blob, $host);
        if (($check['ok'] ?? false) !== true) {
            return ['ok' => false, 'message' => $verifier::errorCopy((string) ($check['reason'] ?? ''))];
        }

        try {
            $verifier->clearRevocation();
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            session()->put('demo_key_accepted', true);
            session()->forget('demo_key_error');
        } catch (\Throwable) {
        }

        $document = $check['document'] ?? null;
        if (is_array($document)) {
            try {
                $verifier->cacheDocument($document);
            } catch (\Throwable $e) {
                report($e);
            }

            try {
                $stored = $verifier::encodeForEnv($verifier::canonicalJson($document));
                if ((EnvWriter::write(['DEMO_KEY' => $stored])['ok'] ?? false) === true) {
                    config(['college.demo_key' => $stored]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        Log::info('controlplane.import.demo_accepted', [
            'host' => $host,
            'exp' => is_array($document) ? ($document['payload']['expires_at'] ?? null) : null,
        ]);

        return ['ok' => true, 'demo' => true];
    }

    /**
     * Universal activation for the single-field UI: paste a claim code, a
     * demo document, a bare demo-XXXXXXXX code, or a licence file blob —
     * each routes to its own flow. With $fresh, existing business records
     * are cleared FIRST (local-only, keeper-safe) so a failed activation
     * never consumes a one-time code on a dirty slate.
     *
     * @return array{ok: bool, message?: string, demo?: bool, manual_lines?: list<string>, path?: string, refreshed?: bool, cleared_total?: int}
     */
    public function activate(string $input, bool $fresh = false, ?School $school = null, ?int $keeperId = null): array
    {
        $school ??= School::current();
        if ($school === null) {
            return ['ok' => false, 'message' => __('Set up the school profile first, then activate.')];
        }

        $trimmed = trim($input);
        if ($trimmed === '') {
            return ['ok' => false, 'message' => __('Paste your enrollment code, demo key, or licence file, then activate.')];
        }

        $clearedTotal = 0;
        if ($fresh) {
            if ($keeperId === null) {
                return ['ok' => false, 'message' => __('Sign in again, then activate.')];
            }

            $refreshed = app(\App\Services\ActivationDataRefreshService::class)->refresh($school, $keeperId);
            if (! ($refreshed['ok'] ?? false)) {
                return ['ok' => false, 'message' => (string) ($refreshed['message'] ?? __('Could not clear existing records.'))];
            }
            $clearedTotal = (int) ($refreshed['total'] ?? 0);
        }

        $result = str_starts_with($trimmed, '{') || $this->looksLikeDemoCode($trimmed)
            ? $this->importFile($trimmed, $school)
            : $this->redeem($trimmed, $school);

        if (($result['ok'] ?? false) === true && $fresh) {
            $result['refreshed'] = true;
            $result['cleared_total'] = $clearedTotal;
        }

        return $result;
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
        $grants = $this->readLastGrant();
        // Back-compat: early recordings stored the flat core map.
        $core = is_array($grants) && array_key_exists('core', $grants) ? $grants['core'] : $grants;

        $school ??= School::current();
        $row = $school?->licence()->first();

        $out = [];
        foreach (config('licence.core_features', []) as $key => $feat) {
            if (($feat['locked'] ?? false) || ! isset($feat['db_column'])) {
                continue;
            }
            if (is_array($core) && array_key_exists($key, $core)) {
                $out[$key] = (bool) $core[$key];
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
     * Last known central grant per module key: which modular extensions sit
     * inside this install's plan (toggleable) versus outside it (hidden).
     * Falls back to the local row, which linked installs seed from central.
     *
     * @return array<string, bool>
     */
    public function centralModuleGrants(?School $school = null): array
    {
        $grants = $this->readLastGrant();
        $modules = is_array($grants) && array_key_exists('modules', $grants) ? $grants['modules'] : null;

        $school ??= School::current();
        $row = $school?->licence()->first();

        $out = [];
        foreach (config('licence.modules', []) as $key => $feat) {
            if (! isset($feat['db_column'])) {
                continue;
            }
            if (is_array($modules) && array_key_exists($key, $modules)) {
                $out[$key] = (bool) $modules[$key];
            } elseif ($row !== null) {
                $col = $feat['db_column'];
                $out[$key] = (bool) $row->$col;
            } else {
                $out[$key] = (bool) ($feat['default'] ?? false);
            }
        }

        return $out;
    }

    /**
     * Live licence = linked to ControlDesk AND within its dates. Null dates
     * mean "no expiry recorded". Anything else (provisional, pending,
     * legacy, expired) is core-only.
     */
    public function isLicenceLive(?School $school = null): bool
    {
        $school ??= School::current();
        if (! $this->isLinked($school)) {
            return false;
        }

        $row = $school?->licence()->first();
        if ($row === null) {
            return false;
        }

        $today = now()->toDateString();

        foreach (['licence_end', 'support_until'] as $col) {
            $date = $row->$col;
            if ($date === null || $date === '') {
                continue;
            }
            $value = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date;
            if ($value !== '' && $value < $today) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readLastGrant(): ?array
    {
        try {
            $raw = Setting::query()->where('setting_key', self::LAST_GRANT_KEY)->value('setting_value');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            return null;
        }
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
     * Remember the snapshot's central grant for display truth (which core
     * features and which modules sit inside the plan). Never throws; a
     * missed recording just leaves the previous grant (or the local-row
     * fallback) in place.
     */
    private function recordCentralGrant(array $snapshot): void
    {
        try {
            $snapshot = $this->toInternalSnapshot($snapshot);
            $lic = is_array($snapshot['licence'] ?? null) ? $snapshot['licence'] : [];
            $unified = $this->unifiedFlags($lic);

            $core = [];
            foreach (config('licence.core_features', []) as $key => $feat) {
                if (($feat['locked'] ?? false) || ! isset($feat['db_column'])) {
                    continue;
                }
                $core[$key] = array_key_exists('core:'.$key, $unified)
                    ? (bool) $unified['core:'.$key]
                    : (bool) ($feat['default'] ?? true);
            }

            $modules = [];
            foreach (config('licence.modules', []) as $key => $feat) {
                if (! isset($feat['db_column'])) {
                    continue;
                }
                $modules[$key] = array_key_exists('modules:'.$key, $unified)
                    ? (bool) $unified['modules:'.$key]
                    : (bool) ($feat['default'] ?? false);
            }

            Setting::query()->updateOrCreate(
                ['setting_key' => self::LAST_GRANT_KEY],
                [
                    'setting_value' => json_encode(['core' => $core, 'modules' => $modules], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'data_type' => 'json',
                    'category' => 'licence',
                    'description' => 'Last known central grant (display truth for plan badges and licensed modules).',
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
        $snapshot = $this->toInternalSnapshot($snapshot);
        $lic = is_array($snapshot['licence'] ?? null) ? $snapshot['licence'] : [];
        $unified = $this->unifiedFlags($lic);

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

        $tier = (string) ($lic['package_tier'] ?? $lic['tier'] ?? 'complete');
        if ($tier === 'standard') {
            $tier = 'complete';
        }
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
            $fields[$feat['db_column']] = array_key_exists('core:'.$key, $unified)
                ? (bool) $unified['core:'.$key]
                : (bool) ($feat['default'] ?? true);
        }

        foreach (config('licence.modules', []) as $key => $feat) {
            if (! isset($feat['db_column'])) {
                continue;
            }
            $fields[$feat['db_column']] = array_key_exists('modules:'.$key, $unified)
                ? (bool) $unified['modules:'.$key]
                : (bool) ($feat['default'] ?? false);
        }

        return $fields;
    }

    /**
     * Normalize any snapshot to the internal envelope
     * {deployment_uuid, control_plane_token, licence{package_tier,
     * max_active_students, starts_at, expires_at, core{}, modules{}}}.
     *
     * Accepts the nested online shape AND the flat air-gap file shape
     * ControlDesk exports ({product, deployment_uuid, tier, enabled[],
     * caps, starts_at, expires_at, issued_at, version, kid}).
     */
    private function toInternalSnapshot(array $snapshot): array
    {
        if (isset($snapshot['licence']) && is_array($snapshot['licence'])) {
            return $snapshot;
        }

        if (! array_key_exists('enabled', $snapshot) && ! array_key_exists('deployment_uuid', $snapshot)) {
            return $snapshot;
        }

        $enabled = $snapshot['enabled'] ?? [];
        if (! is_array($enabled)) {
            $enabled = [];
        }

        $core = [];
        $modules = [];
        foreach ($enabled as $key => $value) {
            if (is_int($key)) {
                $name = is_string($value) ? $value : '';
                $granted = true;
            } else {
                $name = (string) $key;
                $granted = (bool) $value;
            }
            if ($name === '') {
                continue;
            }
            $shortCore = ControlPlaneClient::shortCoreKey($name);
            if ($shortCore !== null) {
                $core[$shortCore] = $granted;
                continue;
            }
            $shortModule = ControlPlaneClient::shortModuleKey($name);
            if ($shortModule !== null) {
                if ($granted) {
                    $modules[$shortModule] = true;
                }
                continue;
            }
        }

        $caps = $snapshot['caps'] ?? [];
        if (! is_array($caps)) {
            $caps = [];
        }

        $tier = (string) ($snapshot['tier'] ?? 'complete');
        if ($tier === 'standard') {
            $tier = 'complete';
        }

        return [
            'deployment_uuid' => (string) ($snapshot['deployment_uuid'] ?? ''),
            'control_plane_token' => (string) ($snapshot['control_plane_token'] ?? ''),
            'licence' => [
                'package_tier' => $tier,
                'max_active_students' => $caps['max_active_students']
                    ?? $caps['max_students']
                    ?? $caps['students']
                    ?? null,
                'starts_at' => $snapshot['starts_at'] ?? null,
                'expires_at' => $snapshot['expires_at'] ?? null,
                'core' => $core,
                'modules' => $modules,
            ],
        ];
    }

    /**
     * Merge core + modules flag maps into short-key lookups, accepting
     * short keys (finance), db_column keys (module_finance), and list
     * forms. Keys land as core:<short> / modules:<short>.
     *
     * @return array<string, bool>
     */
    private function unifiedFlags(array $lic): array
    {
        $unified = [];

        foreach (['core', 'modules'] as $mapKey) {
            $map = $lic[$mapKey] ?? [];
            if (! is_array($map)) {
                continue;
            }
            foreach ($map as $key => $value) {
                if (is_int($key)) {
                    $name = is_string($value) ? $value : '';
                    if ($name === '') {
                        continue;
                    }
                    $granted = true;
                } else {
                    $name = (string) $key;
                    $granted = (bool) $value;
                }
                if (($short = ControlPlaneClient::shortCoreKey($name)) !== null) {
                    $unified['core:'.$short] = $granted;
                } elseif (($short = ControlPlaneClient::shortModuleKey($name)) !== null) {
                    // Explicit false for modules == absent (default false),
                    // but record it anyway so callers can distinguish.
                    $unified['modules:'.$short] = $granted;
                }
            }
        }

        return $unified;
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
