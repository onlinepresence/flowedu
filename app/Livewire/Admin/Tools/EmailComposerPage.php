<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tools;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Mail\CollegeNotificationMail;
use App\Support\MailSetup;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Throwable;

class EmailComposerPage extends Component
{
    use DispatchesCollegeToasts;

    public string $recipients = '';

    public string $subject = '';

    public string $htmlBody = '';

    /**
     * @var array<string, string>
     */
    public array $deliveryFailures = [];

    public int $deliveredCount = 0;

    public function mount(): void
    {
        $this->guard();
    }

    /**
     * @return list<string>
     */
    public function getRecipientListProperty(): array
    {
        $parts = preg_split('/[\s,;]+/', trim($this->recipients)) ?: [];

        $out = [];
        foreach ($parts as $part) {
            $email = trim((string) $part);
            if ($email !== '' && ! in_array($email, $out, true)) {
                $out[] = $email;
            }
        }

        return $out;
    }

    public function getRecipientCountProperty(): int
    {
        return count($this->recipientList);
    }

    public function send(): void
    {
        $this->guard();

        $this->resetErrorBag();
        $this->deliveryFailures = [];
        $this->deliveredCount = 0;

        $validated = $this->validate([
            'subject' => ['required', 'string', 'max:255'],
            'htmlBody' => ['required', 'string', 'max:50000'],
        ]);

        $emails = $this->recipientList;
        if ($emails === []) {
            $this->addError('recipients', __('Add at least one recipient.'));

            return;
        }

        if (count($emails) > 50) {
            $this->addError('recipients', __('Too many recipients (maximum :max at once).', ['max' => 50]));

            return;
        }

        $invalid = array_values(array_filter($emails, fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) === false));
        if ($invalid !== []) {
            $shown = implode(', ', array_slice($invalid, 0, 3));
            $extra = count($invalid) > 3 ? __(' (+:count more)', ['count' => count($invalid) - 3]) : '';
            $this->addError('recipients', __('These addresses look invalid: :list:extra', ['list' => $shown, 'extra' => $extra]));

            return;
        }

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new CollegeNotificationMail($validated['subject'], $validated['htmlBody']));
                $this->deliveredCount++;
            } catch (Throwable $exception) {
                report($exception);

                $message = trim($exception->getMessage());
                $this->deliveryFailures[$email] = $message !== '' ? $message : $exception::class;
            }
        }

        if ($this->deliveryFailures !== []) {
            $this->addError('delivery', __(':sent of :total email(s) sent. Fix the errors below, then retry.', [
                'sent' => $this->deliveredCount,
                'total' => count($emails),
            ]));
            $this->recipients = implode(', ', array_keys($this->deliveryFailures));

            return;
        }

        $this->collegeToast(__('Sent :count email(s).', ['count' => $this->deliveredCount]));
        $this->recipients = '';
        $this->subject = '';
        $this->htmlBody = '';
        $this->deliveryFailures = [];
        $this->deliveredCount = 0;
    }

    public function render(): View
    {
        return view('livewire.admin.tools.email-composer-page')->layout('components.layouts.admin', [
            'title' => __('Email composer'),
            'headerTitle' => __('Compose Email'),
            'headerDescription' => __('Send an HTML email to one or more recipients. Delivery is immediate and errors are shown here.'),
        ]);
    }

    private function guard(): void
    {
        abort_unless(MailSetup::isConfigured(), 404);

        $user = auth()->user();
        abort_unless($user !== null && $user->canAdmin('admin.nav_tools_email'), 403);
    }
}
