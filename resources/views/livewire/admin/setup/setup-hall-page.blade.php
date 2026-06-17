<div class="mx-auto max-w-5xl space-y-8">
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ $editingHallId ? __('Edit hall') : __('Add hall') }}
            </h2>
        </div>
        <form wire:submit="{{ $editingHallId ? 'updateHall' : 'saveHall' }}" class="space-y-4 p-6">
            <div>
                <label for="hall-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Hall name') }}</label>
                <input wire:model="name" id="hall-name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required />
                @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="hall-master" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Hall master') }}</label>
                <input wire:model="master" id="hall-master" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                @error('master') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="hall-cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Cost per head (GHC)') }}</label>
                <input wire:model="cost" id="hall-cost" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required />
                @error('cost') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="hall-period" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Duration of cost') }}</label>
                <select wire:model="period" id="hall-period" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" required>
                    <option value="per_semester">{{ __('Per semester') }}</option>
                    <option value="per_year">{{ __('Per year') }}</option>
                </select>
                @error('period') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <x-college-form-submit target="{{ $editingHallId ? 'updateHall' : 'saveHall' }}" class="inline-flex justify-center">
                {{ $editingHallId ? __('Update hall') : __('Add hall') }}
            </x-college-form-submit>
            @if ($editingHallId)
                <button
                    type="button"
                    wire:click="cancelEditHall"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    {{ __('Cancel') }}
                </button>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Halls') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Master') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Cost') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Period') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($halls as $hall)
                        <tr wire:key="hall-{{ $hall->id }}">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $hall->name }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $hall->master ?: __('Not set') }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">GHC {{ number_format($hall->cost, 2) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $hall->period === 'per_semester' ? __('Per semester') : __('Per year') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <button type="button" wire:click="editHall({{ $hall->id }})" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Edit') }}</button>
                                <button type="button" wire:click="deleteHall({{ $hall->id }})" wire:confirm="{{ __('Delete this hall?') }}" class="ml-4 text-red-600 hover:text-red-500 dark:text-red-400">{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No halls yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $halls->links() }}
        </div>
    </div>
</div>
