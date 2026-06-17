# Project Context: College/School Management System

## Overview

A comprehensive **College/School Management System** built with custom PHP architecture. The system manages academic operations, student lifecycle, teacher administration, financial transactions, and administrative workflows for educational institutions.

---

## What the Software Does

The system provides a multi-role platform for managing all aspects of a college/school:

- **Student Management**: Registration, admission approval, academic records, attendance, results, clearance, transcripts, medical records, discipline tracking
- **Teacher/Lecturer Management**: Staff onboarding, course assignments, role management, evaluations, materials review, announcements
- **Academic Administration**: Program management, department/faculty structure, academic sessions/semesters, timetables, course management
- **Financial Management**: Fee structures, payment processing, outstanding fees tracking, scholarships/grants
- **Grading & Assessment**: Result entry, upload, approval workflow, grade points, transcripts
- **Administrative Operations**: Staff assignments, non-teaching staff management, system settings, backup/restore
- **Communication**: Teacher announcements (with admin approval), student-teacher messaging
- **Evaluation System**: Student evaluation of teachers with form management, questions, and response tracking

---

## Core Features

### User Roles & Access

1. **Admin/Owner**: Full system access, setup wizard, student approval, staff management, financial oversight
2. **Teacher/Lecturer**: Course management, student attendance, result entry, materials upload, announcements
3. **Student**: Profile management, course viewing, results access, clearance tracking, evaluation participation

### Key Functional Areas

- **Setup Wizard**: Multi-step initialization (personal info → school → faculties → departments → programs → halls → activation)
- **Admission System**: Student registration with guardian info, admin approval workflow, profile activation
- **Academic Sessions**: Term/semester management with date ranges, current session tracking
- **Image Validation**: Passport photo validation with configurable background color, dimensions, aspect ratio checks
- **Evaluation System**: Dynamic form builder, question management, student responses, analytics
- **Clearance System**: Multi-department clearance tracking (library, finance, academic, hostel, sports, medical)
- **Job Queue System**: Background task processing (file deletion, email sending) via worker.php

---

## Key Assumptions

1. **Single School Instance**: System designed for one institution per installation
2. **Ghana Context**: Phone number validation, Ghana Card format, local currency (implied)
3. **Academic Structure**: Faculty → Department → Program hierarchy
4. **User Types**: Three primary types (admin, teacher, student) with role-based sub-types
5. **Session-Based Auth**: PHP sessions for authentication (no JWT/token-based)
6. **MySQL Database**: All data stored in MySQL with Phinx migrations
7. **File Uploads**: Profile pictures, documents stored in `assets/` directory structure
8. **Email Verification**: Required for account activation
9. **Admin Approval**: Students require admin approval before full access
10. **Setup Mode**: System has initialization phase before full activation

---

## Folder Structure

```
college-school/
├── admin/                    # Admin-specific pages and logic
│   ├── ajax/                # AJAX handlers for admin operations
│   ├── pages/               # Admin page views (students, staff, finance, etc.)
│   ├── setup/               # Admin setup wizard pages
│   ├── dashboard.php        # Admin dashboard
│   └── submit.php           # Central form submission handler for admin
│
├── student/                  # Student-specific pages
│   ├── pages/               # Student feature pages (results, timetable, etc.)
│   ├── setup/               # Student registration/setup pages
│   ├── dashboard.php
│   └── submit.php           # Student form submissions
│
├── teacher/                  # Teacher-specific pages
│   ├── pages/               # Teacher feature pages (courses, students, etc.)
│   ├── dashboard.php
│   ├── setup-personal.php   # Teacher onboarding
│   └── submit.php           # Teacher form submissions
│
├── includes/                 # Core system files
│   ├── components.php       # UI component functions (input, select, button, etc.)
│   ├── database_functions.php # Database CRUD operations
│   ├── functions.php         # Core business logic, user management
│   ├── form-validation.php   # Form validation rules engine
│   ├── helpers.php           # Utility functions (url, asset, etc.)
│   ├── session.php           # Session initialization
│   ├── image_validation.php  # Passport photo validation
│   ├── mailer_functions.php  # Email functionality
│   └── routes.php            # Route matching logic
│
├── layouts/                  # Page templates
│   ├── auth.php             # Authenticated user layout
│   ├── guest.php            # Guest/public layout
│   └── parts/               # Layout components (nav, sidebar)
│       ├── admin-nav.php
│       ├── student-nav.php
│       └── teacher-nav.php
│
├── assets/                   # Static assets
│   ├── css/                 # Compiled CSS
│   ├── js/                  # JavaScript files
│   │   └── functions.js     # Global JS utilities (alert_box, display_form_errors)
│   ├── admins/              # Admin profile pictures
│   ├── students/            # Student profile pictures
│   └── teachers/            # Teacher profile pictures
│
├── db/
│   └── migrations/          # Phinx migration files
│
├── pages/                    # Public/shared pages
│   ├── login.php
│   ├── create-account.php
│   └── generate_env.php     # Environment setup
│
├── tools/                    # Utility pages
│   └── test_passport_validation.php
│
├── index.php                 # Application entry point, router
├── routes.php                # Route definitions
├── middleware.php            # Middleware functions
└── composer.json             # PHP dependencies
```

---

## Important Constraints

### Technical Constraints

1. **No Framework**: Custom PHP implementation, no Laravel/Symfony
2. **Routing**: Custom router in `index.php`, routes defined in `routes.php`
3. **Database**: Custom wrapper around `mysqli`, not PDO or ORM
4. **Component System**: PHP functions generate HTML (not templates like Twig/Blade)
5. **Output Buffering**: Pages use `ob_start()`/`ob_get_clean()` pattern
6. **Session Management**: Manual session handling, user data cached in `$_SESSION['user']`
7. **Error Handling**: Custom error display via `$_SESSION['errors']` and `alert_box()` JS function

### Architectural Constraints

1. **Single Entry Point**: All requests go through `index.php`
2. **Middleware Pattern**: Functions in `middleware.php` wrap routes
3. **Form Submission**: Centralized handlers (`admin/submit.php`, `student/submit.php`, `teacher/submit.php`)
4. **AJAX Pattern**: Separate AJAX files in `admin/ajax/`, return JSON with `status` and `errors` keys
5. **Component Functions**: Must use functions from `includes/components.php` for UI consistency
6. **Validation**: Use `validate_form()` from `includes/form-validation.php`
7. **Database Operations**: Use `fetchData()`, `data_insert()`, `update()`, `delete()` from `includes/database_functions.php`

### Business Logic Constraints

1. **School Must Exist**: `check_school()` middleware ensures school record exists
2. **School Must Be Ready**: `check_school_status()` prevents access if school not activated
3. **User Onboarding**: Admins/teachers must complete setup before full access
4. **Student Approval**: Students need admin approval before dashboard access
5. **Admission Control**: `admission_is_open` middleware controls registration access

---

## Non-Obvious Decisions

### 1. **Component-Based UI System**
- **Decision**: PHP functions generate HTML instead of template files
- **Rationale**: Reusability, consistency, easier maintenance
- **Impact**: All UI must use component functions (`input()`, `select()`, `button()`, etc.)
- **Example**: `input("text", "Name", "name", $value, true, $attributes)`

### 2. **Centralized Form Handlers**
- **Decision**: Single submit file per role (`admin/submit.php`, `student/submit.php`)
- **Rationale**: Easier to manage validation, error handling, security
- **Impact**: All form submissions go through these files, use `submit` parameter to route

### 3. **Session-Based User Caching**
- **Decision**: User data cached in `$_SESSION['user']` with 5-minute refresh
- **Rationale**: Reduces database queries, improves performance
- **Impact**: Must call `user(true)` to force refresh when data changes

### 4. **Route Prefix Groups**
- **Decision**: Routes organized by prefix with shared middleware
- **Rationale**: DRY principle, easier route management
- **Impact**: Routes like `/admin/staff/*` share `['auth', 'valid_admin', 'check_school_status']`

### 5. **Dynamic Route Parameters**
- **Decision**: Routes support `{param}` syntax (e.g., `/admin/approve-student/{index_number}/{guardian}/{id}`)
- **Rationale**: Clean URLs, flexible routing
- **Impact**: Parameters extracted and passed to route files via `extract($params)`

### 6. **Output Buffering Pattern**
- **Decision**: Pages capture content in `$content` variable, then include layout
- **Rationale**: Allows dynamic page titles, scripts injection, consistent layout
- **Pattern**: 
  ```php
  ob_start();
  // ... page content ...
  $content = ob_get_clean();
  require relative_path('layouts/auth.php');
  ```

### 7. **Error Handling Strategy**
- **Decision**: Two-tier error system (session errors + JavaScript functions)
- **Rationale**: Server-side validation + client-side display
- **Implementation**: 
  - Backend: `$errors["field_name"]` or `$errors["system_error"]`
  - Frontend: `display_form_errors(errors, $form)` and `alert_box(message, color)`

### 8. **Image Validation System**
- **Decision**: Custom passport photo validation with configurable parameters
- **Rationale**: Ensure quality, consistency of student/teacher profile photos
- **Features**: Background color detection, dimension checks, aspect ratio validation
- **Future**: Settings system will make parameters admin-configurable

### 9. **Evaluation System Architecture**
- **Decision**: Dynamic form builder with JSON options storage
- **Rationale**: Flexible question types, reusable forms across academic years
- **Features**: Unique codes, scheduling, draft/submitted states, response tracking

### 10. **Job Queue System**
- **Decision**: Background worker for async tasks (file deletion, emails)
- **Rationale**: Non-blocking operations, better UX
- **Implementation**: `jobs/worker.php` processes queued tasks

### 11. **Setup Wizard Flow**
- **Decision**: Multi-step setup with dependency checking
- **Rationale**: Ensures proper initialization order (school → faculties → departments → programs)
- **Middleware**: `check_departments()` prevents program creation without departments

### 12. **Navigation Structure**
- **Decision**: Navigation defined in PHP arrays in `layouts/parts/*-nav.php`
- **Rationale**: Dynamic menu generation, role-based visibility
- **Pattern**: Returns array with `text`, `url`, `icon`, `group`, `items` keys

---

## Database Patterns

### Core Tables
- `users`: Base user table (email, password, type, active)
- `students`: Student-specific data (index_number, program_id, current_year, etc.)
- `teachers`: Teacher-specific data (staff_id, department_id, rank, qualification, etc.)
- `admins`: Admin-specific data (type references user_roles)
- `schools`: Single school record (name, address, email, ready flag)
- `departments`, `faculties`, `programs`: Academic structure
- `academic_sessions`, `semesters`: Academic calendar
- `evaluation_forms`, `evaluation_questions`, `evaluation_responses`: Evaluation system

### Migration System
- Uses **Phinx** for database migrations
- Files in `db/migrations/` with timestamp prefix
- Pattern: `YYYYMMDDHHmmss_description.php`
- A custom command has been created and can be accessed with `php phinx command [options]`

### Database reference
- **Schema snapshot (human-readable):** [.ai/DATABASE_STRUCTURE.sql](.ai/DATABASE_STRUCTURE.sql) — use for discovery; confirm columns on the live DB when it may lag migrations.
- **Migrations (source of truth for changes):** [db/migrations/](db/migrations/) — e.g. semester FK: `20260321231635_add_foreign_key_to_semesters_table.php`.
- Prefer **applied migrations + `DESCRIBE`/`SHOW CREATE TABLE`** over an outdated dump when code and SQL disagree.

### Database Functions
- `fetchData($columns, $tables, $where, $limit, ...)`: Query with joins support
- `data_insert($table, $data)`: Insert with prepared statements
- `update($existing, $data, $table, $keys)`: Update with key matching
- `delete($table, $where)`: Delete operations

---

## Frontend Stack

- **CSS**: TailwindCSS (configured in `tailwind.config.js`)
- **JavaScript**: 
  - jQuery (DOM manipulation, AJAX)
  - Alpine.js (reactive components)
  - Chart.js (data visualization)
- **Icons**: Font Awesome
- **Dark Mode**: Built-in Tailwind dark mode support

### JavaScript Patterns
- Global functions in `assets/js/functions.js`:
  - `alert_box(message, color, time)`: Toast notifications
  - `display_form_errors(errors, $form)`: Form validation display
  - `ajaxCall(options)`: Standardized AJAX wrapper
- AJAX responses expect: `{status: bool, data: {}, errors: {}}`

---

## Security Considerations

1. **Authentication**: Session-based, password hashing with `password_hash()`
2. **Authorization**: Middleware checks (`valid_admin`, `valid_teacher`, `student_ready`)
3. **SQL Injection**: Prepared statements via custom database functions
4. **XSS Protection**: `htmlspecialchars()` used in component functions
5. **CSRF**: Not explicitly implemented (consider adding tokens)
6. **File Uploads**: Validation in `form_data()` function, stored in `assets/` directories

---

## Development Workflow

### Adding New Features

1. **Create Route**: Add to `routes.php` with appropriate middleware
2. **Create Page**: Add PHP file in appropriate directory (`admin/pages/`, `student/pages/`, etc.)
3. **Add Navigation**: Update relevant nav file (`layouts/parts/*-nav.php`)
4. **Create Handler**: Add to appropriate `submit.php` file
5. **Add AJAX** (if needed): Create file in `admin/ajax/` or similar
6. **Use Components**: Leverage functions from `includes/components.php`
7. **Follow Patterns**: Match existing code style and structure

### Database Changes

1. **Create Migration**: Use Phinx to generate migration file
2. **Run Migration**: `php vendor/bin/phinx migrate`
3. **Update Functions**: Modify helper functions if schema changes affect them

---

## Current State

### Completed Features
- ✅ User authentication and role management
- ✅ Admin setup wizard
- ✅ Student registration and approval workflow
- ✅ Teacher onboarding
- ✅ Academic structure management (faculties, departments, programs)
- ✅ Academic sessions and semesters
- ✅ Student pages (dashboard, profile, results, timetable, clearance, transcript, attendance, medical, discipline, job-alerts)
- ✅ Teacher pages (dashboard, profile, courses, materials, timetable, students, attendance, performance, results upload, grades, announcements, messages)
- ✅ Admin pages (students, staff, academic, grading, finance, reports, settings)
- ✅ Image validation system
- ✅ Evaluation system (forms, questions, responses)
- ✅ Staff management (admin staff, teachers, non-teaching staff)
- ✅ Staff assignments and roles (separate for teachers and admin staff)

### In Progress / Planned
- 🔄 Settings management system (brief created in `SETTINGS_FEATURE_BRIEF.md`)
- 🔄 Backend integration for mock pages
- 🔄 Real data integration
- 🔄 Testing phase

### Known Limitations
- Most pages are mockups/prototypes
- Backend handlers exist but not fully tested with real data
- Settings system not yet implemented
- Some features may need refinement based on real-world usage

---

## Key Files Reference

- **Routing**: `index.php`, `routes.php`, `includes/routes.php`
- **Middleware**: `middleware.php`
- **Components**: `includes/components.php`, `includes/component_functions.php`
- **Database**: `includes/database_functions.php`
- **Validation**: `includes/form-validation.php`
- **User Management**: `includes/functions.php` (user(), login(), etc.)
- **Helpers**: `includes/helpers.php` (url(), asset(), relative_path())
- **Layouts**: `layouts/auth.php`, `layouts/guest.php`
- **Navigation**: `layouts/parts/*-nav.php`
- **Form Handlers**: `admin/submit.php`, `student/submit.php`, `teacher/submit.php`
- **JavaScript**: `assets/js/functions.js`

---

## Important Notes for New Developers

1. **Always use component functions** for UI elements - don't write raw HTML
2. **Follow the routing pattern** - add routes to `routes.php`, not direct file access
3. **Use middleware** for access control - don't check permissions in page files
4. **Centralize form handling** - use `submit.php` files, not inline processing
5. **Cache user data** - use `user()` function, call `user(true)` to refresh
6. **Error handling** - use `$errors` array with field keys or `system_error`
7. **AJAX responses** - always return `{status: bool, data: {}, errors: {}}`
8. **Database operations** - use helper functions, not raw SQL
9. **Output buffering** - capture content, then include layout
10. **File paths** - use `relative_path()` and `asset()` helpers, not absolute paths

---

This document should provide sufficient context for a new AI agent to understand the project architecture, patterns, and continue development effectively.
