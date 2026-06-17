@extends('layouts.print', ['title' => $title, 'backUrl' => $backUrl])

@section('content')
    <style>
        @media print {
            .page-break {
                page-break-before: always;
                margin-top: 2cm;
            }
            body {
                background: white;
                color: black;
            }
        }
        .transcript-page {
            padding-bottom: 20px;
        }
    </style>

    @forelse ($groupedByYear as $year => $semesters)
        <div class="transcript-page {{ $loop->first ? '' : 'page-break' }}">
            <!-- Consistent School Header -->
            <header class="ps-school">
                <h1>{{ $school?->name ?? config('app.name') }}</h1>
                @if ($school?->address)
                    <p>{{ $school->address }}</p>
                @endif
                <span class="ps-tag">{{ __('OFFICIAL ACADEMIC TRANSCRIPT') }} (YEAR {{ $year }})</span>
            </header>

            <!-- Biodata Section -->
            <section class="ps-hero" style="margin-bottom: 16px;">
                @if ($loop->first)
                    <div class="ps-photo-wrap" style="width: 120px; height: 140px;">
                        @if ($photoDataUrl)
                            <img src="{{ $photoDataUrl }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div class="ps-photo-placeholder" style="font-size: 10px;">{{ __('No photo') }}</div>
                        @endif
                    </div>
                    <div class="ps-biodata" style="margin-left: 20px;">
                        <h2 style="margin: 0 0 6px 0; font-size: 20px;">{{ $student->lastname }} {{ $student->firstname }} {{ $student->othernames }}</h2>
                        <p class="ps-index" style="margin: 0 0 8px 0; font-weight: bold;">{{ __('Index: :n', ['n' => $student->index_number]) }}</p>
                        <dl class="ps-dl" style="font-size: 13px;">
                            <dt>{{ __('Program') }}</dt>
                            <dd>{{ $student->program?->name ?? '—' }}</dd>
                            <dt>{{ __('Department') }}</dt>
                            <dd>{{ $student->department?->name ?? '—' }}</dd>
                        </dl>
                    </div>
                @else
                    <div class="ps-biodata" style="width: 100%; border-bottom: 1px solid #ccc; padding-bottom: 6px;">
                        <h3 style="margin: 0; font-size: 16px;">{{ $student->lastname }} {{ $student->firstname }} {{ $student->othernames }}</h3>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: #555;">
                            {{ __('Index: :n', ['n' => $student->index_number]) }} | {{ __('Program:') }} {{ $student->program?->name ?? '—' }}
                        </p>
                    </div>
                @endif
            </section>

            <!-- Semester tables for this Year -->
            @foreach ($semesters as $semName => $semData)
                <div class="ps-card" style="margin-bottom: 24px; border: 1px solid #ddd; border-radius: 6px; padding: 12px; background: #fff;">
                    <h3 style="margin-top: 0; color: #5B21B6; font-size: 14px; font-weight: bold; border-bottom: 2px solid #5B21B6; padding-bottom: 4px;">
                        {{ $semName }}
                    </h3>
                    <table class="ps-table" style="width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 12px;">
                        <thead>
                            <tr style="border-bottom: 1px solid #ccc;">
                                <th style="text-align: left; padding: 6px;">{{ __('Course Code') }}</th>
                                <th style="text-align: left; padding: 6px;">{{ __('Course Title') }}</th>
                                <th style="text-align: center; padding: 6px;">{{ __('Score') }}</th>
                                <th style="text-align: center; padding: 6px;">{{ __('Grade') }}</th>
                                <th style="text-align: center; padding: 6px;">{{ __('Points') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($semData['results'] as $result)
                                <tr style="border-bottom: 1px dotted #eee;">
                                    <td style="padding: 6px; font-weight: bold;">{{ $result->course?->code ?? '—' }}</td>
                                    <td style="padding: 6px;">{{ $result->course?->name ?? '—' }}</td>
                                    <td style="padding: 6px; text-align: center;">{{ floatval($result->score) }}</td>
                                    <td style="padding: 6px; text-align: center; font-weight: bold;">{{ $result->grade ?? '—' }}</td>
                                    <td style="padding: 6px; text-align: center;">{{ floatval($result->grade_points) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- GPA Calculations -->
                    <div style="margin-top: 10px; display: flex; justify-content: space-between; font-size: 11px; font-weight: bold; background: #f9fafb; padding: 6px 10px; border-radius: 4px; border: 1px solid #e5e7eb;">
                        <div>{{ __('Semester GPA:') }} <span style="color: #5B21B6;">{{ $semData['gpa'] }}</span></div>
                        <div>{{ __('Cumulative CGPA:') }} <span style="color: #5B21B6;">{{ $semData['cgpa'] }}</span></div>
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <div class="ps-card">
            <p style="margin:0;color:var(--ps-muted);">{{ __('No result rows on file for this student.') }}</p>
        </div>
    @endforelse

    <footer class="ps-foot" style="margin-top: 30px; font-size: 11px; border-top: 1px solid #ddd; padding-top: 10px; text-align: center; color: #666;">
        {{ __('Official Academic Transcript — generated :date', ['date' => now()->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
    </footer>
@endsection
