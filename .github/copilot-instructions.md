# Copilot Instructions for TechFixPHP

## Project Overview
- **TechFixPHP** is a PHP-based web application for managing tech repair operations.
- The codebase is organized by user roles (admin, customer) under `pages/`.
- Common UI components (e.g., sidebar) are in `pages/admin/template/`.
- Database configuration is in `config/db.php`.

## Architecture & Data Flow
- **Entry points:**
  - `index.html` and `login.html` are public-facing entry points.
  - Admin and customer dashboards are under `pages/admin/` and `pages/customer/`.
- **Database:**
  - All database connections use `config/db.php` (PDO or mysqli, check file for details).
- **Includes:**
  - Shared PHP includes (headers, footers, etc.) are in `includes/`.
- **Templates:**
  - Admin UI uses templates in `pages/admin/template/` for layout consistency.

## Developer Workflows
- **Local development:**
  - Use XAMPP or similar LAMP stack. Place project in `htdocs` and access via `http://localhost/TechFixPHP/`.
- **Debugging:**
  - Use `error_log()` or enable PHP error reporting at the top of PHP files for troubleshooting.
- **No automated build/test scripts** are present; manual browser testing is standard.

## Project Conventions
- **File naming:**
  - Use lowercase, hyphen-separated names for HTML files; PHP files use lowercase and underscores.
- **Role-based directories:**
  - Place admin-specific code in `pages/admin/`, customer code in `pages/customer/`.
- **Templates/components:**
  - Reuse UI components via PHP `include` or `require`.
- **Database access:**
  - Always use the shared connection from `config/db.php`.

## Integration Points
- **External dependencies:**
  - No package manager detected; all dependencies are included manually in `assets/`.
- **Assets:**
  - CSS, JS, and images are stored in `assets/`.

## Examples
- To add a new admin page:
  1. Create a PHP file in `pages/admin/`.
  2. Use `include '../template/sidebar.php';` for consistent navigation.
  3. Access the database via `require '../../../config/db.php';`.

---

**For AI agents:**
- Always follow the directory and naming conventions above.
- Reference existing files for examples before introducing new patterns.
- If unsure about a workflow or pattern, prefer the approach used in `pages/admin/dashboard.php` and `config/db.php`.
