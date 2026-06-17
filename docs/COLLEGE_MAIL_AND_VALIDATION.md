# Mail and validation (legacy parity)

## Mail

- Use **Laravel Mail / Notifications** only — do not add PHPMailer to `new-college`.
- **Internal / operational / JSON-safe plain text:** dispatch [`SendCollegePlainMailJob`](../app/Jobs/SendCollegePlainMailJob.php) with scalar `toAddress`, `subjectLine`, `bodyText`.
- Mailable: [`CollegePlainMail`](../app/Mail/CollegePlainMail.php) + [`resources/views/emails/college-plain-text.blade.php`](../resources/views/emails/college-plain-text.blade.php).
- **User-facing notifications (default HTML):** dispatch [`SendCollegeNotificationMailJob`](../app/Jobs/SendCollegeNotificationMailJob.php) with scalar `toAddress`, `mailSubject`, `htmlBody` (escaped HTML fragment, e.g. `<p>…</p>`).
- Mailable: [`CollegeNotificationMail`](../app/Mail/CollegeNotificationMail.php) (`htmlString` content).
- Configure transports in `.env` / `config/mail.php` (legacy used SMTPS 465, `APP_ENV=local` behaviour — mirror in Mailer config as needed).

### 9. Choose per feature (summary)

| Audience | Default | Queued job | Example |
|----------|---------|------------|---------|
| End users (owners, students, staff) | HTML | `SendCollegeNotificationMailJob` | School activated/deactivated email from [`SetupActivatePage`](../app/Livewire/Admin/Setup/SetupActivatePage.php) |
| Internal alerts, logs-to-inbox, simple automation | Plain text | `SendCollegePlainMailJob` | Operational notices where HTML adds no value |

Prefer **notifications** for multi-channel or Laravel-native flows; use the two jobs above when you want explicit, queue-serializable payloads matching the college mail wrappers.

## Custom validation (legacy `form-validation.php`)

| Legacy | Laravel |
|--------|---------|
| Ghana Card `GHA-\d{9}-\d{1}` | [`App\Rules\GhanaCardNumber`](../app/Rules/GhanaCardNumber.php) |
| Ghana mobile prefixes | [`App\Rules\GhanaMobilePhone`](../app/Rules/GhanaMobilePhone.php) + [`config/college.php`](../config/college.php) `ghana_phone_prefixes` |
| Passport profile photo (legacy `image_validation.php`) | [`App\Services\PassportPhotoValidationService`](../app/Services/PassportPhotoValidationService.php) + [`App\Rules\PassportPhotoFile`](../app/Rules/PassportPhotoFile.php) + [`config/image_validation.php`](../config/image_validation.php) (`IMAGE_VALIDATION_*` / `COLLEGE_PASSPORT_*` env aliases) |

**Example**

```php
use App\Rules\GhanaCardNumber;
use App\Rules\GhanaMobilePhone;

$request->validate([
    'ghana_card' => ['nullable', new GhanaCardNumber],
    'phone' => ['required', new GhanaMobilePhone],
]);
```

Image validation rules from legacy `image_validation.php` should become Rule objects or config-driven rules when those admin pages are ported.
