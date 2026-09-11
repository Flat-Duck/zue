# Zue

Internal employee management application: personnel, attendance and approvals,
appraisals, clinic records, flights, and maintenance. The active application uses
Laravel 13, Livewire 3, Blade, MySQL, and PHP 8.3 or newer. Use the locked Composer
packages and their platform requirements when provisioning PHP.

## Local setup

Install dependencies with `composer install` and `npm ci`. Copy `.env.example`
to `.env`, configure a dedicated development MySQL database, then generate an
application key and run migrations. Never point local setup or tests at production.
Run `npm run dev` during frontend development or `npm run build` for bundled assets.
See [deployment instructions](deploy/README.md) for workers, Reverb, storage and scheduling.

## Verification

Create `.env.testing` with a separate MySQL database named `zue_testing` and its
connection credentials. Clear cached configuration before testing. Browser tests
use `.env.dusk.local` and a separate `zue_dusk` database. Neither contains production data.

- `vendor/bin/phpunit` runs the default Application suite (unit and feature tests).
- `vendor/bin/phpunit --testsuite=Performance` explicitly runs volume benchmarks.
- `php artisan dusk` runs browser tests with the Dusk environment and application server.
- `composer analyse` runs Larastan; suppressions remain debt, not proof of correctness.
- `vendor/bin/pint --dirty` formats changed PHP; `npm run build` verifies assets.
- `composer audit` and `npm audit` check dependencies.

## Identity and access

`users.id` identifies an account; `employees.id` identifies an HR record.
`users.employee_id` links an account to its employee. Employee numbers are business
identifiers, not interchangeable with account IDs. Global employee permissions
remain global; timesheet management scopes are enforced by the scope services.

## Current remediation

[FIX_PLAN_CHECKLIST.md](FIX_PLAN_CHECKLIST.md) is the current implementation ledger.
Older audit documents describe historical snapshots and must not be treated as
current completion evidence. Generated legacy APIs are outside the active MVC scope.
