I have analyzed the project and confirmed it is a **custom PHP application** (not using a standard framework like Laravel or Symfony) with the following structure:

### 1. Core Architecture
- **Routing**: A custom router is implemented in `index.php` that processes requests and loads routes defined in `includes/routes.php`. It supports dynamic parameters (e.g., `{id}`) and middleware.
- **Database**: Uses a custom wrapper around `mysqli` (found in `includes/database_functions.php`) with helper functions for CRUD operations (`data_insert`, `fetchData`, `update`) using prepared statements.
- **Migrations**: Uses **Phinx** for database migrations (`db/migrations/`).
- **Entry Point**: All requests are directed to `index.php`.

### 2. Tech Stack
- **Backend**: PHP 8+, Composer (dependencies: `phpspreadsheet`, `phinx`).
- **Frontend**: **Tailwind CSS** (configured in `tailwind.config.js`), **Alpine.js**, **jQuery**, and **Chart.js**.
- **Templating**: Uses native PHP files in `layouts/` for page structures.

### 3. Directory Structure
- **`admin/`, `student/`, `teacher/`**: Separate directories for each user role containing their specific pages and logic.
- **`includes/`**: Contains core logic, database helpers, session management, and utility functions.
- **`assets/`**: Compiled CSS, JavaScript, images, and fonts.
- **`db/`**: Database migration files.

I understand the codebase structure and am ready to assist with any tasks.