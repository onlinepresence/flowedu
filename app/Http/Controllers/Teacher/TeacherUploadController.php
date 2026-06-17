<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class TeacherUploadController extends Controller
{
    public function profilePhoto(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->type === 'teacher', 403);

        $teacher = $user->teacher;
        abort_unless($teacher !== null, 404);

        $path = $teacher->profile_pic;
        if ($path === null || $path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk('college_uploads');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path));
    }

    public function document(Request $request, string $type): Response
    {
        $user = $request->user();
        abort_unless($user !== null && $user->type === 'teacher', 403);

        $teacher = $user->teacher;
        abort_unless($teacher !== null, 404);

        $column = match ($type) {
            'cv' => 'cv',
            'certificate' => 'certificate',
            'id_document' => 'id_document',
            default => abort(404),
        };

        $path = $teacher->{$column};
        if ($path === null || $path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk('college_uploads');
        abort_unless($disk->exists($path), 404);

        $downloadName = Str::of($path)->afterLast('/')->toString() ?: $type;

        return $disk->download($path, $downloadName);
    }
}
