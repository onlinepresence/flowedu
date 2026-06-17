<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\JobAlert;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentJobAlertsPage extends Component
{
    public string $typeFilter = ''; // empty means 'all'
    public string $search = '';

    protected $queryString = [
        'typeFilter' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function render(): View
    {
        $today = now()->toDateString();
        $oneMonthAgo = now()->subMonth()->toDateString();

        // Base query for active opportunities
        $activeQuery = JobAlert::query()
            ->where('expiry_date', '>=', $today)
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('company_or_organizer', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('expiry_date'); // Show closest to expiry first

        // Base query for expired opportunities (up to 1 month old)
        $expiredQuery = JobAlert::query()
            ->where('expiry_date', '<', $today)
            ->where('expiry_date', '>=', $oneMonthAgo)
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('company_or_organizer', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('expiry_date'); // Show most recently expired first

        return view('livewire.student.student-job-alerts-page', [
            'activeItems' => $activeQuery->get(),
            'expiredItems' => $expiredQuery->get(),
        ])->layout('components.layouts.student', [
            'title' => __('Job Alerts & Activities'),
            'hideHeader' => true,
        ]);
    }
}
