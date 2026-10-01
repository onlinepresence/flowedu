<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * TEMPORARY admin-only SMTP smoke test (delete once emailing is verified).
 *
 * GET /__testing/mail?to=someone@example.com&message=hello — sends
 * synchronously (not queued) so the result reflects the SMTP round-trip
 * immediately, and reports success or the exact failure.
 */
class MailTestController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null && $user->type === 'admin', 403);

        $result = null;
        $to = trim((string) $request->query('to', ''));
        $message = (string) $request->query('message', '');

        if ($to !== '' || $message !== '') {
            $validated = $request->validate([
                'to' => ['required', 'email', 'max:255'],
                'message' => ['required', 'string', 'max:5000'],
            ]);

            try {
                Mail::raw($validated['message'], function ($mail) use ($validated): void {
                    $mail->to($validated['to'])->subject(__('Test email from :app', ['app' => (string) config('app.name')]));
                });
                $result = [
                    'ok' => true,
                    'detail' => __('Sent via the :mailer mailer to :to.', [
                        'mailer' => (string) config('mail.default'),
                        'to' => $validated['to'],
                    ]),
                ];
            } catch (\Throwable $e) {
                report($e);
                $result = ['ok' => false, 'detail' => $e->getMessage()];
            }
        }

        return view('mail-test', [
            'result' => $result,
            'to' => $to,
            'message' => $message,
            'mailer' => (string) config('mail.default'),
            'demoMode' => (bool) config('college.demo_mode', false),
        ]);
    }
}
