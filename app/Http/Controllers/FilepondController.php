<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class FilepondController extends Controller
{
    /**
     * FilePond expects the response body to be the server-side file identifier (plain text).
     *
     * @see https://pqina.nl/filepond/docs/api/server/#process
     */
    public function process(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $purpose = (string) $request->input('purpose', 'generic_image');

        $rules = match ($purpose) {
            'school_logo', 'profile_photo', 'admin_profile_photo' => ['required', 'file', 'image', 'max:2048'],
            'passport_photo', 'teacher_profile_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,avif,webp', 'max:5120'],
            'teacher_cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:2048'],
            'teacher_certificate', 'teacher_id_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:2048'],
            'teacher_course_material' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xlsx,xls,txt,zip', 'max:15360'],
            'backup_upload' => ['required', 'file', 'max:51200'],
            'teacher_import' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
            'results_upload' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
            'teacher_attendance_sheet' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,pdf', 'max:10240'],
            default => ['required', 'file', 'max:12288'],
        };

        $request->validate([
            'filepond' => $rules,
        ]);

        $file = $request->file('filepond');
        $ext = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $relative = 'filepond-tmp/'.$user->id.'/'.Str::ulid().'.'.$ext;

        Storage::disk('local')->put($relative, file_get_contents($file->getRealPath()));

        return response($relative, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * FilePond sends the server id as the raw request body for revert.
     */
    public function revert(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $relative = trim($request->getContent());
        $prefix = 'filepond-tmp/'.$user->id.'/';

        if ($relative === '' || ! str_starts_with($relative, $prefix) || str_contains(Str::after($relative, $prefix), '..')) {
            return response('', 400);
        }

        if (Storage::disk('local')->exists($relative)) {
            Storage::disk('local')->delete($relative);
        }

        return response('', 200);
    }
}
