<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Finance;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\FeeBreakdownRequest;
use App\Models\FeeComponent;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Setting;
use App\Notifications\CollegeNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class FeesIndex extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $activeTab = 'payments';

    public string $receiptHeaderTitle = '';

    public string $receiptHeaderSubtitle = '';

    public string $receiptContactInfo = '';

    public string $receiptFooterNote = '';

    public bool $receiptShowSignature = true;

    public bool $receiptShowStamp = true;

    public string $filterSessionId = '';

    public string $search = '';

    public string $filterLevel = '';

    public string $program_id = '';

    public string $level = '';

    public string $session_id = '';

    public string $tuition_fee = '';

    /** @var array<int, array{id: int, name: string, field: string|null, amount: string, is_system: bool}> */
    public array $selectedComponents = [];

    /** @var array<int, bool> */
    public array $componentActive = [];

    public string $newComponentName = '';

    public string $newComponentPercentage = '';

    public bool $showStructureModal = false;

    public bool $showStructuresTableSection = true;

    public ?int $editingStructureId = null;

    protected $queryString = [
        'activeTab' => ['except' => 'payments'],
        'filterSessionId' => ['except' => ''],
        'search' => ['except' => ''],
        'filterLevel' => ['except' => ''],
    ];

    public function mount(): void
    {
        $latestSession = AcademicSession::query()->orderByDesc('start_date')->value('id');
        if ($latestSession !== null) {
            $this->filterSessionId = (string) $latestSession;
            $this->session_id = (string) $latestSession;
        }

        $this->loadComponentConfiguration();
        $this->loadReceiptSettings();
    }

    public function updatedFilterSessionId(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterLevel(): void
    {
        $this->resetPage();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function toggleSection(string $section): void
    {
        if ($section === 'structure-form') {
            $this->showStructureModal = ! $this->showStructureModal;
            if (! $this->showStructureModal) {
                $this->cancelEdit();
            }

            return;
        }

        if ($section === 'structures-table') {
            $this->showStructuresTableSection = ! $this->showStructuresTableSection;
        }
    }

    public function openStructureModal(): void
    {
        $this->cancelEdit();
        $currentSessionId = AcademicSession::query()->where('is_current', true)->value('id');
        if ($currentSessionId !== null) {
            $this->session_id = (string) $currentSessionId;
        }

        $this->selectedComponents = FeeComponent::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('field')->orWhere('field', '!=', 'tuition_fee');
            })
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn (FeeComponent $component): array => $this->componentEntry($component))
            ->all();

        $this->showStructureModal = true;
        $this->dispatch('open-modal', 'fee-structure-modal');
    }

    public function cancelEdit(): void
    {
        $this->editingStructureId = null;
        $this->program_id = '';
        $this->level = '';
        $this->session_id = '';
        $this->tuition_fee = '';
        $this->selectedComponents = [];
        $this->resetValidation();
    }

    public function editStructure(int $id): void
    {
        $structure = FeeStructure::query()->with('items.feeComponent')->findOrFail($id);
        $this->editingStructureId = $structure->id;
        $this->program_id = (string) $structure->program_id;
        $this->level = (string) $structure->level;
        $this->session_id = (string) $structure->session_id;
        $this->tuition_fee = (string) $structure->tuition_fee;
        $this->selectedComponents = [];

        $items = $structure->items->filter(fn ($item): bool => $item->feeComponent !== null);

        if ($items->isNotEmpty()) {
            foreach ($items as $item) {
                $component = $item->feeComponent;
                $amount = (float) $item->amount;

                if ($component->field === 'tuition_fee') {
                    $this->tuition_fee = number_format($amount, 2, '.', '');

                    continue;
                }

                if ($amount <= 0) {
                    continue;
                }

                $this->selectedComponents[] = $this->componentEntry(
                    $component,
                    number_format($amount, 2, '.', '')
                );
            }
        } else {
            foreach (FeeComponent::query()->whereNotNull('field')->where('field', '!=', 'tuition_fee')->get() as $component) {
                $amount = (float) ($structure->{$component->field} ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                $this->selectedComponents[] = $this->componentEntry(
                    $component,
                    number_format($amount, 2, '.', '')
                );
            }
        }

        $this->showStructureModal = true;
        $this->dispatch('open-modal', 'fee-structure-modal');
    }

    public function deleteStructure(int $id): void
    {
        $hasPayments = Payment::query()->where('fee_structure_id', $id)->exists();
        if ($hasPayments) {
            $this->collegeToast(__('Cannot delete fee structure because payments have already been recorded against it.'), 'error');

            return;
        }

        FeeStructure::query()->whereKey($id)->delete();
        $this->collegeToast(__('Fee structure deleted successfully.'));
    }

    public function createStructureFromProgram(): void
    {
        $this->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'level' => ['required', 'integer', 'in:100,200,300,400'],
            'session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'tuition_fee' => ['required', 'numeric', 'min:0'],
            'selectedComponents' => ['array'],
            'selectedComponents.*.id' => ['required', 'integer', 'distinct', 'exists:fee_components,id'],
            'selectedComponents.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $totals = array_fill_keys(FeeComponent::SYSTEM_FIELDS, 0.0);
        $totals['tuition_fee'] = $this->normalizeAmount($this->tuition_fee);
        $selectedItems = [];
        $customTotal = 0.0;

        foreach ($this->selectedComponents as $component) {
            $amount = $this->normalizeAmount($component['amount'] ?? '');
            $field = $component['field'] ?? null;
            $isMappedField = is_string($field)
                && $field !== 'tuition_fee'
                && array_key_exists($field, $totals);

            if ($isMappedField) {
                $totals[$field] = $amount;
            } else {
                $customTotal += $amount;
            }

            $selectedItems[] = [
                'id' => (int) $component['id'],
                'amount' => $amount,
            ];
        }

        $payload = [
            'program_id' => (int) $this->program_id,
            'level' => (int) $this->level,
            'session_id' => (int) $this->session_id,
            ...$totals,
            'total_amount' => array_sum($totals) + $customTotal,
        ];

        $editingId = $this->editingStructureId;

        DB::transaction(function () use ($editingId, $payload, $selectedItems, $totals): void {
            if ($editingId !== null) {
                $structure = FeeStructure::query()->findOrFail($editingId);
                $structure->update($payload);
            } else {
                $structure = FeeStructure::query()->updateOrCreate(
                    [
                        'program_id' => $payload['program_id'],
                        'level' => $payload['level'],
                        'session_id' => $payload['session_id'],
                    ],
                    [
                        ...$payload,
                        'created_by' => auth()->id(),
                    ]
                );
            }

            $components = FeeComponent::query()
                ->whereIn('id', array_column($selectedItems, 'id'))
                ->get()
                ->keyBy('id');

            $tuitionComponent = FeeComponent::query()->where('field', 'tuition_fee')->first();
            $itemRows = [];

            if ($tuitionComponent !== null) {
                $itemRows[] = [
                    'fee_component_id' => $tuitionComponent->id,
                    'amount' => $totals['tuition_fee'],
                ];
            }

            foreach ($selectedItems as $selectedItem) {
                if ($tuitionComponent !== null && $selectedItem['id'] === $tuitionComponent->id) {
                    continue;
                }

                if (! $components->has($selectedItem['id'])) {
                    continue;
                }

                $itemRows[] = [
                    'fee_component_id' => $selectedItem['id'],
                    'amount' => $selectedItem['amount'],
                ];
            }

            $structure->items()->delete();

            if ($itemRows !== []) {
                $structure->items()->createMany($itemRows);
            }
        });

        $this->filterSessionId = $this->session_id;
        $this->showStructureModal = false;
        $this->dispatch('close-modal', 'fee-structure-modal');
        $this->cancelEdit();
        $this->resetPage();

        $this->collegeToast($editingId !== null
            ? __('Fee structure updated successfully.')
            : __('Fee structure created successfully.'));
    }

    public function fillFromProgramCost(): void
    {
        $this->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
        ]);

        $program = Program::query()->findOrFail((int) $this->program_id);
        $base = (float) ($program->cost ?? 0);
        if ($base <= 0) {
            $this->addError('program_id', __('Selected program has no base cost set.'));

            return;
        }

        $tuitionComponent = FeeComponent::query()->where('field', 'tuition_fee')->first();
        $tuitionPercentage = $tuitionComponent !== null
            ? (float) $tuitionComponent->default_percentage
            : 70.0;

        $this->tuition_fee = number_format($base * ($tuitionPercentage / 100), 2, '.', '');

        $components = FeeComponent::query()
            ->whereIn('id', array_column($this->selectedComponents, 'id'))
            ->get()
            ->keyBy('id');

        foreach ($this->selectedComponents as $index => $selectedComponent) {
            $component = $components->get($selectedComponent['id']);
            $percentage = $component !== null ? (float) $component->default_percentage : 0.0;

            $this->selectedComponents[$index]['amount'] = number_format(
                $base * ($percentage / 100),
                2,
                '.',
                ''
            );
        }
    }

    public function addComponent(int $componentId): void
    {
        $component = FeeComponent::query()
            ->whereKey($componentId)
            ->where('is_active', true)
            ->firstOrFail();

        foreach ($this->selectedComponents as $selectedComponent) {
            if ((int) $selectedComponent['id'] === $component->id) {
                return;
            }
        }

        $this->selectedComponents[] = $this->componentEntry($component);
    }

    public function removeComponent(int $index): void
    {
        if (! isset($this->selectedComponents[$index])) {
            return;
        }

        $amount = $this->normalizeAmount($this->selectedComponents[$index]['amount'] ?? '');
        $this->tuition_fee = number_format(
            $this->normalizeAmount($this->tuition_fee) + $amount,
            2,
            '.',
            ''
        );

        unset($this->selectedComponents[$index]);
        $this->selectedComponents = array_values($this->selectedComponents);
    }

    public function addFeeComponent(): void
    {
        $this->newComponentName = trim($this->newComponentName);
        $this->newComponentPercentage = trim($this->newComponentPercentage);

        $this->validate([
            'newComponentName' => ['required', 'string', 'max:100'],
            'newComponentPercentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $alreadyExists = FeeComponent::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($this->newComponentName)])
            ->exists();

        if ($alreadyExists) {
            $this->addError('newComponentName', __('A fee component with that name already exists.'));

            return;
        }

        $component = FeeComponent::query()->create([
            'name' => $this->newComponentName,
            'field' => null,
            'default_percentage' => $this->normalizeAmount($this->newComponentPercentage),
            'is_active' => true,
            'is_system' => false,
        ]);

        $this->componentActive[$component->id] = true;
        $this->newComponentName = '';
        $this->newComponentPercentage = '';
        $this->resetValidation();

        $this->collegeToast(__('Fee component added successfully.'));
    }

    public function saveComponentConfiguration(): void
    {
        foreach ($this->componentActive as $componentId => $isActive) {
            FeeComponent::query()
                ->whereKey((int) $componentId)
                ->update(['is_active' => (bool) $isActive]);
        }

        FeeComponent::query()
            ->where('field', 'tuition_fee')
            ->update(['is_active' => true]);

        $this->syncExtraFeeKeysSetting();
        $this->collegeToast(__('Fee component configuration saved.'));
    }

    public function approveRequest(int $requestId): void
    {
        $request = FeeBreakdownRequest::findOrFail($requestId);
        $request->update([
            'status' => 'approved',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        $request->student->user->notify(new CollegeNotification(
            __('Fee Breakdown Approved'),
            __('Your request for a detailed breakdown of Level :level fees for the :session session has been approved.', [
                'level' => $request->feeStructure->level,
                'session' => $request->feeStructure->session->name,
            ]),
            route('student.fees.index')
        ));

        $this->collegeToast(__('Request approved successfully.'));
    }

    public function rejectRequest(int $requestId): void
    {
        $request = FeeBreakdownRequest::findOrFail($requestId);
        $request->update([
            'status' => 'rejected',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        $request->student->user->notify(new CollegeNotification(
            __('Fee Breakdown Request Declined'),
            __('Your request for a detailed breakdown of Level :level fees for the :session session was declined.', [
                'level' => $request->feeStructure->level,
                'session' => $request->feeStructure->session->name,
            ]),
            route('student.fees.index')
        ));

        $this->collegeToast(__('Request declined successfully.'));
    }

    public function render(): View
    {
        $structures = FeeStructure::query()
            ->with(['program', 'session'])
            ->when($this->filterSessionId !== '', fn ($q) => $q->where('session_id', (int) $this->filterSessionId))
            ->orderByDesc('session_id')
            ->orderBy('program_id')
            ->orderBy('level')
            ->paginate(20, ['*'], 'structuresPage');

        // Query actual Payment transactions instead of FeePayment ledger
        $payments = Payment::query()
            ->with(['student.user', 'feeStructure.program', 'feeStructure.session'])
            ->when($this->search !== '', function ($q): void {
                $q->whereHas('student', function ($s): void {
                    $s->where('index_number', 'like', '%'.$this->search.'%')
                        ->orWhere('lastname', 'like', '%'.$this->search.'%')
                        ->orWhere('firstname', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(20, ['*'], 'paymentsPage');

        // Stats calculations
        $totalPaid = (float) Payment::query()
            ->when($this->search !== '', function ($q): void {
                $q->whereHas('student', function ($s): void {
                    $s->where('index_number', 'like', '%'.$this->search.'%')
                        ->orWhere('lastname', 'like', '%'.$this->search.'%')
                        ->orWhere('firstname', 'like', '%'.$this->search.'%');
                });
            })
            ->sum('amount_paid');

        $totalBalance = (float) FeePayment::query()
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($inner): void {
                    $inner->where('lastname', 'like', '%'.$this->search.'%')
                        ->orWhere('othernames', 'like', '%'.$this->search.'%')
                        ->orWhereHas('student', function ($s): void {
                            $s->where('index_number', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->sum('balance');

        $requests = FeeBreakdownRequest::query()
            ->with(['student.user', 'feeStructure.program', 'feeStructure.session'])
            ->latest()
            ->paginate(20, ['*'], 'requestsPage');

        $pendingRequestsCount = FeeBreakdownRequest::where('status', 'pending')->count();

        $feeComponents = FeeComponent::query()
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        $selectedIds = array_map('intval', array_column($this->selectedComponents, 'id'));

        $availableComponents = $feeComponents
            ->filter(fn (FeeComponent $component): bool => $component->is_active
                && $component->field !== 'tuition_fee'
                && ! in_array($component->id, $selectedIds, true))
            ->values();

        return view('livewire.admin.finance.fees-index', [
            'programs' => Program::query()->orderBy('name')->get(['id', 'name', 'cost']),
            'sessions' => AcademicSession::query()->orderByDesc('start_date')->get(['id', 'name', 'start_date', 'is_current']),
            'structures' => $structures,
            'payments' => $payments,
            'totalPaid' => $totalPaid,
            'totalBalance' => $totalBalance,
            'feeComponents' => $feeComponents,
            'availableComponents' => $availableComponents,
            'requests' => $requests,
            'pendingRequestsCount' => $pendingRequestsCount,
        ])->layout('components.layouts.admin', [
            'title' => __('Fees Management'),
            'headerDescription' => __('Configure program fee structures, fee components, and view recent transactions.'),
        ]);
    }

    private function loadComponentConfiguration(): void
    {
        $this->componentActive = FeeComponent::query()
            ->pluck('is_active', 'id')
            ->map(fn ($isActive): bool => (bool) $isActive)
            ->all();
    }

    /**
     * @return array{id: int, name: string, field: string|null, amount: string, is_system: bool}
     */
    private function componentEntry(FeeComponent $component, string $amount = ''): array
    {
        return [
            'id' => $component->id,
            'name' => $component->name,
            'field' => $component->field,
            'amount' => $amount,
            'is_system' => (bool) $component->is_system,
        ];
    }

    private function syncExtraFeeKeysSetting(): void
    {
        $selected = FeeComponent::query()
            ->where('is_active', true)
            ->whereNotNull('field')
            ->where('field', '!=', 'tuition_fee')
            ->orderBy('id')
            ->pluck('field')
            ->all();

        Setting::query()->updateOrCreate(
            ['setting_key' => 'finance.extra_fee_keys'],
            [
                'category' => 'finance',
                'setting_value' => (string) json_encode($selected),
                'data_type' => 'json',
                'description' => 'Enabled extra fee components',
                'updated_by' => auth()->id(),
            ]
        );
    }

    private function loadReceiptSettings(): void
    {
        $raw = Setting::query()
            ->where('setting_key', 'finance.receipt_settings')
            ->value('setting_value');

        $settings = is_string($raw) ? json_decode($raw, true) : null;

        $this->receiptHeaderTitle = (string) ($settings['header_title'] ?? __('College of Education'));
        $this->receiptHeaderSubtitle = (string) ($settings['header_subtitle'] ?? __('Official Tuition & Fees Payment Receipt'));
        $this->receiptContactInfo = (string) ($settings['contact_info'] ?? __('+233 24 000 0000 | finance@college.edu.gh'));
        $this->receiptFooterNote = (string) ($settings['footer_note'] ?? __('Thank you for your payment. This is an official computer-generated receipt.'));
        $this->receiptShowSignature = (bool) ($settings['show_signature'] ?? true);
        $this->receiptShowStamp = (bool) ($settings['show_stamp'] ?? true);
    }

    public function saveReceiptSettings(): void
    {
        $this->validate([
            'receiptHeaderTitle' => ['required', 'string', 'max:100'],
            'receiptHeaderSubtitle' => ['required', 'string', 'max:150'],
            'receiptContactInfo' => ['nullable', 'string', 'max:255'],
            'receiptFooterNote' => ['nullable', 'string', 'max:500'],
            'receiptShowSignature' => ['boolean'],
            'receiptShowStamp' => ['boolean'],
        ]);

        $payload = [
            'header_title' => trim($this->receiptHeaderTitle),
            'header_subtitle' => trim($this->receiptHeaderSubtitle),
            'contact_info' => trim($this->receiptContactInfo),
            'footer_note' => trim($this->receiptFooterNote),
            'show_signature' => $this->receiptShowSignature,
            'show_stamp' => $this->receiptShowStamp,
        ];

        Setting::query()->updateOrCreate(
            ['setting_key' => 'finance.receipt_settings'],
            [
                'category' => 'finance',
                'setting_value' => json_encode($payload),
                'data_type' => 'json',
                'description' => 'Custom receipt layout settings',
                'updated_by' => auth()->id(),
            ]
        );

        $this->collegeToast(__('Receipt settings saved successfully.'));
    }

    private function normalizeAmount(string|float|int|null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }
}
