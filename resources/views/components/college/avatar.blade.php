@props([
    'src' => null,
    'name' => '?',
    'size' => 'md',
])

@php
    $name = trim((string) $name);
    $initials = '?';
    if ($name !== '') {
        $parts = preg_split('/\s+/', $name);
        if (count($parts) > 1) {
            $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
        } else {
            $initials = strtoupper(substr($parts[0], 0, 1));
        }
    }

    $sizeClasses = match($size) {
        'sm' => 'h-10 w-10 text-sm font-semibold',
        'md' => 'h-16 w-16 text-lg font-bold',
        'lg' => 'h-24 w-24 text-2xl font-bold',
        'xl' => 'h-32 w-32 text-3xl font-extrabold',
        default => $size // Fallback for custom class strings like 'h-28 w-28 text-3xl'
    };

    // Determine font size class if custom height/width class is passed
    $fontClass = str_contains($size, 'text-') ? '' : (str_contains($size, 'h-') ? 'text-xl font-bold' : '');
@endphp

<div {{ $attributes->merge(['class' => "relative flex shrink-0 items-center justify-center rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 shadow-inner overflow-hidden border border-gray-200 dark:border-gray-750 {$sizeClasses}"]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="" class="h-full w-full object-cover" />
    @else
        <span class="uppercase tracking-wider {{ $fontClass }}">{{ $initials }}</span>
    @endif
</div>
