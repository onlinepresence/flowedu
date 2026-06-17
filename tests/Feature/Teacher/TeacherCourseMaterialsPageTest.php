<?php

declare(strict_types=1);

namespace Tests\Feature\Teacher;

use App\Livewire\Teacher\TeacherCourseMaterialsPage;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Teacher;
use App\Models\TimetableClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class TeacherCourseMaterialsPageTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    /**
     * @return array{program: Program, course: Course, teacher: Teacher, user: User}
     */
    private function seedTeacherWithTimetableClass(): array
    {
        $this->createTestSchool();

        $faculty = Faculty::query()->create(['name' => 'Science']);
        $department = Department::query()->forceCreate([
            'name' => 'Computing',
            'faculty_id' => $faculty->id,
        ]);
        $program = Program::query()->forceCreate([
            'name' => 'BSc CS',
            'department_id' => $department->id,
            'certificate' => 'BSc',
            'cost' => 100,
        ]);

        $user = User::factory()->create([
            'type' => 'teacher',
            'username' => 'lec1',
        ]);

        $teacher = Teacher::query()->forceCreate([
            'user_id' => $user->id,
            'lastname' => 'T',
            'othernames' => 'One',
            'password_reset_required' => false,
            'is_onboarded' => true,
        ]);

        $course = Course::query()->forceCreate([
            'code' => 'CS101',
            'name' => 'Intro CS',
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'course_semester' => '1',
            'year_level' => '1',
        ]);

        TimetableClass::query()->forceCreate([
            'timetable_id' => null,
            'program_id' => $program->id,
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'day' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'venue' => 'LT 1',
        ]);

        return compact('program', 'course', 'teacher', 'user');
    }

    public function test_teacher_without_timetable_class_sees_upload_disabled_notice(): void
    {
        $this->createTestSchool();

        $user = User::factory()->create(['type' => 'teacher', 'username' => 't2']);
        Teacher::query()->forceCreate([
            'user_id' => $user->id,
            'lastname' => 'X',
            'othernames' => 'Y',
            'password_reset_required' => false,
            'is_onboarded' => true,
        ]);

        $this->actingAs($user)
            ->get(route('teacher.courses.materials'))
            ->assertOk()
            ->assertSee(__('Uploads are available only when you have at least one class on your timetable'), false);
    }

    public function test_teacher_can_upload_material_when_assigned_timetable_class(): void
    {
        ['course' => $course, 'user' => $user, 'teacher' => $teacher] = $this->seedTeacherWithTimetableClass();

        Storage::fake('local');
        Storage::fake('college_uploads');

        $pending = 'filepond-tmp/'.$user->id.'/mat.pdf';
        Storage::disk('local')->put($pending, '%PDF fake');

        Livewire::actingAs($user)
            ->test(TeacherCourseMaterialsPage::class)
            ->set('uploadTitle', 'Lecture 1')
            ->set('uploadCourseId', $course->id)
            ->set('materialFilePond', $pending)
            ->call('saveMaterial')
            ->assertHasNoErrors();

        $material = CourseMaterial::query()->where('teacher_id', $teacher->id)->first();
        $this->assertNotNull($material);
        $this->assertSame($course->id, (int) $material->course_id);
        $this->assertStringStartsWith('teachers/course-materials/', $material->file_path);
        Storage::disk('college_uploads')->assertExists($material->file_path);
    }

    public function test_teacher_can_download_own_material(): void
    {
        ['course' => $course, 'user' => $user, 'teacher' => $teacher] = $this->seedTeacherWithTimetableClass();

        Storage::fake('college_uploads');
        $path = 'teachers/course-materials/doc.pdf';
        Storage::disk('college_uploads')->put($path, 'pdf-bytes');

        $material = CourseMaterial::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'title' => 'Doc',
            'file_path' => $path,
            'file_type' => 'PDF',
            'status' => 'pending',
            'published' => false,
        ]);

        $this->actingAs($user)
            ->get(route('teacher.courses.materials.download', $material))
            ->assertOk();
    }
}
