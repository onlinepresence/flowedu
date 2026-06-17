<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Practicum;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeachingPracticeSupervision;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class AdminPracticumAssignPage extends Component
{
    use DispatchesCollegeToasts, WithFileUploads, WithPagination;

    public ?int $academicSessionId = null;
    public ?int $selectedStudentId = null;
    public ?int $selectedTeacherId = null;
    public string $partnershipSchool = '';
    public string $search = '';

    public string $studentSearch = '';
    public string $teacherSearch = '';

    public bool $showAssignModal = false;
    public bool $showImportModal = false;
    public $rosterFile;

    protected $updatesQueryString = ['search'];

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAdminPermission('nav_practicum_assign'), 403);

        $session = AcademicSession::where('is_current', true)->first() ?? AcademicSession::first();
        if ($session) {
            $this->academicSessionId = (int)$session->id;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openImportModal(): void
    {
        $this->resetValidation();
        $this->rosterFile = null;
        $this->showImportModal = true;
        $this->dispatch('open-modal', 'import-roster-modal');
    }

    public function openAssignModal(): void
    {
        $this->resetValidation();
        $this->selectedStudentId = null;
        $this->selectedTeacherId = null;
        $this->partnershipSchool = '';
        $this->studentSearch = '';
        $this->teacherSearch = '';
        $this->showAssignModal = true;
        $this->dispatch('open-modal', 'assign-trainee-modal');
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->dispatch('close-modal', 'assign-trainee-modal');
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->dispatch('close-modal', 'import-roster-modal');
    }

    public function saveAssignment(): void
    {
        $this->validate([
            'selectedStudentId' => ['required', 'exists:students,id'],
            'selectedTeacherId' => ['required', 'exists:teachers,id'],
            'partnershipSchool' => ['required', 'string', 'min:3', 'max:255'],
            'academicSessionId' => ['required', 'exists:academic_sessions,id'],
        ]);

        // Check if student is already assigned this session
        $existing = TeachingPracticeSupervision::where('student_id', $this->selectedStudentId)
            ->where('academic_session_id', $this->academicSessionId)
            ->first();

        if ($existing) {
            $this->addError('selectedStudentId', __('This student is already assigned a supervisor for this session.'));
            return;
        }

        TeachingPracticeSupervision::create([
            'student_id' => $this->selectedStudentId,
            'teacher_id' => $this->selectedTeacherId,
            'academic_session_id' => $this->academicSessionId,
            'partnership_school' => $this->partnershipSchool,
            'status' => 'assigned',
        ]);

        $this->showAssignModal = false;
        $this->dispatch('close-modal', 'assign-trainee-modal');
        $this->collegeToast(__('Student trainee assigned successfully.'));
    }

    public function deleteAssignment(int $id): void
    {
        $supervision = TeachingPracticeSupervision::findOrFail($id);
        $supervision->delete();
        $this->collegeToast(__('Assignment deleted successfully.'), 'danger');
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['student_email', 'teacher_email', 'partnership_school']);
            fclose($file);
        };

        return response()->streamDownload($callback, 'practicum_roster_template.csv', $headers);
    }

    public function exportRoster()
    {
        if (!$this->academicSessionId) {
            $this->collegeToast(__('No academic session active.'), 'danger');
            return null;
        }

        $session = AcademicSession::findOrFail($this->academicSessionId);
        $supervisions = TeachingPracticeSupervision::with(['student.user', 'teacher.user'])
            ->where('academic_session_id', $this->academicSessionId)
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        $filename = 'practicum_roster_' . Str::slug($session->name) . '.csv';

        $callback = function() use ($supervisions) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['student_name', 'student_email', 'student_index', 'teacher_name', 'teacher_email', 'partnership_school', 'status', 'score']);
            foreach ($supervisions as $s) {
                fputcsv($file, [
                    $s->student->user->name,
                    $s->student->user->email,
                    $s->student->index_number,
                    $s->teacher->user->name,
                    $s->teacher->user->email,
                    $s->partnership_school,
                    $s->status,
                    $s->score ?? ''
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function importRoster(): void
    {
        $this->validate([
            'rosterFile' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        if (!$this->academicSessionId) {
            $this->collegeToast(__('No academic session active.'), 'danger');
            return;
        }

        $path = $this->rosterFile->getRealPath();
        $file = fopen($path, 'r');
        $header = fgetcsv($file);

        if (!$header || !in_array('student_email', $header) || !in_array('teacher_email', $header) || !in_array('partnership_school', $header)) {
            $this->addError('rosterFile', __('Invalid CSV columns. Template must contain: student_email, teacher_email, partnership_school.'));
            fclose($file);
            return;
        }

        $inserted = 0;
        $skipped = 0;

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);
            $studentEmail = trim($data['student_email'] ?? '');
            $teacherEmail = trim($data['teacher_email'] ?? '');
            $school = trim($data['partnership_school'] ?? '');

            if ($studentEmail === '' || $teacherEmail === '' || $school === '') {
                $skipped++;
                continue;
            }

            $student = Student::whereHas('user', fn($q) => $q->where('email', $studentEmail))->first();
            $teacher = Teacher::whereHas('user', fn($q) => $q->where('email', $teacherEmail))->first();

            if (!$student || !$teacher) {
                $skipped++;
                continue;
            }

            TeachingPracticeSupervision::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_session_id' => $this->academicSessionId,
                ],
                [
                    'teacher_id' => $teacher->id,
                    'partnership_school' => $school,
                    'status' => 'assigned',
                ]
            );

            $inserted++;
        }

        fclose($file);
        $this->rosterFile = null;
        $this->showImportModal = false;
        $this->dispatch('close-modal', 'import-roster-modal');

        $this->collegeToast(__("Import completed. Assigned: :assigned, Skipped: :skipped", ['assigned' => $inserted, 'skipped' => $skipped]));
    }

    public function render(): View
    {
        $sessions = AcademicSession::orderBy('name', 'desc')->get();

        $query = TeachingPracticeSupervision::with(['student.user', 'teacher.user'])
            ->where('academic_session_id', $this->academicSessionId);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->whereHas('student.user', function ($sq) {
                    $sq->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('teacher.user', function ($tq) {
                    $tq->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('partnership_school', 'like', '%' . $this->search . '%');
            });
        }

        $supervisions = $query->paginate(15);

        // Fetch students not yet assigned for assignment modal drop-down
        $assignedStudentIds = TeachingPracticeSupervision::where('academic_session_id', $this->academicSessionId)
            ->pluck('student_id')
            ->all();

        $availableStudentsQuery = Student::with('user')
            ->whereNotIn('id', $assignedStudentIds);

        if ($this->studentSearch !== '') {
            $availableStudentsQuery->where(function($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->where('name', 'like', '%' . $this->studentSearch . '%')
                        ->orWhere('email', 'like', '%' . $this->studentSearch . '%');
                })->orWhere('index_number', 'like', '%' . $this->studentSearch . '%');
            });
        }

        $availableStudents = $availableStudentsQuery->limit(5)->get();

        $teachersQuery = Teacher::with('user');
        if ($this->teacherSearch !== '') {
            $teachersQuery->where(function($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->where('name', 'like', '%' . $this->teacherSearch . '%')
                        ->orWhere('email', 'like', '%' . $this->teacherSearch . '%');
                });
            });
        }
        $teachers = $teachersQuery->limit(5)->get();

        return view('livewire.admin.practicum.admin-practicum-assign-page', [
            'sessions' => $sessions,
            'supervisions' => $supervisions,
            'availableStudents' => $availableStudents,
            'teachers' => $teachers,
        ])->layout('components.layouts.admin', [
            'title' => __('TP Supervisor Assignment'),
            'headerTitle' => __('Supervision Assignment'),
            'headerDescription' => __('Assign student trainees to supervisors for the selected academic year, or upload a bulk roster template.'),
        ]);
    }
}
