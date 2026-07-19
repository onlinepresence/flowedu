<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Program;
use App\Models\Course;
use App\Models\Student;
use App\Models\LeaveRequest;
use App\Models\StaffLeaveType;
use App\Livewire\Admin\Students\StudentShowPage;
use App\Livewire\Admin\Grading\EnterGradesPage;
use App\Livewire\Admin\Staff\StaffLeavesPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class AdminScopingTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        UserRole::ensureSystemRoles();
        $this->createTestSchool();
    }

    private function createFaculty(string $name): Faculty
    {
        return Faculty::query()->create(['name' => $name]);
    }

    private function createDepartment(string $name, Faculty $faculty): Department
    {
        return Department::query()->forceCreate([
            'name' => $name,
            'faculty_id' => $faculty->id,
        ]);
    }

    private function createProgram(string $name, Department $department): Program
    {
        return Program::query()->create([
            'name' => $name,
            'department_id' => $department->id,
            'certificate' => 'Cert',
            'cost' => 100,
            'program_length' => 3,
        ]);
    }

    private function createCourse(string $name, string $code, Program $program): Course
    {
        return Course::query()->forceCreate([
            'name' => $name,
            'code' => $code,
            'program_id' => $program->id,
            'course_semester' => '1',
            'year_level' => '1',
        ]);
    }

    private function createScopedAdmin(string $roleName, ?int $departmentId = null, ?int $facultyId = null): User
    {
        $roleId = UserRole::query()->where('role_name', $roleName)->value('id');
        $user = User::factory()->create([
            'type' => 'admin',
            'username' => 'admin_' . uniqid(),
        ]);
        
        Admin::query()->forceCreate([
            'user_id' => $user->id,
            'type' => $roleId,
            'department_id' => $departmentId,
            'faculty_id' => $facultyId,
        ]);

        return $user;
    }

    private function createStudent(string $indexNumber, Department $department, Program $program): Student
    {
        $hall = \App\Models\Hall::query()->firstOrCreate(
            ['name' => 'Test Hall'],
            ['cost' => 0, 'period' => 'per_year']
        );
        $user = User::factory()->create(['type' => 'student']);
        return Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => $indexNumber,
            'admission_index' => $indexNumber,
            'lastname' => 'Doe',
            'firstname' => 'John',
            'gender' => 'male',
            'nationality' => 'GH',
            'date_of_birth' => '2001-01-01',
            'phone_number' => '0240000000',
            'approved' => true,
            'department_id' => $department->id,
            'program_id' => $program->id,
            'contact_address' => 'Addr',
            'hall_id' => $hall->id,
            'profile_pic' => 'pic.png',
        ]);
    }

    public function test_admin_scoping_helpers(): void
    {
        $faculty1 = $this->createFaculty('Faculty One');
        $faculty2 = $this->createFaculty('Faculty Two');

        $dept1 = $this->createDepartment('Dept One', $faculty1);
        $dept2 = $this->createDepartment('Dept Two', $faculty2);

        $prog1 = $this->createProgram('Prog One', $dept1);
        $prog2 = $this->createProgram('Prog Two', $dept2);

        $course1 = $this->createCourse('Course One', 'C1', $prog1);
        $course2 = $this->createCourse('Course Two', 'C2', $prog2);

        $student1 = $this->createStudent('S1', $dept1, $prog1);
        $student2 = $this->createStudent('S2', $dept2, $prog2);

        // Global admin (no department/faculty constraints)
        $globalUser = $this->createScopedAdmin('owner');
        $globalAdmin = $globalUser->admin;

        $this->assertTrue($globalAdmin->canAccessDepartment($dept1->id));
        $this->assertTrue($globalAdmin->canAccessDepartment($dept2->id));
        $this->assertTrue($globalAdmin->canAccessFaculty($faculty1->id));
        $this->assertTrue($globalAdmin->canAccessFaculty($faculty2->id));
        $this->assertTrue($globalAdmin->canAccessStudent($student1));
        $this->assertTrue($globalAdmin->canAccessStudent($student2));
        $this->assertTrue($globalAdmin->canAccessProgram($prog1));
        $this->assertTrue($globalAdmin->canAccessProgram($prog2));
        $this->assertTrue($globalAdmin->canAccessCourse($course1));
        $this->assertTrue($globalAdmin->canAccessCourse($course2));

        // Dept 1 admin (HOD)
        $deptUser = $this->createScopedAdmin('hod', $dept1->id);
        $deptAdmin = $deptUser->admin;

        $this->assertTrue($deptAdmin->canAccessDepartment($dept1->id));
        $this->assertFalse($deptAdmin->canAccessDepartment($dept2->id));
        $this->assertTrue($deptAdmin->canAccessFaculty($faculty1->id));
        $this->assertFalse($deptAdmin->canAccessFaculty($faculty2->id));
        $this->assertTrue($deptAdmin->canAccessStudent($student1));
        $this->assertFalse($deptAdmin->canAccessStudent($student2));
        $this->assertTrue($deptAdmin->canAccessProgram($prog1));
        $this->assertFalse($deptAdmin->canAccessProgram($prog2));
        $this->assertTrue($deptAdmin->canAccessCourse($course1));
        $this->assertFalse($deptAdmin->canAccessCourse($course2));

        // Faculty 1 admin (Dean)
        $facultyUser = $this->createScopedAdmin('principal', null, $faculty1->id);
        $facultyAdmin = $facultyUser->admin;

        $this->assertTrue($facultyAdmin->canAccessDepartment($dept1->id));
        $this->assertFalse($facultyAdmin->canAccessDepartment($dept2->id));
        $this->assertTrue($facultyAdmin->canAccessFaculty($faculty1->id));
        $this->assertFalse($facultyAdmin->canAccessFaculty($faculty2->id));
        $this->assertTrue($facultyAdmin->canAccessStudent($student1));
        $this->assertFalse($facultyAdmin->canAccessStudent($student2));
        $this->assertTrue($facultyAdmin->canAccessProgram($prog1));
        $this->assertFalse($facultyAdmin->canAccessProgram($prog2));
        $this->assertTrue($facultyAdmin->canAccessCourse($course1));
        $this->assertFalse($facultyAdmin->canAccessCourse($course2));
    }

    public function test_student_show_page_scoping(): void
    {
        $faculty1 = $this->createFaculty('Faculty One');
        $faculty2 = $this->createFaculty('Faculty Two');

        $dept1 = $this->createDepartment('Dept One', $faculty1);
        $dept2 = $this->createDepartment('Dept Two', $faculty2);

        $prog1 = $this->createProgram('Prog One', $dept1);
        $prog2 = $this->createProgram('Prog Two', $dept2);

        $student1 = $this->createStudent('S1', $dept1, $prog1);
        $student2 = $this->createStudent('S2', $dept2, $prog2);

        $deptUser = $this->createScopedAdmin('registrar', $dept1->id);

        // Can access student 1
        Livewire::actingAs($deptUser)
            ->test(StudentShowPage::class, ['index_number' => $student1->index_number])
            ->assertStatus(200);

        // Cannot access student 2
        Livewire::actingAs($deptUser)
            ->test(StudentShowPage::class, ['index_number' => $student2->index_number])
            ->assertStatus(403);
    }

    public function test_enter_grades_page_scoping(): void
    {
        $faculty1 = $this->createFaculty('Faculty One');
        $faculty2 = $this->createFaculty('Faculty Two');

        $dept1 = $this->createDepartment('Dept One', $faculty1);
        $dept2 = $this->createDepartment('Dept Two', $faculty2);

        $prog1 = $this->createProgram('Prog One', $dept1);
        $prog2 = $this->createProgram('Prog Two', $dept2);

        $course1 = $this->createCourse('Course One', 'C1', $prog1);
        $course2 = $this->createCourse('Course Two', 'C2', $prog2);

        $deptUser = $this->createScopedAdmin('principal', $dept1->id);

        Livewire::actingAs($deptUser)
            ->test(EnterGradesPage::class, ['programId' => $prog1->id, 'courseId' => $course1->id])
            ->assertStatus(200);

        Livewire::actingAs($deptUser)
            ->test(EnterGradesPage::class, ['programId' => $prog2->id])
            ->assertStatus(403);
    }

    public function test_staff_leaves_page_scoping(): void
    {
        $faculty1 = $this->createFaculty('Faculty One');
        $faculty2 = $this->createFaculty('Faculty Two');

        $dept1 = $this->createDepartment('Dept One', $faculty1);
        $dept2 = $this->createDepartment('Dept Two', $faculty2);

        $leaveType = StaffLeaveType::query()->create([
            'name' => 'Annual',
            'max_leave_days' => 20,
        ]);

        $hodUser1 = $this->createScopedAdmin('hod', $dept1->id);
        $dept2->hod = $hodUser1->id;
        $dept2->save();
        
        $teacherUser2 = User::factory()->create(['type' => 'teacher']);
        \App\Models\Teacher::query()->forceCreate([
            'user_id' => $teacherUser2->id,
            'department_id' => $dept2->id,
            'lastname' => 'Smith',
            'othernames' => 'John',
            'gender' => 'male',
            'phone_number' => '0240000002',
            'nationality' => 'GH',
        ]);

        $leaveRequest2 = LeaveRequest::query()->forceCreate([
            'user_id' => $teacherUser2->id,
            'staff_leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-05',
            'requested_days' => 5,
            'status' => 'pending',
            'current_stage' => 'pending_hod',
            'reason' => 'Rest',
            'is_emergency' => false,
        ]);

        Livewire::actingAs($hodUser1)
            ->test(StaffLeavesPage::class)
            ->set('selected_request_id', $leaveRequest2->id)
            ->call('approveRequest')
            ->assertStatus(403);
    }
}
