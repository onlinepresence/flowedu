<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeachingPracticeSupervision;
use App\Models\SharedLessonPlan;
use App\Models\SchoolLicence;
use Illuminate\Database\Seeder;

class PracticumDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Enable module_practicum on the current license
        $licence = SchoolLicence::first();
        if ($licence) {
            $licence->update([
                'module_practicum' => true,
            ]);
        }

        // 2. Fetch current session, teachers, and students
        $session = AcademicSession::where('is_current', true)->first() ?? AcademicSession::first();
        if (!$session) {
            return;
        }

        $teachers = Teacher::with('user')->get();
        $students = Student::with('user')->get();

        if ($teachers->isEmpty() || $students->isEmpty()) {
            return;
        }

        // 3. Create a few supervisions
        $partnershipSchools = [
            'Accra College of Education Demonstration School',
            'Presbyterian Practice School, Akropong',
            'Methodist Girls High School Practicum Center',
            'University Primary School, Legon',
        ];

        // Let's pair up to 4 students with the first couple of teachers
        $pairCount = min($students->count(), 4);
        for ($i = 0; $i < $pairCount; $i++) {
            $student = $students[$i];
            $teacher = $teachers[$i % $teachers->count()];
            $school = $partnershipSchools[$i % count($partnershipSchools)];

            $evaluated = $i >= 2; // Evaluate some, leave some assigned

            TeachingPracticeSupervision::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_session_id' => $session->id,
                ],
                [
                    'teacher_id' => $teacher->id,
                    'partnership_school' => $school,
                    'status' => $evaluated ? 'evaluated' : 'assigned',
                    'score' => $evaluated ? (80.00 + ($i * 4.5)) : null,
                    'evaluation_notes' => $evaluated ? "The trainee displayed standard lesson planning skills. Delivery was engaging, though student participation could be optimized further." : null,
                    'evaluated_at' => $evaluated ? now()->subDays(2) : null,
                ]
            );
        }

        // 4. Create some shared lesson plans
        foreach ($teachers as $teacher) {
            if (!$teacher->department_id) {
                continue;
            }

            SharedLessonPlan::create([
                'teacher_id' => $teacher->id,
                'department_id' => $teacher->department_id,
                'title' => 'Weekly Lesson Note Template - Departmental Outline',
                'description' => 'A template lesson note covering pedagogy standards and student engagement metrics, uploaded for teacher reference.',
                'file_path' => 'demo/lesson_plan_template.pdf',
                'file_name' => 'lesson_plan_template.pdf',
                'file_size' => 102400, // 100 KB
            ]);
        }
    }
}
