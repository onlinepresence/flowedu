<div class="mx-auto max-w-4xl space-y-6">
    <x-card :title="__('Backup')">
        <div class="mb-6 flex flex-wrap items-center gap-3">
            <button
                type="button"
                wire:click="createBackup"
                wire:loading.attr="disabled"
                wire:target="createBackup"
                class="inline-flex items-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 disabled:opacity-50 dark:bg-purple-500 dark:hover:bg-purple-600"
            >
                <span wire:loading.remove wire:target="createBackup">{{ __('Create backup') }}</span>
                <span wire:loading.delay.200ms wire:target="createBackup" wire:loading.class.remove="hidden" class="hidden inline-flex items-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                    {{ __('Please wait…') }}
                </span>
            </button>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Dumps are stored privately (not under /public). Download requires system admin access.') }}
            </p>
        </div>

        @if ($canRestore)
            <div class="mb-8 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/40">
                <h3 class="mb-2 text-sm font-semibold text-amber-900 dark:text-amber-100">{{ __('Restore database') }}</h3>
                <p class="mb-3 text-xs text-amber-800 dark:text-amber-200">
                    {{ __('Owner only. A pre-restore dump is attempted first. Requires MySQL (not sqlite).') }}
                </p>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300" for="restore-sql">{{ __('SQL file') }}</label>
                        <x-filepond
                            field="restoreFilePond"
                            purpose="backup_upload"
                            :label="__('SQL dump file')"
                            accept=".sql,text/plain,application/sql"
                        />
                        @error('restoreFilePond')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <button
                        type="button"
                        wire:click="restoreDatabase"
                        wire:loading.attr="disabled"
                        wire:target="restoreDatabase"
                        class="inline-flex items-center rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50 dark:border-red-800 dark:bg-gray-900 dark:text-red-300 dark:hover:bg-red-950/50"
                    >
                        <span wire:loading.remove wire:target="restoreDatabase">{{ __('Restore') }}</span>
                        <span wire:loading.delay.200ms wire:target="restoreDatabase" wire:loading.class.remove="hidden" class="hidden inline-flex items-center gap-2">
                            <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                            {{ __('Please wait…') }}
                        </span>
                    </button>
                </div>
            </div>
        @endif
    </x-card>

    <x-card :title="__('Backup history')">
        @if ($backups->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No backups recorded yet.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="py-2 pe-4 text-start font-medium text-gray-700 dark:text-gray-300">{{ __('File') }}</th>
                            <th class="py-2 pe-4 text-start font-medium text-gray-700 dark:text-gray-300">{{ __('Size') }}</th>
                            <th class="py-2 pe-4 text-start font-medium text-gray-700 dark:text-gray-300">{{ __('Created') }}</th>
                            <th class="py-2 pe-4 text-start font-medium text-gray-700 dark:text-gray-300">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($backups as $backup)
                            <tr wire:key="backup-{{ $backup->id }}">
                                <td class="py-2 pe-4 font-mono text-gray-900 dark:text-gray-100">{{ $backup->filename }}</td>
                                <td class="py-2 pe-4 text-gray-600 dark:text-gray-400">
                                    @if ($backup->file_size !== null)
                                        {{ number_format((int) $backup->file_size / 1024, 1) }} KB
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-2 pe-4 text-gray-600 dark:text-gray-400">
                                    {{ $backup->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="py-2 pe-4">
                                    <a
                                        href="{{ route('admin.settings.backup.download', $backup) }}"
                                        class="font-medium text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300"
                                    >
                                        {{ __('Download') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</div>
