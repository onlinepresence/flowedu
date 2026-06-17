# Project Context: College/School Management System (Laravel)

## Overview

A comprehensive, enterprise-ready **College/School Management System** built on **Laravel** and **Livewire**. The system handles academic structures, student lifecycles, teacher/lecturer assignments, financial ledger operations, results grading, and administrative workflows for educational institutions.

---

## Technical Stack

The system is built on a modern Laravel framework architecture:
- **Backend Framework**: Laravel (PHP 8.2+)
- **Frontend Layer**: Livewire 3 (reactive stateful components), Alpine.js, TailwindCSS, Font Awesome
- **Bundler**: Vite
- **Database**: MySQL (managed via Eloquent ORM and Laravel migrations)
- **PDF Generation**: DomPDF (`barryvdh/laravel-dompdf`) for proforma invoices, student receipts, and transcripts
- **Testing Suite**: Pest / PHPUnit for feature and unit testing

---

## Core Features & Operations

### 1. User Roles & Permission Gating
Access is controlled via database-defined user roles and system type attributes (`type` in `users` table: `admin`, `staff`, `teacher`, `student`).
- **Admin/Staff**: Structured permissions mapped in `config/college.php`. Controls registration approvals, setups, and system configurations.
- **Teacher/Lecturer**: Permitted routes gated via `college.teacher-permission:<slug>`. Controls course timetable, student attendance registers, grading entries, and lecture files.
- **Student**: Access to profiles, course details, grades transcript, clearances, and evaluations.

### 2. Academic & Campus Structure
Managed hierarchically: **Faculty → Department → Program → Courses**.
- **Sessions & Semesters**: Academic calendars are divided into sessions (e.g., academic year) and active semesters.
- **Halls/Hostels**: Campus housing registries.

### 3. Student Lifecycle & Welfare
- **Onboarding / Setup Wizard**: Multi-step registration wizard for admins, teachers, and students.
- **Admission Approvals**: Registration queues where admins approve profile data.
- **Welfare & Health**: Records tracking medical history and student disciplinary actions.
- **Progression**: Tools to batch-promote students to the next level or complete graduation processing.
- **Clearance System**: Multi-department clearance (Library, Finance, SRC, etc. mapped in `config/clearance.php`) required for graduation.

### 4. Financial Portal
- **Fees & Structures**: Structured tuition fees by department, level, and semester.
- **Payments & Ledger**: Real-time payment entries, scholarship/grant allocations, and outstanding dues tracking.
- **Quote Receipts**: Public landing page calculator calculates custom quotas and sends a Proforma PDF to clients.

### 5. Results & Grading System
- **Grades Configuration**: Custom grade point scales (GPAs) for programs.
- **Results Entries**: Teacher grades submission panel supporting Excel imports.
- **Approval Workflow**: Submissions go to HOD/Dean review and approval before publishing.
- **Transcripts**: Dynamically generated official transcript sheets.

### 6. Dynamic Evaluations
- **Form Builder**: Dynamic questionnaire builder for student ratings of teaching staff.
- **Analytics**: Collects and graphs rating aggregates per instructor.

### 7. Administrative Tools & Utilities
- **Impersonation System**: Owner/Admin accounts can log in as other users for troubleshooting, with all actions recorded in `admin_impersonation_logs`.
- **Passport Photo Verification**: Auto-validates uploaded profile images based on background color, size, and aspect ratios configured in `config/image_validation.php`.
- **System Backups**: Interface to generate, list, and download school database backups.
- **Memos System**: Multi-level signatory memos and communications with read-receipts tracking.
- **Teaching Practice (Practicum)**: Supervisor-to-trainee assignment logs and rubrics.

---

## Folder Structure

```
college-school/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Standard Laravel controllers (Landing, Download routes)
│   │   ├── Middleware/       # Gating middleware (Licence check, User-type, Valid-admin)
│   │   └── Kernel.php
│   ├── Livewire/             # Stateful reactive views (Admin, Student, Teacher UI pages)
│   ├── Mail/                 # Quote receipts and requests mailables
│   ├── Models/               # Eloquent Models (School, Student, Teacher, User, etc.)
│   ├── Rules/                # Custom validation rules (e.g., Ghana Phone Prefix checker)
│   └── Services/             # Business logic (Licence calculations, Quote calculations)
│
├── bootstrap/                # Application initialization and middleware registration
│
├── config/                   # Configuration mappings
│   ├── college.php           # Global application settings, permissions, phone prefixes
│   ├── licence.php           # Core pricing, student bands, add-on modules catalogue
│   ├── clearance.php         # Clearance departments definitions
│   └── image_validation.php  # Dimensions, aspect ratio, background checks for photos
│
├── database/
│   ├── factories/            # Model factories for testing
│   ├── migrations/           # Database structure migrations (Source of truth)
│   └── seeders/              # Database seeds for mock datasets
│
├── resources/
│   ├── css/                  # Frontend styling (Tailwind CSS)
│   ├── js/                   # Javascript configuration
│   └── views/                # Blade layouts & template files
│       ├── components/       # Reusable layout and custom UI components
│       ├── livewire/         # Livewire component view templates
│       ├── layouts/          # Base structures (Admin, Student, Teacher main layouts)
│       └── pdf/              # Invoice and transcript PDF styling
│
├── routes/                   # Routing configuration files
│   ├── web.php               # General landing page, download, and fallback routes
│   ├── admin.php             # Admin settings and dashboards (gated)
│   ├── student.php           # Student portal routes (gated)
│   ├── teacher.php           # Teacher portal routes (gated)
│   └── auth.php              # Auth/Login screens
│
├── tests/                    # Pest / PHPUnit test classes
└── vite.config.js            # Vite build asset configs
```

---

## Key Assumptions & Constraints

1. **Single Tenant / School Instance**: Each database instance supports one school installation.
2. **Ghana Context**: Local currency is GHS. Phone number validation restricts prefixes to Ghana-based carriers (configured in `config/college.php`). Memos and ID formats assume local institutional standards.
3. **Database Relationships**: Eloquent models enforce foreign key integrity. Custom database operations must run through Eloquent or Laravel's query builder (no raw PDO/MySQLi connections).
4. **License Feature Gating**: Features must be protected using either the `college.licence:<feature_key>` route middleware or programmatic check `$licenceService->can($feature)`.
5. **UI & Component Consistency**: Pages and forms must utilize Livewire's binding mechanism and TailwindCSS styles. Avoid hardcoding raw CSS or introducing third-party styling frameworks unless explicitly requested.

---

## Developer Workflow

### 1. Adding a Gated Route
1. Define the route path in the appropriate file (`routes/admin.php`, `routes/student.php`, etc.).
2. Apply the licence middleware to ensure security:
   ```php
   Route::get('my-feature', MyFeatureComponent::class)
       ->middleware('college.licence:my_feature_key')
       ->name('admin.my-feature');
   ```
3. Update `layouts/parts/admin-nav.php` (or respective layout) to filter navigation elements by licence checks:
   ```php
   if ($licenceService->can('my_feature_key')) { ... }
   ```

### 2. Database Modifications
1. Create a migration using Artisan:
   ```bash
   php artisan make:migration add_column_to_table_name
   ```
2. Define the schema change, then run:
   ```bash
   php artisan migrate
   ```

### 3. Verification & Testing
Before committing work, run the automated suite to ensure core operations remain functional:
```bash
php artisan test
```

---

*Document version 3.0 · June 2026*
