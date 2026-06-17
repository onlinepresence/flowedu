@extends('layouts.print', ['title' => $title, 'backUrl' => $backUrl])

@section('content')
    <header class="ps-school">
        <h1>{{ $school?->name ?? config('app.name') }}</h1>
        @if ($school?->address)
            <p>{{ $school->address }}</p>
        @endif
        <span class="ps-tag">{{ __('Official Student Record Card') }}</span>
    </header>

    <!-- Bio & Primary Info -->
    <section class="ps-hero">
        <div class="ps-photo-wrap">
            @if ($photoDataUrl)
                <img src="{{ $photoDataUrl }}" alt="" width="200" height="250">
            @else
                <div class="ps-photo-placeholder">
                    <span class="text-3xl font-bold uppercase" style="color: #64748b; font-size: 2.5rem;">
                        {{ substr($student->firstname ?? 'S', 0, 1) }}{{ substr($student->lastname ?? 'S', 0, 1) }}
                    </span>
                    <div style="margin-top: 8px; font-size: 0.75rem; color: #94a3b8;">{{ __('No photo on file') }}</div>
                </div>
            @endif
        </div>
        <div class="ps-biodata">
            <h2>{{ trim(implode(' ', array_filter([$student->firstname, $student->othernames, $student->lastname]))) }}</h2>
            <p class="ps-index">{{ __('Index Number: :n', ['n' => $student->index_number]) }}</p>
            <dl class="ps-dl">
                <dt>{{ __('Program') }}</dt>
                <dd>{{ $student->program?->name ?? '—' }}</dd>
                <dt>{{ __('Department') }}</dt>
                <dd>{{ $student->department?->name ?? '—' }}</dd>
                <dt>{{ __('Current Level') }}</dt>
                <dd>{{ __('Level :l', ['l' => $student->current_year]) }}</dd>
                <dt>{{ __('Hall of Residence') }}</dt>
                <dd>{{ $student->hall?->name ?? '—' }}</dd>
                <dt>{{ __('Date of Birth') }}</dt>
                <dd>{{ $student->date_of_birth?->format('F d, Y') ?? '—' }}</dd>
                <dt>{{ __('Gender') }}</dt>
                <dd>{{ ucfirst($student->gender) }}</dd>
                <dt>{{ __('Nationality') }}</dt>
                <dd>{{ $student->nationality }}</dd>
                <dt>{{ __('Phone Number') }}</dt>
                <dd>{{ $student->phone_number }}</dd>
                <dt>{{ __('Email Address') }}</dt>
                <dd>{{ $student->user?->email ?? '—' }}</dd>
            </dl>
        </div>
    </section>

    <!-- Admission & Contacts -->
    <div class="ps-grid cols-2" style="margin-bottom: 20px;">
        <div class="ps-card">
            <h3>{{ __('Admission Details') }}</h3>
            <dl class="ps-dl">
                <dt>{{ __('Admission Index') }}</dt>
                <dd>{{ $student->admission_index }}</dd>
                <dt>{{ __('Admission Date') }}</dt>
                <dd>{{ $student->admission_date?->format('Y-m-d') ?? '—' }}</dd>
                <dt>{{ __('Account Status') }}</dt>
                <dd>{{ $student->approved ? __('Approved') : __('Pending Approval') }}</dd>
                <dt>{{ __('Ghana Card') }}</dt>
                <dd>{{ $student->ghana_card ?? '—' }}</dd>
            </dl>
        </div>
        <div class="ps-card">
            <h3>{{ __('Parent & Guardian Contacts') }}</h3>
            @forelse ($student->parentGuardians as $guardian)
                <div style="border-bottom: 1px solid var(--ps-border); padding-bottom: 8px; margin-bottom: 8px; font-size: 0.85rem;" class="last:border-0 last:pb-0 last:mb-0">
                    <strong style="display:block; color: var(--ps-ink);">{{ $guardian->name }} ({{ $guardian->relationship }})</strong>
                    <span style="color: var(--ps-muted); font-size: 0.75rem;">Phone: {{ $guardian->phone_number }} | Email: {{ $guardian->email ?: '—' }}</span>
                </div>
            @empty
                <p style="font-size: 0.85rem; color: var(--ps-muted); margin: 0;">{{ __('No parent or guardian contacts listed.') }}</p>
            @endforelse
        </div>
    </div>

    <!-- Health & Medical History -->
    <div class="ps-card" style="margin-bottom: 20px; break-inside: avoid;">
        <h3>{{ __('Medical Profile & Clinical History') }}</h3>
        <div class="ps-grid cols-2" style="margin-bottom: 14px; padding-bottom: 14px; border-b: 1px dashed var(--ps-border);">
            <div>
                <strong style="font-size: 0.75rem; color: var(--ps-muted); text-transform: uppercase;">{{ __('Known Allergies') }}</strong>
                <p style="margin: 4px 0 0; font-size: 0.9rem; font-weight: 600;">{{ $student->allergy ?: __('None Recorded') }}</p>
            </div>
            <div>
                <strong style="font-size: 0.75rem; color: var(--ps-muted); text-transform: uppercase;">{{ __('Insurance Number') }}</strong>
                <p style="margin: 4px 0 0; font-size: 0.9rem; font-weight: 600;">{{ $student->insurance_number ?: __('None') }}</p>
            </div>
        </div>

        @if ($medicalLogs->isNotEmpty())
            <table class="ps-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">{{ __('Date') }}</th>
                        <th style="width: 25%;">{{ __('Conditions') }}</th>
                        <th style="width: 30%;">{{ __('Medications / Treatment') }}</th>
                        <th style="width: 30%;">{{ __('Immunization / Details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($medicalLogs as $log)
                        <tr>
                            <td style="font-size: 0.8rem; font-weight: 600;">{{ $log->created_at?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $log->medical_conditions ?: '—' }}</td>
                            <td>{{ $log->medications ?: '—' }}</td>
                            <td style="font-size: 0.8rem; color: var(--ps-muted);">{{ $log->immunization_records ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="font-size: 0.85rem; color: var(--ps-muted); margin: 0;">{{ __('No clinical logs recorded.') }}</p>
        @endif
    </div>

    <!-- Disciplinary Incidents -->
    <div class="ps-card" style="margin-bottom: 20px; break-inside: avoid;">
        <h3>{{ __('Disciplinary Incident Logs') }}</h3>
        @if ($disciplinaryLogs->isNotEmpty())
            <table class="ps-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">{{ __('Date') }}</th>
                        <th style="width: 30%;">{{ __('Offense') }}</th>
                        <th style="width: 35%;">{{ __('Action Taken') }}</th>
                        <th style="width: 20%;">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($disciplinaryLogs as $dis)
                        <tr>
                            <td style="font-size: 0.8rem; font-weight: 600;">{{ $dis->date_of_action?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $dis->offense }}</td>
                            <td>{{ $dis->action_taken }}</td>
                            <td>
                                @if ($dis->return_status)
                                    <span style="color: #16a34a; font-weight: 600;">{{ __('Closed') }}</span>
                                @else
                                    <span style="color: #dc2626; font-weight: 600;">{{ __('Active Suspension') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="font-size: 0.85rem; color: var(--ps-muted); margin: 0;">{{ __('No disciplinary incidents recorded.') }}</p>
        @endif
    </div>

    <footer class="ps-foot">
        {{ __('Official College Registrar Document — Generated :date', ['date' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
    </footer>
@endsection
