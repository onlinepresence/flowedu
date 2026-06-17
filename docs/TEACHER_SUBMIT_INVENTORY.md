# `teacher/submit.php` inventory (`submit` request parameter)

Branches from [teacher/submit.php](../../teacher/submit.php). Map each to Laravel **Form Requests**, **Actions**, and/or **Livewire** (e.g. [`TeacherSetupWizard`](../app/Livewire/Teacher/TeacherSetupWizard.php)) when porting.

| `submit` value | Notes / legacy behaviour |
|----------------|--------------------------|
| `set_password` | POST `password`, `confirm_password`, `user_id`, optional `new_user`. Validates with `is_valid_password`; hashes and updates `users`. If `new_user`, clears `teachers.password_reset_required`, refreshes session, triggers `send_verification_email()`. Flash: "Password has been reset". |
| `save_teacher` | First-time onboarding save: `validate_form` rules for profile fields + optional files (`cv`, `profile_pic`, `id_document`, `certificate`). `form_data("teachers/$staff_id")`; `update` `teachers` by `user_id`; sets `users.username` to `staff_id`; redirect `teacher/dashboard` on success; on failure deletes uploaded assets. |
| `update_teacher` | Same validation as `save_teacher`; sets `is_onboarded` true in payload; omits unchanged file fields when files not re-uploaded; `update` `teachers` by `user_id`; session refresh. |

## JSON responses

When `response_type=json`, legacy returns:

```json
{ "errors": {}, "old_input": {}, "status": false, "message": null }
```

Align any remaining AJAX callers with Livewire or a dedicated JSON endpoint (plan §9.3).

## Related §8 sources

- Validation rules mirror [`includes/form-validation.php`](../../includes/form-validation.php) (e.g. `ghana_card`, `phone`, file rules).
- File paths / `asset()` usage → Laravel `Storage` and [`config/filesystems.php`](../config/filesystems.php) `college_uploads`.
