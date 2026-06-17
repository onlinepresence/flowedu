@php
    $name = $name ?? 'squares-2x2';
    $fa = match ($name) {
        'user' => 'fa-user',
        'squares-2x2' => 'fa-compass',
        'building-office-2' => 'fa-school',
        'identification' => 'fa-id-card',
        'building-library' => 'fa-building',
        'briefcase' => 'fa-briefcase',
        'book-open' => 'fa-book',
        'home-modern' => 'fa-hotel',
        'power' => 'fa-toggle-on',
        'academic-cap' => 'fa-graduation-cap',
        'building-office' => 'fa-school',
        'pencil-square' => 'fa-pen-to-square',
        'user-group' => 'fa-users',
        'users' => 'fa-users',
        'currency-dollar' => 'fa-dollar-sign',
        'chart-bar' => 'fa-chart-bar',
        'cog-6-tooth' => 'fa-gear',
        'wrench-screwdriver' => 'fa-wrench',
        'eye' => 'fa-eye',
        'shield-check' => 'fa-shield',
        'clipboard-document-check' => 'fa-clipboard-check',
        'trash' => 'fa-trash',
        'clipboard-document-list' => 'fa-clipboard-list',
        'calendar-days' => 'fa-calendar-days',
        'heart' => 'fa-heart',
        'exclamation-triangle' => 'fa-triangle-exclamation',
        'envelope' => 'fa-envelope',
        'folder' => 'fa-folder',
        default => 'fa-circle',
    };
@endphp
<span class="inline-flex h-5 w-5 shrink-0 items-center justify-center text-current opacity-90" aria-hidden="true">
    <i class="fa-solid {{ $fa }} text-sm leading-none"></i>
</span>
