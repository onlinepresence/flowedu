<div class="mx-auto max-w-4xl space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                {{ __('My Allowances') }}
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ __('Track all your awarded monthly allowances, stipends, and bursaries. Filter by academic session to view historical disbursements.') }}
            </p>
        </div>
    </div>

    {{-- Filters Card --}}
    <x-card class="p-4 no-print">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="sessionFilter" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">{{ __('Filter by Academic Year') }}</label>
                <select id="sessionFilter" wire:model.live="sessionFilter" class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-650 dark:bg-gray-900 dark:text-white">
                    <option value="">{{ __('All Academic Years') }}</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-card>

    {{-- Tabular Allowances List --}}
    <x-card>
        <div class="overflow-x-auto -mx-6 -my-5">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Allowance / Scheme Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Academic Year') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Award Date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $row)
                        <tr wire:key="allowance-row-{{ $row->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/10">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $row->scholarship?->name ?? __('Allowance Award') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $row->academicSession?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ $row->award_date?->format('F d, Y') ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                @if ($row->status === 'approved' || $row->status === 'active')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                                        {{ __('Approved') }}
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                        {{ __('Pending') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-right text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                GHS {{ number_format((float) $row->amount_awarded, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No allowance records found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
