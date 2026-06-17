# Apex College Management System: UI/UX Guidelines

This document outlines the standard UI/UX patterns, class limitations, and interface components for all frontend development. Follow these rules strictly to ensure visual and functional parity across all modules.

---

## 1. General Page Layout & Typography

- **Page Headers**: Avoid using standalone section headings, subheadings, or icons at the top of the page. Integrate the title, description, and primary action buttons (such as "Add Record", "Export", etc.) into a single, clean header row using the custom page header component.
- **Redundant Titles**: On pages where the main layout shell automatically renders the page title globally (for example, on profile pages), do not include a duplicate local `<x-college.page-header>` component within the view template.
- **Typography**: Utilize the system's defined font family (e.g., *Outfit* or *Inter*). Do not override typography locally unless specifically instructed.
- **Component Re-use**: Always check for and reuse existing custom Blade and Livewire components before writing raw HTML/CSS. This ensures a consistent look and feel and reduces duplicate code. Key components include:
  - `<x-college.page-header>` for all main page titles and headers.
  - `<x-college.filter-card>` for layout out filters consistently.
  - `<x-college.empty-state>` for empty tables/searches/lists.
  - `<x-college.stats-card>` for dashboard summaries and metrics.
  - `<x-college.modal>` and `<x-college.confirm-modal>` for modal overlays.

---

## 2. Tailwind CSS Standards & Colors

> [!WARNING]
> **NO PREMIUM OR CUSTOM TAILWIND SHADES**
> Do not use non-standard Tailwind colors such as `blue-550`, `indigo-650`, or custom spacing configurations unless they are explicitly defined in the project's root tailwind configuration. Stick strictly to standard Tailwind CSS color palettes.

### Core Color Palette
- **Primary Actions / Branding**: `indigo-600` (hover: `indigo-500`)
- **Accent Elements / Alternative Highlights**: `purple-600` (hover: `purple-700`)
- **Neutral Grays**:
  - Light mode: `gray-50` (backgrounds), `gray-100` (borders/dividers), `gray-600` (secondary labels), `gray-900` (primary text).
  - Dark mode: `dark:bg-gray-900` (backgrounds), `dark:bg-gray-800` (cards), `dark:border-gray-700` (borders), `dark:text-gray-300` (secondary), `dark:text-white` (primary).

### Status Badges & State Colors
Always align statuses and state badges with these standard color and border patterns:
- **Success / Completed States**:
  - Actions/Texts: `green-600` / background: `green-50`
  - Badges (Theme aligned): `bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20`
- **Warning / Pending States**:
  - Actions/Texts: `amber-500` / background: `amber-50`
  - Badges (Theme aligned): `bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20`
- **Danger / Deletions / Cancel States**:
  - Actions/Texts: `red-600` or `red-650` / background: `red-50`
  - Badges (Theme aligned): `bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20`

---

## 3. Reusable UI Components & Code Examples

### A. Page Header (`<x-college.page-header>`)
Use this component at the top of all pages to render titles and headers uniformly.
```html
<x-college.page-header :title="__('Student Discipline Records')" :description="__('Monitor and manage student behavioral and disciplinary actions.')">
    <x-slot name="icon">
        <i class="fa-solid fa-gavel"></i>
    </x-slot>
    
    <x-slot name="actions">
        <!-- Export / Action buttons -->
        <button wire:click="create" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            <i class="fa-solid fa-plus"></i> {{ __('Add Record') }}
        </button>
    </x-slot>
</x-college.page-header>
```

### B. Filter Cards (`<x-college.filter-card>`)
Use this component for any filtering interface. Do not write custom grid containers for filters.
By default, the layout uses 4 columns. Align inputs in a consistent responsive grid (e.g. `cols="4"` or `cols="5"` on desktop, collapsing to 2 columns on mobile/tablet) using:
```html
<x-college.filter-card cols="4">
    <div>
        <label for="filter-program" class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            {{ __('Program') }}
        </label>
        <select wire:model.live="programFilter" id="filter-program" class="form-select w-full rounded-md border-gray-300 dark:bg-gray-800 ...">
            <option value="">{{ __('All Programs') }}</option>
            <!-- Options loop -->
        </select>
    </div>
</x-college.filter-card>
```

### C. Stats Cards (`<x-college.stats-card>`)
Use this to display high-level dashboard summaries or report metrics.
```html
<x-college.stats-card 
    :title="__('Total Active Students')" 
    :value="number_format($totalEnrolled)" 
    icon="fa-solid fa-id-card" 
    color="purple" 
/>
```

### D. Empty States (`<x-college.empty-state>`)
Always show a clean empty state graphic and text when a dynamic list or table search yields zero results.
```html
@if($records->isEmpty())
    <x-college.empty-state 
        :title="__('No Records Found')" 
        :description="__('Try adjusting your filters or search query to find what you are looking for.')"
    >
        <x-slot name="icon">
            <i class="fa-solid fa-folder-open text-gray-400"></i>
        </x-slot>
    </x-college.empty-state>
@else
    <!-- Table Content -->
@endif
```

---

## 4. Livewire Loading States & UX

Every dynamic listing table should support real-time user feedback during filtering, sorting, pagination, or action submission.

### A. Localized / Targeted Loading Overlays
Do not allow the layout to jump, flicker, or replace entire structures with generic skeletons. Instead, apply a targeted absolute loading overlay over the table parent component:

```html
<x-card class="overflow-hidden relative">
    {{-- Targeted Loading Overlay --}}
    <div wire:loading.delay wire:target="previousPage, nextPage, gotoPage, search, programFilter" 
         class="absolute inset-0 bg-white/40 dark:bg-gray-900/40 backdrop-blur-[1px] flex items-center justify-center z-10 transition-opacity duration-200">
        <div class="flex items-center gap-2 rounded-lg bg-white/80 px-4 py-2 shadow-lg dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700">
            <i class="fa-solid fa-circle-notch fa-spin text-indigo-600 dark:text-indigo-400"></i>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('Loading data...') }}</span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table>...</table>
    </div>
</x-card>
```

### B. Button Action Loading
When performing operations that modify database records, export reports, or dispatch actions, disable the action button and display a loader inside the button itself to prevent double submissions:

```html
<button wire:click="export" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
    <span wire:loading.remove wire:target="export" class="flex items-center gap-2">
        <i class="fa-solid fa-download"></i> {{ __('Export') }}
    </span>
    <span wire:loading wire:target="export" class="flex items-center gap-2">
        <i class="fa-solid fa-circle-notch fa-spin"></i> {{ __('Exporting...') }}
    </span>
</button>
```

---

## 5. Pagination & Large Dataset Handling

- **Default Pagination Limit**: Keep default pagination to **10 items** per page to ensure optimal loading speeds.
- **Double/Independent Pagination**:
  If a page contains multiple tables (e.g., Welfare page displaying both disciplinary records and medical records), implement separate pagination handles using custom page names:
  ```php
  // In Livewire Controller
  use Livewire\WithPagination;

  $disciplinaryCases = DisciplinaryRecord::orderByDesc('date_of_action')->paginate(10, ['*'], 'disciplinaryPage');
  $medicalRegistry = MedicalHistory::paginate(10, ['*'], 'medicalPage');
  ```
  ```html
  {{-- In Blade View --}}
  {{ $disciplinaryCases->links(value: 'components.vendor.pagination.tailwind', data: ['pageName' => 'disciplinaryPage']) }}
  {{ $medicalRegistry->links(value: 'components.vendor.pagination.tailwind', data: ['pageName' => 'medicalPage']) }}
  ```
- **Export Fallbacks**: For large datasets, provide dedicated export buttons (CSV / Excel) rather than displaying excessively long tables which hurts page responsiveness and scroll experience.

---

## 6. Export & Print Layout Integration

### A. Export Dropdown Workflow
All reporting modules must offer unified export options using an Alpine-driven dropdown structure.

```html
<div x-data="{ open: false }" class="relative inline-block text-left no-print print:hidden">
    <button @click="open = !open" type="button" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
        <i class="fa-solid fa-download"></i> {{ __('Export') }} <i class="fa-solid fa-chevron-down text-xs"></i>
    </button>
    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg dark:bg-gray-800">
        <div class="py-1">
            <button wire:click="exportCSV" @click="open = false" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                <i class="fa-solid fa-file-csv text-green-500"></i> {{ __('Export CSV') }}
            </button>
            <button wire:click="exportExcel" @click="open = false" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                <i class="fa-solid fa-file-excel text-blue-500"></i> {{ __('Export Excel') }}
            </button>
        </div>
    </div>
</div>
```

### B. Printing Layout Best Practices
To ensure printed pages (like invoices, transcripts, and attendance sheets) look like official paper documents and do not contain web elements (sidebars, scrollbars, headers, search fields), follow these practices:

#### 1. Hide Web Infrastructure
Mark all non-printable wrappers (sidebar, top navbar, action buttons, card headers, scroll indicators, page tabs) with:
```html
class="print:hidden"
```
Or use the global print layout helper style at the bottom of the page.

#### 2. Print-Specific Containers & Spacing
Implement a dedicated print container that remains hidden in browser view but renders on paper. Stick to high-contrast, ink-saving black text on white backgrounds, and clear double-column signature blocks at the bottom:
```html
<!-- Hidden in browser, shown on print -->
<div class="hidden print:block print:w-full print:p-0 print:m-0 text-black bg-white">
    <!-- Printable Document Header, Body, and Signatures -->
</div>
```

#### 3. Print Media Queries Overrides
To avoid browser printing scaling pages down or cropping reports, override layout containers to render fully using custom `@media print` style overrides at the bottom of the page:

```html
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
        @page {
            margin: 1.5cm;
        }
    }
</style>

---

## 7. Livewire Modal & Header Action Scoping Best Practices

To prevent layout bugs, auto-showing modals on page load, and broken action buttons, adhere to the following two layout rules.

### A. Modal Rendering Patterns (Hybrid Approach)

We use a hybrid approach to render modals based on their complexity. Avoid hardcoding modals as always-present in the DOM with static attributes unless they are simple toggles with no state.

#### Pattern 1: Conditional Wrapper (`@if ($showModal) ... @endif`)
**Use Case**: Modals with complex forms, file upload components (like Filepond), validation states, or multi-step processes.
- **Why**: Wrapping the modal in an `@if` block ensures that the component, its child components, inputs, validation errors, and upload arrays are completely destroyed and reset to their default states on close. It also prevents the modal from showing on initial page load.
- **Rule**: Set `:show="true"` on the modal component, since it will only be rendered when `$showModal` is set to `true` on the backend.

```html
@if ($showEditModal)
    <x-college.modal name="edit-modal" title="Edit Record" :show="true" livewireSynced="true">
        <!-- Form Content (Includes Filepond / custom inputs) -->
        <x-slot:footer>
            <button type="button" wire:click="closeEditModal">Cancel</button>
            <button type="submit" form="edit-form">Save</button>
        </x-slot:footer>
    </x-college.modal>
@endif
```

#### Pattern 2: Attribute Binding (`:show="$showModal"`)
**Use Case**: Simple modals (e.g. read-only detail views, confirmation boxes, or static notifications) where input state persistence is not an issue.
- **Why**: Keeps the modal markup in the DOM at all times, allowing clean transition animations (like fades and slides) to play fully on close.
- **Rule**: Bind the modal's `:show` attribute directly to the Livewire boolean variable.

```html
<x-college.modal name="confirm-delete-modal" title="Confirm Action" :show="$showDeleteModal" livewireSynced="true">
    <p>Are you sure you want to delete this item?</p>
    <x-slot:footer>
        <button type="button" wire:click="$set('showDeleteModal', false)">Cancel</button>
        <button type="button" wire:click="delete">Confirm</button>
    </x-slot:footer>
</x-college.modal>
```

### B. Header Actions Slot Scoping
Action buttons placed within layout header slots (e.g. `<x-slot name="headerActions">`) are rendered outside the Livewire component's DOM scope. Consequently, using direct `wire:click` handlers on these elements will fail.

**Standard Pattern to Bridge the Scope:**
1. Wrap the header action button in `x-data` and dispatch a window-level Alpine event when clicked: `x-on:click="$dispatch('open-my-modal')"`.
2. Add a window event listener to the outer wrapper `div` of your main Livewire view template: `x-on:open-my-modal.window="$wire.openModal()"`.

*Example View:*
```html
<x-slot name="headerActions">
    <div x-data>
        <button type="button" x-on:click="$dispatch('trigger-upload-modal')">
            Upload Roster
        </button>
    </div>
</x-slot>

<div x-data x-on:trigger-upload-modal.window="$wire.openUploadModal()">
    <!-- Main Livewire Page Content -->
</div>
```

