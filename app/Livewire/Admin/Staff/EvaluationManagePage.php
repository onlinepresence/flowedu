<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EvaluationManagePage extends Component
{
    use DispatchesCollegeToasts;

    public string $form_code = '';

    public string $tab = 'questions';

    public ?EvaluationForm $form = null;

    public string $title = '';

    public string $academic_year = '';

    public string $start_time = '';

    public string $end_time = '';

    public string $control_type = 'auto';

    public bool $is_active = true;

    public string $new_question_text = '';

    public string $new_rating_type = 'scale_5';

    public bool $new_is_required = true;

    public int $new_question_order = 1;

    /** @var array<int, string> */
    public array $new_options = [''];

    public ?int $editingQuestionId = null;

    // Reporting & Filters
    public ?int $selectedTeacherId = null;

    public string $searchTeacher = '';

    // Paginated Text Modal
    public ?int $viewingTextQuestionId = null;

    public int $textPage = 1;

    public int $textPerPage = 10;

    // Detailed / Summarized View
    public string $reportView = 'summarized';

    public int $detailedPage = 1;

    public int $detailedPerPage = 10;

    public ?int $filterDepartmentId = null;

    public ?int $filterProgramId = null;

    public ?string $filterYearLevel = null;

    public ?int $viewingResponseId = null;

    public function mount(string $form_code, ?string $tab = null): void
    {
        $this->form_code = $form_code;
        if ($tab !== null && $tab !== '') {
            $this->tab = $tab;
        }

        $this->form = EvaluationForm::query()->where('unique_code', $form_code)->firstOrFail();
        $this->title = (string) ($this->form->title ?? '');
        $this->academic_year = (string) ($this->form->academic_year ?? '');
        $this->start_time = $this->form->start_time?->format('Y-m-d\TH:i') ?? '';
        $this->end_time = $this->form->end_time?->format('Y-m-d\TH:i') ?? '';
        $this->control_type = (string) ($this->form->control_type ?? 'auto');
        $this->is_active = (bool) $this->form->is_active;
        $this->new_question_order = $this->nextQuestionOrder();
    }

    public function saveDetails(): void
    {
        if ($this->form === null) {
            return;
        }

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'academic_year' => ['required', 'string', 'max:9'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],
            'control_type' => ['required', 'in:auto,manual'],
            'is_active' => ['boolean'],
        ]);

        // Legacy parity: academic year is fixed once form is created.
        $this->academic_year = (string) ($this->form->academic_year ?? $this->academic_year);

        $this->form->forceFill([
            'title' => $this->title,
            'academic_year' => $this->academic_year,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'control_type' => $this->control_type,
            'is_active' => $this->is_active,
            'last_edited_by' => auth()->id(),
        ])->save();

        $this->collegeToast(__('Evaluation details saved.'));
    }

    public function saveQuestion(): void
    {
        if ($this->form === null) {
            return;
        }

        $this->validate([
            'new_question_text' => ['required', 'string', 'max:2000'],
            'new_rating_type' => ['required', 'in:scale_5,scale_10,text_short,text_long,boolean,select_single,select_multiple'],
            'new_is_required' => ['boolean'],
            'new_question_order' => ['required', 'integer', 'min:1'],
        ]);

        $options = null;
        if (in_array($this->new_rating_type, ['select_single', 'select_multiple'], true)) {
            $clean = collect($this->new_options)
                ->map(fn ($option) => trim((string) $option))
                ->filter(fn ($option) => $option !== '')
                ->values()
                ->all();

            if (count($clean) < 2) {
                $this->addError('new_options', __('Choice questions require at least two options.'));

                return;
            }

            $options = $clean;
        }

        $maxOrder = $this->nextQuestionOrder();
        $targetOrder = max(1, min($this->new_question_order, $maxOrder));

        if ($this->editingQuestionId === null) {
            EvaluationQuestion::query()
                ->where('form_id', $this->form->id)
                ->whereNull('deleted_at')
                ->where('question_order', '>=', $targetOrder)
                ->increment('question_order');

            EvaluationQuestion::query()->create([
                'form_id' => $this->form->id,
                'question_text' => $this->new_question_text,
                'question_order' => $targetOrder,
                'rating_type' => $this->new_rating_type,
                'is_required' => $this->new_is_required,
                'options_json' => $options,
                'created_by' => auth()->id(),
                'last_edited_by' => auth()->id(),
            ]);

            $this->collegeToast(__('Question added.'));
        } else {
            $question = EvaluationQuestion::query()
                ->where('form_id', $this->form->id)
                ->whereNull('deleted_at')
                ->findOrFail($this->editingQuestionId);

            $oldOrder = (int) $question->question_order;
            if ($targetOrder !== $oldOrder) {
                if ($targetOrder > $oldOrder) {
                    EvaluationQuestion::query()
                        ->where('form_id', $this->form->id)
                        ->whereNull('deleted_at')
                        ->whereBetween('question_order', [$oldOrder + 1, $targetOrder])
                        ->decrement('question_order');
                } else {
                    EvaluationQuestion::query()
                        ->where('form_id', $this->form->id)
                        ->whereNull('deleted_at')
                        ->whereBetween('question_order', [$targetOrder, $oldOrder - 1])
                        ->increment('question_order');
                }
            }

            $question->forceFill([
                'question_text' => $this->new_question_text,
                'question_order' => $targetOrder,
                'rating_type' => $this->new_rating_type,
                'is_required' => $this->new_is_required,
                'options_json' => $options,
                'last_edited_by' => auth()->id(),
            ])->save();

            $this->collegeToast(__('Question updated.'));
        }

        $this->resetQuestionForm();
    }

    public function removeQuestion(int $questionId): void
    {
        if ($this->form === null) {
            return;
        }

        $q = EvaluationQuestion::query()
            ->where('form_id', $this->form->id)
            ->whereKey($questionId)
            ->first();

        if ($q === null) {
            return;
        }

        $q->forceFill([
            'deleted_at' => now(),
            'deleted_by' => auth()->id(),
        ])->save();

        $remaining = EvaluationQuestion::query()
            ->where('form_id', $this->form->id)
            ->whereNull('deleted_at')
            ->orderBy('question_order')
            ->get();

        foreach ($remaining as $index => $row) {
            $expected = $index + 1;
            if ((int) $row->question_order !== $expected) {
                $row->forceFill(['question_order' => $expected])->save();
            }
        }

        $this->collegeToast(__('Question removed.'));
        if ($this->editingQuestionId === $questionId) {
            $this->resetQuestionForm();
        }
    }

    public function editQuestion(int $questionId): void
    {
        if ($this->form === null) {
            return;
        }

        $question = EvaluationQuestion::query()
            ->where('form_id', $this->form->id)
            ->whereNull('deleted_at')
            ->findOrFail($questionId);

        $this->editingQuestionId = $question->id;
        $this->new_question_text = (string) $question->question_text;
        $this->new_rating_type = (string) $question->rating_type;
        $this->new_is_required = (bool) $question->is_required;
        $this->new_question_order = (int) ($question->question_order ?? 1);
        $this->new_options = is_array($question->options_json) && $question->options_json !== []
            ? array_values($question->options_json)
            : [''];
    }

    public function cancelQuestionEdit(): void
    {
        $this->resetQuestionForm();
    }

    public function addOptionField(): void
    {
        $this->new_options[] = '';
    }

    public function removeOptionField(int $index): void
    {
        if (! array_key_exists($index, $this->new_options)) {
            return;
        }

        unset($this->new_options[$index]);
        $this->new_options = array_values($this->new_options);
        if ($this->new_options === []) {
            $this->new_options = [''];
        }
    }

    public function moveQuestionUp(int $questionId): void
    {
        $this->moveQuestion($questionId, -1);
    }

    public function moveQuestionDown(int $questionId): void
    {
        $this->moveQuestion($questionId, 1);
    }

    private function moveQuestion(int $questionId, int $direction): void
    {
        if ($this->form === null) {
            return;
        }

        $question = EvaluationQuestion::query()
            ->where('form_id', $this->form->id)
            ->whereNull('deleted_at')
            ->findOrFail($questionId);

        $targetOrder = (int) $question->question_order + $direction;
        if ($targetOrder < 1) {
            return;
        }

        $swap = EvaluationQuestion::query()
            ->where('form_id', $this->form->id)
            ->whereNull('deleted_at')
            ->where('question_order', $targetOrder)
            ->first();
        if ($swap === null) {
            return;
        }

        $currentOrder = (int) $question->question_order;
        $swap->forceFill(['question_order' => $currentOrder])->save();
        $question->forceFill(['question_order' => $targetOrder])->save();
    }

    private function nextQuestionOrder(): int
    {
        if ($this->form === null) {
            return 1;
        }

        $maxOrder = (int) EvaluationQuestion::query()
            ->where('form_id', $this->form->id)
            ->whereNull('deleted_at')
            ->max('question_order');

        return max(1, $maxOrder + 1);
    }

    private function resetQuestionForm(): void
    {
        $this->editingQuestionId = null;
        $this->new_question_text = '';
        $this->new_rating_type = 'scale_5';
        $this->new_is_required = true;
        $this->new_question_order = $this->nextQuestionOrder();
        $this->new_options = [''];
        $this->resetErrorBag(['new_question_text', 'new_rating_type', 'new_is_required', 'new_question_order', 'new_options']);
    }

    public function selectTeacher(?int $teacherId): void
    {
        $this->selectedTeacherId = $teacherId;
        $this->detailedPage = 1;
    }

    public function viewAllTextResponses(int $questionId): void
    {
        $this->viewingTextQuestionId = $questionId;
        $this->textPage = 1;
    }

    public function closeTextModal(): void
    {
        $this->viewingTextQuestionId = null;
    }

    public function nextTextPage(): void
    {
        $this->textPage++;
    }

    public function prevTextPage(): void
    {
        if ($this->textPage > 1) {
            $this->textPage--;
        }
    }

    public function updatedFilterDepartmentId(): void
    {
        $this->filterProgramId = null;
        $this->detailedPage = 1;
    }

    public function updatedFilterProgramId(): void
    {
        $this->detailedPage = 1;
    }

    public function updatedFilterYearLevel(): void
    {
        $this->detailedPage = 1;
    }

    public function updatedReportView(): void
    {
        $this->detailedPage = 1;
    }

    public function nextDetailedPage(): void
    {
        $this->detailedPage++;
    }

    public function prevDetailedPage(): void
    {
        if ($this->detailedPage > 1) {
            $this->detailedPage--;
        }
    }

    public function viewResponseDetails(int $responseId): void
    {
        $this->viewingResponseId = $responseId;
    }

    public function closeResponseDetailsModal(): void
    {
        $this->viewingResponseId = null;
    }

    public function downloadReport(string $format, ?int $teacherId = null)
    {
        if ($this->form === null) {
            return null;
        }

        $query = $this->form->responses()->where('status', 'submitted');
        if ($teacherId !== null) {
            $query->where('teacher_id', $teacherId);
        }
        if ($this->filterDepartmentId) {
            $query->where('student_department_id', $this->filterDepartmentId);
        }
        if ($this->filterProgramId) {
            $query->whereHas('studentUser.student', function ($q) {
                $q->where('program_id', $this->filterProgramId);
            });
        }
        if ($this->filterYearLevel) {
            $query->whereHas('studentUser.student', function ($q) {
                $q->where('current_year', $this->filterYearLevel);
            });
        }
        $responses = $query->with(['teacherUser.teacher.department', 'details'])->get();

        $questions = EvaluationQuestion::query()
            ->where('form_id', $this->form->id)
            ->whereNull('deleted_at')
            ->orderBy('question_order')
            ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            __('Response ID'),
            __('Submission Date'),
        ];
        if ($teacherId === null) {
            $headers[] = __('Lecturer Name');
            $headers[] = __('Lecturer Department');
        }

        foreach ($questions as $q) {
            $headers[] = $q->question_text;
        }

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($responses as $response) {
            $row = [
                $response->response_code,
                $response->submitted_at?->format('Y-m-d H:i') ?? '',
            ];

            if ($teacherId === null) {
                $row[] = $response->teacherUser?->name ?? __('N/A');
                $row[] = $response->teacherUser?->teacher?->department?->name ?? __('N/A');
            }

            $detailsMap = $response->details->keyBy('question_id');

            foreach ($questions as $q) {
                $detail = $detailsMap->get($q->id);
                if (!$detail) {
                    $row[] = '';
                    continue;
                }

                if (in_array($q->rating_type, ['scale_5', 'scale_10'], true)) {
                    $row[] = $detail->answer_value ?? '';
                } elseif ($q->rating_type === 'boolean') {
                    $row[] = $detail->answer_value === null ? '' : ($detail->answer_value === 1 ? __('Yes') : __('No'));
                } elseif ($q->rating_type === 'select_multiple') {
                    $decoded = json_decode($detail->answer_text ?? '', true);
                    $row[] = is_array($decoded) ? implode(', ', $decoded) : '';
                } else {
                    $row[] = $detail->answer_text ?? '';
                }
            }

            $sheet->fromArray($row, null, 'A' . $rowNum);
            $rowNum++;
        }

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = str_replace(' ', '_', strtolower($this->form->title)) . '_report_' . now()->format('YmdHis');

        if ($format === 'xlsx') {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $filename .= '.xlsx';
        } else {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
            $contentType = 'text/csv';
            $filename .= '.csv';
        }

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function render(): View
    {
        $questions = collect();
        $stats = ['responses' => 0, 'submitted' => 0, 'teachers_evaluated' => 0, 'participation_rate' => 0];
        $questionStats = [];
        $teachers = collect();
        $textQuestion = null;
        $paginatedTextResponses = collect();
        $textTotal = 0;
        $textMaxPage = 1;

        if ($this->form !== null) {
            $questions = EvaluationQuestion::query()
                ->where('form_id', $this->form->id)
                ->whereNull('deleted_at')
                ->orderBy('question_order')
                ->get();

            $baseResponsesQuery = $this->form->responses();
            if ($this->filterDepartmentId) {
                $baseResponsesQuery->where('student_department_id', $this->filterDepartmentId);
            }
            if ($this->filterProgramId) {
                $baseResponsesQuery->whereHas('studentUser.student', function ($q) {
                    $q->where('program_id', $this->filterProgramId);
                });
            }
            if ($this->filterYearLevel) {
                $baseResponsesQuery->whereHas('studentUser.student', function ($q) {
                    $q->where('current_year', $this->filterYearLevel);
                });
            }

            $stats['responses'] = (clone $baseResponsesQuery)->count();
            $stats['submitted'] = (clone $baseResponsesQuery)->where('status', 'submitted')->count();

            // Count distinct teachers evaluated
            $stats['teachers_evaluated'] = (clone $baseResponsesQuery)
                ->where('status', 'submitted')
                ->whereNotNull('teacher_id')
                ->distinct('teacher_id')
                ->count('teacher_id');

            // Participation rate
            $totalActiveStudentsQuery = \App\Models\Student::query()->where('approved', true);
            if ($this->filterDepartmentId) {
                $totalActiveStudentsQuery->where('department_id', $this->filterDepartmentId);
            }
            if ($this->filterProgramId) {
                $totalActiveStudentsQuery->where('program_id', $this->filterProgramId);
            }
            if ($this->filterYearLevel) {
                $totalActiveStudentsQuery->where('current_year', $this->filterYearLevel);
            }
            $totalActiveStudents = $totalActiveStudentsQuery->count();
            $stats['participation_rate'] = $totalActiveStudents > 0
                ? round(($stats['submitted'] / $totalActiveStudents) * 100, 1)
                : 0;

            // Load teachers for the sidebar/filter grid (only evaluated ones matching filters)
            $teachersQuery = \App\Models\Teacher::query()
                ->whereIn('user_id', function ($q) {
                    $q->select('teacher_id')
                      ->from('evaluation_responses')
                      ->where('form_id', $this->form->id)
                      ->where('status', 'submitted')
                      ->whereNotNull('teacher_id');

                    if ($this->filterDepartmentId) {
                        $q->where('student_department_id', $this->filterDepartmentId);
                    }

                    if ($this->filterProgramId || $this->filterYearLevel) {
                        $q->join('students', 'students.user_id', '=', 'evaluation_responses.student_id');
                        if ($this->filterProgramId) {
                            $q->where('students.program_id', $this->filterProgramId);
                        }
                        if ($this->filterYearLevel) {
                            $q->where('students.current_year', $this->filterYearLevel);
                        }
                    }
                })
                ->with(['user', 'department']);
            if ($this->searchTeacher !== '') {
                $teachersQuery->where(function($q) {
                    $q->where('lastname', 'like', '%' . $this->searchTeacher . '%')
                      ->orWhere('othernames', 'like', '%' . $this->searchTeacher . '%')
                      ->orWhereHas('user', function($qu) {
                          $qu->where('name', 'like', '%' . $this->searchTeacher . '%');
                      });
                });
            }
            $teachers = $teachersQuery->get();

            // Calculate response count and average score for each teacher to display in sidebar
            $teachers = $teachers->map(function ($t) {
                $tResponseQuery = $this->form->responses()
                    ->where('status', 'submitted')
                    ->where('teacher_id', $t->user_id);

                if ($this->filterDepartmentId) {
                    $tResponseQuery->where('student_department_id', $this->filterDepartmentId);
                }
                if ($this->filterProgramId) {
                    $tResponseQuery->whereHas('studentUser.student', function ($q) {
                        $q->where('program_id', $this->filterProgramId);
                    });
                }
                if ($this->filterYearLevel) {
                    $tResponseQuery->whereHas('studentUser.student', function ($q) {
                        $q->where('current_year', $this->filterYearLevel);
                    });
                }
                
                $tResponseCount = $tResponseQuery->count();
                $tResponseIds = $tResponseQuery->pluck('id')->all();
                
                $tAvg = null;
                if (!empty($tResponseIds)) {
                     $tAvg = \App\Models\ResponseDetail::query()
                        ->whereIn('response_id', $tResponseIds)
                        ->whereHas('question', function ($q) {
                            $q->whereIn('rating_type', ['scale_5', 'scale_10']);
                        })
                        ->whereNotNull('answer_value')
                        ->avg('answer_value');
                    
                    if ($tAvg) {
                        $tAvg = round($tAvg, 1);
                    }
                }
                
                $t->response_count = $tResponseCount;
                $t->average_score = $tAvg;
                return $t;
            });

            if ($this->selectedTeacherId !== null && !$teachers->contains('user_id', $this->selectedTeacherId)) {
                $this->selectedTeacherId = null;
            }

            // Set up responses query based on selectedTeacherId and active filters
            $responsesQuery = $this->form->responses()->where('status', 'submitted');
            if ($this->selectedTeacherId !== null) {
                $responsesQuery->where('teacher_id', $this->selectedTeacherId);
            }
            if ($this->filterDepartmentId) {
                $responsesQuery->where('student_department_id', $this->filterDepartmentId);
            }
            if ($this->filterProgramId) {
                $responsesQuery->whereHas('studentUser.student', function ($q) {
                    $q->where('program_id', $this->filterProgramId);
                });
            }
            if ($this->filterYearLevel) {
                $responsesQuery->whereHas('studentUser.student', function ($q) {
                    $q->where('current_year', $this->filterYearLevel);
                });
            }
            $responseIds = $responsesQuery->pluck('id')->all();

            if (! empty($responseIds)) {
                $details = \App\Models\ResponseDetail::query()
                    ->whereIn('response_id', $responseIds)
                    ->get()
                    ->groupBy('question_id');

                foreach ($questions as $q) {
                    $qDetails = $details->get($q->id) ?? collect();
                    $total = $qDetails->count();

                    $stat = [
                        'total' => $total,
                        'avg' => null,
                        'distribution' => [],
                        'text_samples' => [],
                    ];

                    if ($total > 0) {
                        if (in_array($q->rating_type, ['scale_5', 'scale_10'], true)) {
                            $values = $qDetails->pluck('answer_value')->filter(fn ($v) => ! is_null($v));
                            if ($values->isNotEmpty()) {
                                $stat['avg'] = round($values->average(), 1);
                                $dist = array_fill(1, $q->rating_type === 'scale_5' ? 5 : 10, 0);
                                foreach ($values as $val) {
                                    $valInt = (int) $val;
                                    if (isset($dist[$valInt])) {
                                        $dist[$valInt]++;
                                    }
                                }
                                $stat['distribution'] = $dist;
                            }
                        } elseif ($q->rating_type === 'boolean') {
                            $values = $qDetails->pluck('answer_value')->filter(fn ($v) => ! is_null($v));
                            $yesCount = $values->filter(fn ($v) => (int) $v === 1)->count();
                            $noCount = $values->filter(fn ($v) => (int) $v === 0)->count();
                            $stat['distribution'] = [
                                'yes' => $yesCount,
                                'no' => $noCount,
                                'pct_yes' => $values->isNotEmpty() ? round(($yesCount / $values->count()) * 100, 1) : 0,
                            ];
                        } elseif ($q->rating_type === 'select_single') {
                            $texts = $qDetails->pluck('answer_text')->filter(fn ($t) => ! is_null($t) && $t !== '');
                            $freq = [];
                            foreach ($q->options_json ?? [] as $opt) {
                                $freq[$opt] = 0;
                            }
                            foreach ($texts as $txt) {
                                if (isset($freq[$txt])) {
                                    $freq[$txt]++;
                                } else {
                                    $freq[$txt] = ($freq[$txt] ?? 0) + 1;
                                }
                            }
                            $stat['distribution'] = $freq;
                        } elseif ($q->rating_type === 'select_multiple') {
                            $texts = $qDetails->pluck('answer_text')->filter(fn ($t) => ! is_null($t) && $t !== '');
                            $freq = [];
                            foreach ($q->options_json ?? [] as $opt) {
                                $freq[$opt] = 0;
                            }
                            foreach ($texts as $txt) {
                                $decoded = json_decode($txt, true);
                                if (is_array($decoded)) {
                                    foreach ($decoded as $opt) {
                                        if (isset($freq[$opt])) {
                                            $freq[$opt]++;
                                        } else {
                                            $freq[$opt] = ($freq[$opt] ?? 0) + 1;
                                        }
                                    }
                                }
                            }
                            $stat['distribution'] = $freq;
                        } else {
                            $stat['text_samples'] = $qDetails->pluck('answer_text')
                                ->filter(fn ($t) => ! is_null($t) && trim($t) !== '')
                                ->take(5)
                                ->all();
                        }
                    }
                    $questionStats[$q->id] = $stat;
                }
            }

            // Paginated Text Modal calculations
            if ($this->viewingTextQuestionId !== null) {
                $textQuestion = EvaluationQuestion::find($this->viewingTextQuestionId);
                if ($textQuestion) {
                    $detailsQuery = \App\Models\ResponseDetail::query()
                        ->whereIn('response_id', $responseIds)
                        ->where('question_id', $this->viewingTextQuestionId)
                        ->whereNotNull('answer_text')
                        ->where('answer_text', '!=', '');

                    $textTotal = $detailsQuery->count();
                    $textMaxPage = (int) ceil($textTotal / $this->textPerPage);
                    if ($textMaxPage < 1) {
                        $textMaxPage = 1;
                    }
                    
                    $paginatedTextResponses = $detailsQuery
                        ->orderByDesc('id')
                        ->skip(($this->textPage - 1) * $this->textPerPage)
                        ->take($this->textPerPage)
                        ->get();
                }
            }
        }

        $departments = collect();
        $programs = collect();
        $detailedResponses = collect();
        $detailedTotal = 0;
        $detailedMaxPage = 1;
        $viewingResponse = null;

        if ($this->form !== null) {
            $departments = \App\Models\Department::orderBy('name')->get();
            
            $programsQuery = \App\Models\Program::orderBy('name');
            if ($this->filterDepartmentId) {
                $programsQuery->where('department_id', $this->filterDepartmentId);
            }
            $programs = $programsQuery->get();

            if ($this->reportView === 'detailed') {
                $detailedQuery = $this->form->responses()
                    ->where('status', 'submitted')
                    ->with(['teacherUser.teacher.department', 'studentDepartment', 'studentUser.student.program']);

                if ($this->selectedTeacherId !== null) {
                    $detailedQuery->where('teacher_id', $this->selectedTeacherId);
                }

                if ($this->filterDepartmentId) {
                    $detailedQuery->where('student_department_id', $this->filterDepartmentId);
                }

                if ($this->filterProgramId) {
                    $detailedQuery->whereHas('studentUser.student', function ($q) {
                        $q->where('program_id', $this->filterProgramId);
                    });
                }

                if ($this->filterYearLevel) {
                    $detailedQuery->whereHas('studentUser.student', function ($q) {
                        $q->where('current_year', $this->filterYearLevel);
                    });
                }

                $detailedTotal = $detailedQuery->count();
                $detailedMaxPage = (int) ceil($detailedTotal / $this->detailedPerPage);
                if ($detailedMaxPage < 1) {
                    $detailedMaxPage = 1;
                }
                if ($this->detailedPage > $detailedMaxPage) {
                    $this->detailedPage = $detailedMaxPage;
                }

                $detailedResponses = $detailedQuery
                    ->orderByDesc('submitted_at')
                    ->skip(($this->detailedPage - 1) * $this->detailedPerPage)
                    ->take($this->detailedPerPage)
                    ->get();
            }

            if ($this->viewingResponseId !== null) {
                $viewingResponse = \App\Models\EvaluationResponse::with(['details.question', 'teacherUser'])
                    ->findOrFail($this->viewingResponseId);
            }
        }

        return view('livewire.admin.staff.evaluation-manage-page', [
            'questions' => $questions,
            'stats' => $stats,
            'questionStats' => $questionStats,
            'teachers' => $teachers,
            'selectedTeacherId' => $this->selectedTeacherId,
            'textQuestion' => $textQuestion,
            'paginatedTextResponses' => $paginatedTextResponses,
            'textTotal' => $textTotal,
            'textPage' => $this->textPage,
            'textMaxPage' => $textMaxPage,
            'reportView' => $this->reportView,
            'detailedResponses' => $detailedResponses,
            'detailedTotal' => $detailedTotal,
            'detailedPage' => $this->detailedPage,
            'detailedMaxPage' => $detailedMaxPage,
            'viewingResponse' => $viewingResponse,
            'departments' => $departments,
            'programs' => $programs,
        ])->layout('components.layouts.admin', [
            'title' => __('Manage Evaluation'),
            'headerTitle' => __('Manage Evaluation: :title', ['title' => $this->form->title]),
            'headerDescription' => __('Design questions, set availability windows, and track submission progress.'),
        ]);
    }
}
