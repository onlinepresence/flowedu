<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherAttendanceSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class TeacherAttendanceSheetDownloadController extends Controller
{
    public function __invoke(Request $request, TeacherAttendanceSheet $sheet): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->type === 'teacher', 403);
        abort_unless($user->teacher !== null && (int) $sheet->teacher_id === (int) $user->teacher->id, 403);

        $path = $sheet->file_path;
        abort_if($path === '' || str_contains($path, '..'), 404);

        $disk = Storage::disk('college_uploads');
        abort_unless($disk->exists($path), 404);

        $name = $sheet->original_name ?: basename($path);

        return $disk->download($path, $name);
    }
}
