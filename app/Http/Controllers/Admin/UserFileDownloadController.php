<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserUploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class UserFileDownloadController extends Controller
{
    public function __invoke(Request $request, UserUploadedFile $file): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 403);
        
        // Enforce permission and ownership
        abort_unless($user->canAdmin('admin.manage_file_uploads'), 403);
        abort_unless((int) $file->user_id === (int) $user->id, 403);

        $path = $file->file_path;
        abort_if($path === '' || str_contains($path, '..'), 404);

        $disk = Storage::disk('college_uploads');
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, $file->original_filename);
    }
}
