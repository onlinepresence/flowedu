<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tools;

use App\Jobs\SendCollegeNotificationMailJob;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Support\MailSetup;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EmailComposerPage extends Component
{
    use DispatchesCollegeToasts;

    public string $recipients = '';

    public string $subject = '';

    public string $htmlBody = '';

    public bool $showPreview = false;

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

        // Queued, never instant: bulk sends stay off the request cycle, get
        // retries on SMTP failure, and behave like every other app email.
        // The per-minute queue drain delivers them shortly after.
        foreach ($emails as $email) {
            SendCollegeNotificationMailJob::dispatch($email, $validated['subject'], $validated['htmlBody']);
        }

        $this->collegeToast(__('Queued :count email(s) for delivery.', ['count' => count($emails)]));
        $this->reset(['recipients', 'subject', 'htmlBody', 'showPreview']);
    }

    public function render(): View
    {
        return view('livewire.admin.tools.email-composer-page')->layout('components.layouts.admin', [
            'title' => __('Email composer'),
            'headerTitle' => __('Compose Email'),
            'headerDescription' => __('Send an HTML email to one or more recipients. Delivery is queued.'),
        ]);
    }

    private function guard(): void
    {
        abort_unless(MailSetup::isConfigured(), 404);

        $user = auth()->user();
        abort_unless($user !== null && $user->canAdmin('admin.nav_tools_email'), 403);
    }
}
