<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Admission application approved') }}</title>
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
            background-color: #059669;
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
        .button {
            display: inline-block;
            background-color: #059669;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 8px;
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
            <h1>{{ __('Admission application approved') }}</h1>
        </div>

        <div class="content">
            <p>{{ __('Dear :name,', ['name' => trim(implode(' ', array_filter([$student->firstname, $student->lastname])))]) }}</p>

            <p>
                {{ __('We are pleased to inform you that your application to :school has been approved.', ['school' => $schoolName]) }}
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

            <p>
                {{ __('You can now sign in to activate your student dashboard and continue with enrolment.') }}
            </p>

            <p>
                <a href="{{ route('login') }}" class="button">{{ __('Sign in to your account') }}</a>
            </p>

            <p>
                {{ __('If you have any questions, please contact the admissions office.') }}
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
