# Developer Reference: Dynamic, Scoped Role-Based Dashboards

This reference guide describes the architecture and extension patterns for the administrative dashboard in FlowEdu (College of Education portal).

---

## 1. Blended Architecture Overview

The administrative dashboard utilizes a **Blended Architecture** model consisting of two complementary layers:
1. **Layout Archetypes (Visual Layer):** Determines the high-level dashboard layout and structure mapped from the user's current role.
2. **Permission Gates (Security & Action Layer):** Controls the rendering of individual widgets, statistics cards, and action buttons inside the layouts using standard Laravel `@can` checks.

---

## 2. Layout Archetypes Mapping

Every system role defined in `config/college.php` maps to a specific visual archetype in `app/Livewire/Admin/AdminDashboardPage.php` via the `$archetype` property.

The currently supported archetypes and their role mappings:

| Archetype | Mapped Roles | Core Visual Focus |
| :--- | :--- | :--- |
| **`executive`** | `owner`, `principal`, `vice_principal`, `college_registrar` | High-level operations, student enrollment metrics, quick statistics summary. |
| **`academic`** | `head_of_department`, `dean_of_faculty` | Program counts, course catalog status, teacher metrics, grade entry status. |
| **`finance`** | `finance_officer`, `bursar`, `account_clerk` | Invoices, collections, outstanding balances, expenditure lists, transaction feeds. |
| **`welfare`** | `dean_of_students`, `hall_master`, `welfare_officer` | Medical logs, disciplinary records, welfare enrollment statistics. |
| **`hr`** | `human_resource_manager`, `hr_clerk` | Teacher rosters, support staff counts, pending leave requests. |
| **`audit`** | `internal_auditor` | Full system audit logs, active sessions, security tracking. |
| **`general`** | *All other administrative roles* | Basic dashboard layout, user settings links. |

---

## 3. Data Scoping Helpers

To enforce strict data containment, queries loaded for statistics, lists, and charts must be scoped using the appropriate helper methods in `AdminDashboardPage.php`.

### `applyScope(Builder $query, ?Admin $admin, string $relation = null)`
Applies department or faculty scoping to standard database queries.
* **If `department_id` is set:** Filters direct tables by `department_id` or nested relations (e.g. `program`) using `whereHas`.
* **If `faculty_id` is set:** Filters direct tables using `whereHas('department')` or nested relations (e.g. `program.department`) to restrict records to departments under that faculty.

**Usage Examples:**
```php
// 1. Direct Scoping (Table has `department_id`)
$programsCount = $this->applyScope(Program::query(), $admin)->count();

// 2. Nested Scoping (Course belongs to Program which belongs to Department)
$coursesCount = $this->applyScope(Course::query(), $admin, 'program')->count();

// 3. Multi-level Scoping (Result belongs to Course -> Program -> Department)
$pendingGrades = $this->applyScope(Result::query(), $admin, 'course.program')->count();
```

### `applyEvaluationScope(Builder $query, ?Admin $admin)`
Applies scoping for Course/Teacher evaluation responses. Uses `student_department_id` for quick matching.

### `applyLeaveScope(Builder $query, ?Admin $admin)`
Scopes leave requests pending review based on whether the requesting user belongs to the HOD's department or the Dean's faculty.

---

## 4. How to Extend the Dashboard

### A. Adding a New Widget
1. **Controller Query:** Add the metric query in `app/Livewire/Admin/AdminDashboardPage.php` and wrap it with `applyScope()`.
2. **Expose Variable:** Pass the count or collection down to the view inside the `render()` method array.
3. **Blade Template:** Place the widget card or table inside the matching `@elseif ($archetype === '...')` block in `resources/views/livewire/admin/admin-dashboard-page.blade.php`.
4. **Permissions Gate:** Wrap the card in a `@can('admin.permission_slug')` directive to ensure the user has the explicit privilege.

### B. Adding a New Role
1. Register the role and permissions in `config/college.php`.
2. Update the role-to-archetype matching logic in `AdminDashboardPage.php`'s `render()` method:
```php
$archetype = match ($roleSlug) {
    'new_academic_role' => 'academic',
    'new_finance_role'  => 'finance',
    default            => 'general',
};
```
