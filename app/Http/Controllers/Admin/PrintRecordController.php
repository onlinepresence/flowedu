<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

final class PrintRecordController extends Controller
{
    public function student(string $index_number): View
    {
        $student = Student::query()
            ->where('index_number', $index_number)
            ->with(['department', 'program', 'hall', 'user', 'parentGuardians'])
            ->firstOrFail();

        $school = School::current();
        $photoDataUrl = $this->profilePhotoDataUrl($student->profile_pic);

        $medicalLogs = \App\Models\MedicalHistory::query()
            ->where('student_id', $student->id)
            ->orderByDesc('id')
            ->get();

        $disciplinaryLogs = \App\Models\DisciplinaryRecord::query()
            ->where('index_number', $student->index_number)
            ->orderByDesc('id')
            ->get();

        return view('print.student-record', [
            'title' => __('Student record'),
            'backUrl' => route('admin.students.show', ['index_number' => $student->index_number]),
            'school' => $school,
            'student' => $student,
            'photoDataUrl' => $photoDataUrl,
            'medicalLogs' => $medicalLogs,
            'disciplinaryLogs' => $disciplinaryLogs,
        ]);
    }

    public function transcript(string $index_number): View
    {
        $student = Student::query()
            ->where('index_number', $index_number)
            ->with([
                'department',
                'program',
                'results.course',
                'results.academicSession',
            ])
            ->firstOrFail();

        $school = \App\Models\School::current();
        $photoDataUrl = $this->profilePhotoDataUrl($student->profile_pic);

        // Group by year_level and course_semester
        $results = $student->results->sortBy(fn ($r) => [
            $r->course?->year_level ?? 1,
            $r->course?->course_semester ?? 'Semester 1'
        ]);

        $groupedByYear = [];
        $cumulativePts = 0.0;
        $cumulativeCnt = 0;

        foreach ($results as $res) {
            $year = $res->course?->year_level ?? 1;
            $sem = $res->course?->course_semester ?? 'Semester 1';
            
            if (! isset($groupedByYear[$year])) {
                $groupedByYear[$year] = [];
            }
            if (! isset($groupedByYear[$year][$sem])) {
                $groupedByYear[$year][$sem] = [
                    'results' => [],
                    'pts' => 0.0,
                    'cnt' => 0,
                    'gpa' => '0.00',
                    'cgpa' => '0.00',
                ];
            }

            $groupedByYear[$year][$sem]['results'][] = $res;
            $pts = (float) $res->grade_points;
            $groupedByYear[$year][$sem]['pts'] += $pts;
            $groupedByYear[$year][$sem]['cnt']++;

            $cumulativePts += $pts;
            $cumulativeCnt++;

            $groupedByYear[$year][$sem]['gpa'] = number_format(
                $groupedByYear[$year][$sem]['cnt'] > 0 
                    ? $groupedByYear[$year][$sem]['pts'] / $groupedByYear[$year][$sem]['cnt'] 
                    : 0.0,
                2
            );
            $groupedByYear[$year][$sem]['cgpa'] = number_format(
                $cumulativeCnt > 0 
                    ? $cumulativePts / $cumulativeCnt 
                    : 0.0,
                2
            );
        }

        return view('print.transcript', [
            'title' => __('Academic transcript'),
            'backUrl' => route('admin.grading.transcripts.index'),
            'school' => $school,
            'student' => $student,
            'photoDataUrl' => $photoDataUrl,
            'groupedByYear' => $groupedByYear,
        ]);
    }

    private function profilePhotoDataUrl(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        if (str_contains($relativePath, '..')) {
            return null;
        }

        $disk = Storage::disk('college_uploads');
        if (! $disk->exists($relativePath)) {
            return null;
        }

        $full = $disk->path($relativePath);
        if (! is_readable($full) || filesize($full) > 1_500_000) {
            return null;
        }

        $mime = @mime_content_type($full) ?: 'image/jpeg';
        $raw = @file_get_contents($full);
        if ($raw === false) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($raw);
    }
}
