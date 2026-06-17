@foreach ($items as $item)
    @if (! empty($item['children']))
        @php
            $groupActive = false;
            foreach ($item['children'] as $child) {
                if (! empty($child['route']) && request()->routeIs($child['route'])) {
                    $groupActive = true;
                    break;
                }
                if (! empty($child['href'])) {
                    $cp = ltrim((string) parse_url($child['href'], PHP_URL_PATH), '/');
                    if ($cp !== '' && request()->is($cp, $cp.'/*')) {
                        $groupActive = true;
                        break;
                    }
                }
            }
        @endphp
        <div
            x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }"
            class="relative"
        >
            <button
                type="button"
                @click="open = !open"
                @class([
                    'relative flex w-full items-center justify-between px-6 py-3 text-left text-sm font-semibold transition-colors duration-150',
                    'text-gray-800 dark:text-gray-100' => $groupActive,
                    'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' => ! $groupActive,
                ])
            >
                @if ($groupActive)
                    <span class="absolute inset-y-0 left-0 w-1 rounded-r-lg bg-college-accent" aria-hidden="true"></span>
                @endif
                <span class="inline-flex items-center">
                    @include('livewire.navigation.partials.nav-icon', ['name' => $item['icon'] ?? 'folder'])
                    <span class="ml-4">{{ $item['label'] }}</span>
                </span>
                <svg
                    class="h-4 w-4 shrink-0 text-purple-600 transition dark:text-purple-400"
                    :class="open ? 'rotate-180' : ''"
                    aria-hidden="true"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
            <div
                x-show="open"
                x-transition
                class="mx-4 mt-2 space-y-1 overflow-hidden rounded-md bg-gray-50 p-2 shadow-inner dark:bg-gray-900"
                role="group"
            >
                @foreach ($item['children'] as $child)
                    @if (isset($child['route']))
                        @php $childActive = request()->routeIs($child['route']); @endphp
                        <a
                            wire:navigate
                            href="{{ route($child['route'], $child['route_params'] ?? []) }}"
                            @class([
                                'relative block rounded-sm px-2 py-1.5 pl-3 text-sm font-medium transition-colors duration-150',
                                'text-gray-800 dark:text-gray-100' => $childActive,
                                'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' => ! $childActive,
                            ])
                        >
                            @if ($childActive)
                                <span class="absolute inset-y-0 left-0 w-1 rounded-r bg-college-accent" aria-hidden="true"></span>
                            @endif
                            {{ $child['label'] }}
                        </a>
                    @elseif (isset($child['href']))
                        @php
                            $childPath = ltrim((string) parse_url($child['href'], PHP_URL_PATH), '/');
                            $childActive = $childPath !== '' && request()->is($childPath, $childPath.'/*');
                        @endphp
                        <a
                            wire:navigate
                            href="{{ $child['href'] }}"
                            @class([
                                'relative block rounded-sm px-2 py-1.5 pl-3 text-sm font-medium transition-colors duration-150',
                                'text-gray-800 dark:text-gray-100' => $childActive,
                                'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' => ! $childActive,
                            ])
                        >
                            @if ($childActive)
                                <span class="absolute inset-y-0 left-0 w-1 rounded-r bg-college-accent" aria-hidden="true"></span>
                            @endif
                            {{ $child['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @elseif (isset($item['href']))
        @php
            $itemPath = ltrim((string) parse_url($item['href'], PHP_URL_PATH), '/');
            $itemActive = $itemPath !== '' && request()->is($itemPath, $itemPath.'/*');
        @endphp
        <a
            wire:navigate
            href="{{ $item['href'] }}"
            @class([
                'relative flex w-full items-center px-6 py-3 text-sm font-semibold transition-colors duration-150',
                'text-gray-800 dark:text-gray-100' => $itemActive,
                'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' => ! $itemActive,
            ])
        >
            @if ($itemActive)
                <span class="absolute inset-y-0 left-0 w-1 rounded-r-lg bg-college-accent" aria-hidden="true"></span>
            @endif
            @include('livewire.navigation.partials.nav-icon', ['name' => $item['icon'] ?? 'squares-2x2'])
            <span class="ml-4">{{ $item['label'] }}</span>
        </a>
    @elseif (isset($item['route']))
        @php $itemActive = request()->routeIs($item['route']); @endphp
        <a
            wire:navigate
            href="{{ route($item['route'], $item['route_params'] ?? []) }}"
            @class([
                'relative flex w-full items-center px-6 py-3 text-sm font-semibold transition-colors duration-150',
                'text-gray-800 dark:text-gray-100' => $itemActive,
                'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' => ! $itemActive,
            ])
        >
            @if ($itemActive)
                <span class="absolute inset-y-0 left-0 w-1 rounded-r-lg bg-college-accent" aria-hidden="true"></span>
            @endif
            @include('livewire.navigation.partials.nav-icon', ['name' => $item['icon'] ?? 'squares-2x2'])
            <span class="ml-4">{{ $item['label'] }}</span>
        </a>
    @endif
@endforeach
