<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Support\FilepondPendingFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams the teacher's pending attendance upload (Filepond temp) after it is selected and before submit.
 */
final class TeacherAttendanceSheetPreviewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->type === 'teacher', 403);

        $path = session('teacher_attendance_sheet_tmp');
        abort_if($path === null || $path === '', 404);

        $userId = Auth::id();
        abort_if($userId === null || ! FilepondPendingFile::assertOwnedPendingPath($path, $userId), 404);

        return response()->file(Storage::disk('local')->path($path));
    }
}
