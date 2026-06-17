<div class="mx-auto max-w-lg space-y-6">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <x-filepond
            field="spreadsheetPond"
            purpose="results_upload"
            :label="__('Results file (Excel/CSV)')"
            accept=".xlsx,.xls,.csv,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        />
        <x-college-submit-button action="analyze" class="mt-4 w-auto">{{ __('Analyze') }}</x-college-submit-button>
        @if ($detectedRows !== null)
            <p class="mt-4 text-sm text-gray-700 dark:text-gray-300">{{ __('Highest row: :n', ['n' => $detectedRows]) }}</p>
        @endif
    </div>
</div>
