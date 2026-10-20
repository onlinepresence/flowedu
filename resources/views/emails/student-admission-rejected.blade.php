<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Admission application update') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
            color: #1f2937;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #b91c1c;
            color: #ffffff;
            padding: 28px 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }
        .content {
            padding: 24px;
            font-size: 15px;
            line-height: 1.6;
            color: #4b5563;
        }
        .content p {
            margin-top: 0;
            margin-bottom: 16px;
        }
        .details {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
            margin: 20px 0;
        }
        .details table {
            width: 100%;
            border-collapse: collapse;
        }
        .details td {
            padding: 6px 0;
            font-size: 14px;
            vertical-align: top;
        }
        .details td.label {
            color: #6b7280;
            width: 40%;
        }
        .details td.value {
            color: #111827;
            font-weight: 600;
        }
        .reason {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 16px;
            margin: 20px 0;
            color: #7f1d1d;
        }
        .reason h2 {
            margin: 0 0 8px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .reason p {
            margin: 0;
            white-space: pre-line;
        }
        .footer {
            padding: 18px 24px;
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('Admission application update') }}</h1>
        </div>

        <div class="content">
            <p>{{ __('Dear :name,', ['name' => trim(implode(' ', array_filter([$student->firstname, $student->lastname])))]) }}</p>

            <p>
                {{ __('Thank you for your interest in :school. After reviewing your application, we are unable to offer you admission at this time.', ['school' => $schoolName]) }}
            </p>

            <div class="details">
                <table>
                    <tr>
                        <td class="label">{{ __('Application number') }}</td>
                        <td class="value">{{ $student->index_number }}</td>
                    </tr>
                    @if ($student->program)
                        <tr>
                            <td class="label">{{ __('Programme') }}</td>
                            <td class="value">{{ $student->program->name }}</td>
                        </tr>
                    @endif
                </table>
            </div>

            <div class="reason">
                <h2>{{ __('Reason for decision') }}</h2>
                <p>{{ $rejectionReason }}</p>
            </div>

            <p>
                {{ __('If you believe this decision was made in error or would like clarification, please contact the admissions office.') }}
            </p>
        </div>

        <div class="footer">
            {{ $schoolName }}
            @if ($school?->email)
                &middot; {{ $school->email }}
            @endif
            @if ($school?->phone)
                &middot; {{ $school->phone }}
            @endif
        </div>
    </div>
</body>
</html>
