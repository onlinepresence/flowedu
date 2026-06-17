@php
    $collegeToastInitial = [];
    $status = session('status');
    if (is_string($status) && $status !== '' && $status !== 'verification-link-sent') {
        $collegeToastInitial[] = ['message' => $status, 'variant' => 'success'];
    }
    $backupError = session('backup_error');
    if (is_string($backupError) && $backupError !== '') {
        $collegeToastInitial[] = ['message' => $backupError, 'variant' => 'danger'];
    }
@endphp

@if (count($collegeToastInitial) > 0)
    <script type="application/json" id="college-toast-initial">@json($collegeToastInitial)</script>
@endif
