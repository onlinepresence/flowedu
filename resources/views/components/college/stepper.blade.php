@props(['current' => 1])

@php
    $steps = [
        1 => [
            'title' => __('Personal Details'),
            'desc' => __('Basic profile information'),
            'route' => 'student.setup.personal'
        ],
        2 => [
            'title' => __('Parent / Guardian'),
            'desc' => __('Emergency & guardian contacts'),
            'route' => 'student.setup.guardian'
        ],
        3 => [
            'title' => __('Activation Status'),
            'desc' => __('Review & activate dashboard'),
            'route' => 'student.setup.status'
        ]
    ];
@endphp

<div class="w-full bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm mb-6">
    <!-- Horizontal Stepper for screens >= md -->
    <div class="hidden md:flex items-center justify-between">
        @foreach($steps as $num => $step)
            <div class="flex items-center flex-1 last:flex-none">
                <!-- Step Circle & Info -->
                <div class="flex items-center gap-3">
                    <div @class([
                        'flex h-10 w-10 items-center justify-center rounded-full border-2 text-sm font-bold transition-all duration-300 shadow-sm',
                        'bg-purple-600 border-purple-600 text-white dark:bg-purple-600' => $num < $current,
                        'border-purple-600 bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400' => $num == $current,
                        'border-gray-200 bg-gray-50 text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-500' => $num > $current,
                    ])>
                        @if($num < $current)
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            {{ $num }}
                        @endif
                    </div>
                    
                    <div class="text-left">
                        <p @class([
                            'text-sm font-bold transition-colors duration-300',
                            'text-purple-600 dark:text-purple-400' => $num == $current,
                            'text-gray-900 dark:text-white' => $num < $current,
                            'text-gray-400 dark:text-gray-500' => $num > $current,
                        ])>{{ $step['title'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ $step['desc'] }}</p>
                    </div>
                </div>

                <!-- Connecting Line -->
                @if(!$loop->last)
                    <div class="h-0.5 flex-1 mx-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div @class([
                            'h-full bg-purple-600 transition-all duration-500',
                            'w-full' => $num < $current,
                            'w-1/2' => $num == $current && $current == 1, /* Optional partial fill animation */
                            'w-0' => $num >= $current,
                        ])></div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Mobile view: simple progress text & active step indicator -->
    <div class="md:hidden flex flex-col gap-2">
        <div class="flex justify-between items-center text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
            <span>{{ __('Setup Step') }} {{ $current }} / 3</span>
            <span class="text-purple-600 dark:text-purple-400 font-bold">{{ $steps[$current]['title'] }}</span>
        </div>
        <div class="h-2 w-full bg-gray-100 dark:bg-gray-900 rounded-full overflow-hidden">
            <div class="h-full bg-purple-600 transition-all duration-300" style="width: {{ ($current / 3) * 100 }}%"></div>
        </div>
    </div>
</div>
