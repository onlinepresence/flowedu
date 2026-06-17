<div
    class="mx-auto max-w-7xl space-y-6 print-container"
    x-data
    x-on:export-welfare-disciplinary-csv.window="$wire.exportDisciplinaryCSV()"
    x-on:export-welfare-disciplinary-excel.window="$wire.exportDisciplinaryExcel()"
    x-on:export-welfare-medical-csv.window="$wire.exportMedicalCSV()"
    x-on:export-welfare-medical-excel.window="$wire.exportMedicalExcel()"
>
    {{-- Header Actions --}}
    <x-slot name="headerActions">
        <button type="button" onclick="window.print()" class="no-print inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            <i class="fa-solid fa-print"></i> {{ __('Print Report') }}
        </button>
        
        <div x-data="{ open: false }" class="relative inline-block text-left no-print">
            <button @click="open = !open" type="button" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                <i class="fa-solid fa-download"></i> {{ __('Export') }} <i class="fa-solid fa-chevron-down text-xs"></i>
            </button>
            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-10 mt-2 w-64 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800 dark:ring-gray-700">
                <div class="py-1">
                    <div class="px-4 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 uppercase tracking-wider">{{ __('Disciplinary Cases') }}</div>
                    <button type="button" @click="$dispatch('export-welfare-disciplinary-csv'); open = false" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-file-csv text-green-500"></i> {{ __('Export CSV') }}
                    </button>
                    <button type="button" @click="$dispatch('export-welfare-disciplinary-excel'); open = false" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-file-excel text-blue-500"></i> {{ __('Export Excel') }}
                    </button>
                    
                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                    
                    <div class="px-4 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 uppercase tracking-wider">{{ __('Medical Registry') }}</div>
                    <button type="button" @click="$dispatch('export-welfare-medical-csv'); open = false" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-file-csv text-green-500"></i> {{ __('Export CSV') }}
                    </button>
                    <button type="button" @click="$dispatch('export-welfare-medical-excel'); open = false" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-file-excel text-blue-500"></i> {{ __('Export Excel') }}
                    </button>
                </div>
            </div>
        </div>
    </x-slot>

    {{-- Printable Header (Hidden on Screen) --}}
    <div class="hidden print:block mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Apex Polytechnic</h1>
        <p class="text-gray-600 text-sm">Official Welfare & Disciplinary Summary Report</p>
        <div class="mt-4 border-t border-b border-gray-200 py-2 text-xs text-gray-500 flex justify-between">
            <span>{{ __('Generated on:') }} {{ now()->toDayDateTimeString() }}</span>
            <span>{{ __('Active Cases:') }} {{ $activeCases }}</span>
        </div>
    </div>

    {{-- Stats Cards Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-college.stats-card 
            :title="__('Active Sanctions')" 
            :value="number_format($activeCases)" 
            icon="fa-solid fa-gavel" 
            color="red" 
        />
        <x-college.stats-card 
            :title="__('Resolved Cases')" 
            :value="number_format($resolvedCases)" 
            icon="fa-solid fa-square-check" 
            color="green" 
        />
        <x-college.stats-card 
            :title="__('Total Infractions')" 
            :value="number_format($totalInfractions)" 
            icon="fa-solid fa-clock-rotate-left" 
            color="amber" 
        />
        <x-college.stats-card 
            :title="__('Medical Alerts')" 
            :value="number_format($medicalAlerts)" 
            icon="fa-solid fa-kit-medical" 
            color="purple" 
        />
    </div>

    <div class="no-print">
        <x-college.filter-card cols="5">
            <div>
                <label for="academicSessionId" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Academic Session') }}</label>
                <select id="academicSessionId" wire:model.live="academicSessionId" class="w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">{{ __('All Sessions') }}</option>
                    @foreach ($sessions as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="programId" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Program') }}</label>
                <select id="programId" wire:model.live="programId" class="w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">{{ __('All Programs') }}</option>
                    @foreach ($programs as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="level" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Class Level') }}</label>
                <select id="level" wire:model.live="level" class="w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">{{ __('All Levels') }}</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="300">300</option>
                    <option value="400">400</option>
                </select>
            </div>
            <div>
                <label for="returnStatus" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Sanction Status') }}</label>
                <select id="returnStatus" wire:model.live="returnStatus" class="w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">{{ __('All Statuses') }}</option>
                    <option value="active">{{ __('Active Sanctions') }}</option>
                    <option value="resolved">{{ __('Resolved/Reinstated') }}</option>
                </select>
            </div>
            <div>
                <label for="hasMedicalCondition" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Medical History') }}</label>
                <select id="hasMedicalCondition" wire:model.live="hasMedicalCondition" class="w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">{{ __('All Students') }}</option>
                    <option value="yes">{{ __('With Medical Conditions') }}</option>
                    <option value="no">{{ __('No Known Medical Conditions') }}</option>
                </select>
            </div>
        </x-college.filter-card>
    </div>

    {{-- Main Visual Layout - Disciplinary Cases --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-scale-unbalanced-flip text-red-500"></i>
                {{ __('Disciplinary Cases & Sanctions Registry') }}
            </h2>
        </div>
        
        <div class="relative">
            {{-- Targeted Loading Overlay --}}
            <div wire:loading.delay wire:target="previousPage, nextPage, gotoPage, programId, returnStatus" class="absolute inset-0 bg-white/40 dark:bg-gray-900/40 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity duration-200">
                <div class="flex items-center gap-2 rounded-lg bg-white/80 px-4 py-2 shadow-lg dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700">
                    <i class="fa-solid fa-circle-notch fa-spin text-indigo-600 dark:text-indigo-400"></i>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Loading data...') }}</span>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50/30 dark:bg-gray-900/10">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Student') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Offense Description') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Action Taken') }}</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Action Date') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($disciplinaryCases as $row)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20">
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $row->fullname }}</div>
                                    <div class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $row->index_number }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate">{{ $row->offense }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate">{{ $row->action_taken }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-center text-sm font-mono text-gray-600 dark:text-gray-300">{{ $row->date_of_action?->format('Y-m-d') }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold">
                                    @if ($row->return_status)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                            <i class="fa-solid fa-circle-check"></i> {{ __('Resolved') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                            <i class="fa-solid fa-triangle-exclamation"></i> {{ __('Active') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10">
                                    <x-college.empty-state :title="__('No Infractions Registered')" :description="__('No student disciplinary actions matching filter criteria.')" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700 no-print">
                {{ $disciplinaryCases->links(data: ['pageName' => 'disciplinaryPage']) }}
            </div>
        </div>
    </div>

    {{-- Main Visual Layout - Medical & Allergies --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-briefcase-medical text-purple-500"></i>
                {{ __('Student Health & Medical Alert Registry') }}
            </h2>
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-750 dark:bg-indigo-950/30 dark:text-indigo-300 self-start sm:self-auto">
                {{ __('Current Session: :session', ['session' => $currentSessionName]) }}
            </span>
        </div>
        
        <div class="relative">
            {{-- Targeted Loading Overlay --}}
            <div wire:loading.delay wire:target="previousPage, nextPage, gotoPage, programId, level, hasMedicalCondition, academicSessionId" class="absolute inset-0 bg-white/40 dark:bg-gray-900/40 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity duration-200">
                <div class="flex items-center gap-2 rounded-lg bg-white/80 px-4 py-2 shadow-lg dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700">
                    <i class="fa-solid fa-circle-notch fa-spin text-indigo-600 dark:text-indigo-400"></i>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Loading data...') }}</span>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50/30 dark:bg-gray-900/10">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Index Number') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Student') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Program') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Academic Session') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Medical Conditions') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Allergies') }}</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Emergency Contact') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($medicalRegistry as $row)
                            @php
                                $hasCondition = ($row->medical_conditions !== 'None' || $row->allergies !== 'None');
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 {{ $hasCondition ? 'bg-purple-50/10 dark:bg-purple-900/5' : '' }}">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-mono text-gray-900 dark:text-white">{{ $row->student?->index_number }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $row->student?->lastname }}, {{ $row->student?->firstname }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row->student?->program?->name }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300 font-mono">{{ $row->academicSession?->name ?? __('N/A') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    @if ($row->medical_conditions !== 'None')
                                        <span class="inline-flex rounded bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                            {{ $row->medical_conditions }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ __('None') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    @if ($row->allergies !== 'None')
                                        <span class="inline-flex rounded bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">
                                            {{ $row->allergies }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ __('None') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-300 font-mono">{{ $row->emergency_contacts }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10">
                                    <x-college.empty-state :title="__('No Medical History Registered')" :description="__('No student medical profiles matched filter criteria.')" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700 no-print">
                {{ $medicalRegistry->links(data: ['pageName' => 'medicalPage']) }}
            </div>
        </div>
    </div>

    {{-- Styling for Print Layout --}}
    <style>
        @media print {
            html, body, main, .flex-1, .overflow-y-auto, .min-h-0 {
                overflow: visible !important;
                height: auto !important;
                max-height: none !important;
                min-height: 0 !important;
            }
            body {
                background-color: white !important;
                color: black !important;
            }
            .no-print, aside, nav, header, [role="navigation"] {
                display: none !important;
            }
            .print-container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</div>
