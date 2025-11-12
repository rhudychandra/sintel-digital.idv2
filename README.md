# Sinar Telekom Dashboard System

A PHP (XAMPP) based dashboard for inventory, sales, finance, and reports.

## Quick Start (Local)

- Requirements: XAMPP (Apache + MySQL + PHP 8+)
- Copy `config/config.sample.php` to `config/config.php`, then fill your DB credentials.
- Create database `sinar_telkom_dashboard` (or match `DB_NAME` in config).
- Import your schema/data (via phpMyAdmin or scripts). Optionally run provided migrations in `database/migrations/`.
- Access locally: `http://localhost/sinartelekomdashboardsystem/`

## Sensitive Files

- `config/config.php` is intentionally ignored by Git. Do not commit real credentials.
- Share `config/config.sample.php` instead.

## Deployment Notes

- Paths use `BASE_PATH` from `config/config.php` so app can run in root or subfolders.
- Ensure the `assets/` and `modules/` folders are uploaded.
- If using Apache, `.htaccess` in `config/` may include needed rules.

## Database Utilities

- Reset scripts and migrations are available under `database/migrations/`.
- Seeding initial inventory example: `seed_initial_inventory.php` (per cabang).
- Use with caution on production; take backups before destructive operations.

## Versioning & Git

- `.gitignore` protects credentials and temporary files. Evidence images and migration SQL are tracked.
- Recommended to enable branch protection on main in your remote.

## Troubleshooting

- If background images don’t load on hosting, ensure CSS uses relative paths and `BASE_PATH` is set correctly.
- After moving to a new subfolder, clear browser cache if assets seem missing.