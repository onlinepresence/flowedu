<div class="mx-auto max-w-5xl space-y-8">
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ $editingDepartmentId ? __('Edit department') : __('Add department') }}
            </h2>
        </div>
        <form wire:submit="{{ $editingDepartmentId ? 'updateDepartment' : 'saveDepartment' }}" class="space-y-4 p-6">
            <div>
                <label for="dept-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Department name') }}</label>
                <input wire:model="name" id="dept-name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required />
                @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="dept-faculty" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Faculty') }}</label>
                <select wire:model="faculty_id" id="dept-faculty" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($faculties as $faculty)
                        <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
                    @endforeach
                </select>
                @error('faculty_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            @if ($hodUsers->isNotEmpty())
                <div>
                    <label for="dept-hod" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Head of department') }}</label>
                    <select wire:model="hod" id="dept-hod" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">{{ __('Not set') }}</option>
                        @foreach ($hodUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->username ?? $u->email }}</option>
                        @endforeach
                    </select>
                    @error('hod') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            @endif
            <x-college-form-submit target="{{ $editingDepartmentId ? 'updateDepartment' : 'saveDepartment' }}" class="inline-flex justify-center">
                {{ $editingDepartmentId ? __('Update department') : __('Add department') }}
            </x-college-form-submit>
            @if ($editingDepartmentId)
                <button
                    type="button"
                    wire:click="cancelEditDepartment"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    {{ __('Cancel') }}
                </button>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Departments') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Faculty') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Head') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($departments as $dept)
                        <tr wire:key="dept-{{ $dept->id }}">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $dept->name }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $dept->faculty?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $dept->headOfDepartment?->username ?? $dept->headOfDepartment?->email ?? '—' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <button type="button" wire:click="editDepartment({{ $dept->id }})" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Edit') }}</button>
                                <button type="button" wire:click="deleteDepartment({{ $dept->id }})" wire:confirm="{{ __('Delete this department?') }}" class="ml-4 text-red-600 hover:text-red-500 dark:text-red-400">{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No departments yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $departments->links() }}
        </div>
    </div>
</div>
