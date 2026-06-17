<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\CourseMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class TeacherCourseMaterialDownloadController extends Controller
{
    public function __invoke(Request $request, CourseMaterial $material): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->type === 'teacher', 403);
        abort_unless($user->teacher !== null && (int) $material->teacher_id === (int) $user->teacher->id, 403);

        $path = $material->file_path;
        abort_if($path === '' || str_contains($path, '..'), 404);

        $disk = Storage::disk('college_uploads');
        abort_unless($disk->exists($path), 404);

        $name = Str::of($path)->afterLast('/')->toString() ?: 'material';

        return $disk->download($path, $name);
    }
}
