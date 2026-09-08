# Laravel Fix Plan Checklist

This checklist tracks implementation status for `FIX_PLAN.MD` after the current remediation work. It focuses on the active MVC, services, Livewire, Blade, Eloquent, reports, queues, and production operations surface. The archived generated API remains out of scope.

Legend:

- `[x]` Done
- `[~]` Partially done / needs follow-up
- `[ ]` Not done yet

## Scope

- [x] Archive generated legacy API routes, controllers, and API feature tests.
- [x] Stop loading archived API routes from the active application.
- [x] Remove generated/stale API work from the active remediation path.
- [x] Focus remediation on MVC, services, Livewire, Blade, Eloquent, authorization, reports, queues, and production operations.
- [ ] Write a new clean test suite after application behavior is finalized.

## Phase 0 — Baseline and Safety Foundation

- [ ] Create a dedicated remediation branch.
- [~] Verify the application boots.
  - `php artisan route:cache` passes.
- [~] Repair stale route/view cache problems.
  - Broken `time-sheets.create` / `time-sheets.edit` resource routes were removed from active resource registration.
  - Route cache passes after changes.
- [~] Verify Livewire version usage.
  - Livewire v3 conventions were used in touched components.
- [x] Fix `UserFactory` primary-key collision risk.
- [x] Fix `EmployeeFactory` defaults for active employees, schedule, dates, and controlled user linkage.
- [~] Run existing targeted test suites.
  - Focused regression suites pass.
  - Some old generated/stale tests remain intentionally unreliable/out of scope.
- [ ] Run a full clean application test suite after stale test removal/rewrite is complete.
- [ ] Record formal performance baseline for representative workloads.
- [ ] Store query count, SQL time, request time, memory, and row-count baselines.

## Phase 1 — Contain Critical Security Risks

### Maintenance authorization

- [x] Add dedicated maintenance gate.
- [x] Protect maintenance index/export/import/restore/download/delete/settings/quick-backup actions.
- [x] Add denial tests for guest and ordinary authenticated users.

### Registration

- [x] Disable public registration routes.
- [x] Remove active registration links from auth/navigation views.
- [x] Add tests proving registration page and submit routes are disabled.

### Livewire security

- [x] Secure `TimeTable` mutation actions with server-side authorization.
- [x] Secure `TimeSheetApprove` actions with employee-scope authorization.
- [x] Secure `ClinicEmployeeInfo` independently from controller routes.
- [x] Validate `ClinicEmployeeInfo` diagnosis/prescription state before saving.
- [x] Ensure clinic appointments loaded/saved through the current employee relationship.
- [x] Fix clinic save crash when no appointment is selected.
- [x] Secure `SearchEmployee` before dispatching employee data.
- [x] Fix `SearchEmployee` not-found crash.
- [x] Secure flight employee/passenger Livewire mutation components against unauthorized flight changes.

### Appraisal authorization

- [x] Restrict appraisal configuration routes with `can:manage-appraisals`.
- [x] Restrict official appraisal index to HR/admin/super-admin.
- [x] Restrict official appraisal show/finalize/approve flows by role, self, or manager scope.
- [x] Restrict review creation/update/submission to allowed appraiser/employee scope.
- [x] Restrict employee appraisal form assignment through employee update authorization.
- [~] Dedicated appraisal policies for every action are still pending; current protection uses gates, route middleware, and controller checks.

### Auxiliary operations

- [x] Protect operations archive/unarchive routes with `manage-operations`.
- [x] Add admin-only `manage operations` permission.
- [x] Bound and sanitize employee-number bulk archive input.
- [x] Protect clinic MVC routes with `manage-clinic`.
- [x] Protect injury report MVC routes with `manage-clinic`.
- [x] Add admin-only `manage clinic` permission.
- [x] Protect user import/template actions with user creation authorization.
- [x] Protect report index and report generation with model/FormRequest authorization.
- [x] Protect monthly attendance report with a dedicated FormRequest.
- [~] Wider audit of every remaining administrative bulk action is partially complete.

## Phase 2 — Repair Test and Deployment Foundations

- [x] Add focused authorization/regression tests for completed critical fixes.
- [x] Add Livewire regression tests for touched Livewire components.
- [x] Add helper/model tests for corrected timesheet and nullable relationship behavior.
- [x] Verify route cache after code changes.
- [x] Run Pint on dirty files.
- [ ] Build deployment smoke tests covering login, dashboard, Livewire page, denial, and authorized CRUD.
- [ ] Replace stale generated tests with behavior-specific tests after application workflows stabilize.
- [ ] Run full isolated suite after stale test suite cleanup.

## Phase 3 — Unify Authorization and Identity

- [x] Management-scope authorization uses the V2 policy resolver for timesheet and general management paths.
- [x] Document the V2 `scope_policies` / `scope_policy_actors` tables as the authoritative management-scope store; legacy rows remain a migration bridge.
- [x] Compare old and V2 management-scope results for a representative manager with a regression test.
- [x] Management-scope UI writes are synchronized into the authoritative V2 store.
- [x] Remove the legacy actor-resolution fallback after parity validation; canonical `employees.user_id` links are now required.
- [x] Establish canonical identity rules: `users.id` identifies login accounts, `employees.id` identifies HR records, `employees.user_id` links them, and employee numbers are business identifiers.
- [x] Stop rewriting user primary keys when employee numbers change.
- [x] Reconcile users, employees, signatures, approvers, roles, Sanctum tokens, and management scopes around canonical user and employee IDs; signatures, roles, and tokens remain user-owned while approvals and scopes remain employee-owned.
- [x] Review destructive cascades: deleted management scopes deactivate their V2 policy and retain actor history; existing HR record cascades were not broadened.

## Phase 4 — Timesheet Integrity and Safe Writes

- [x] Fix approval-level query so unapproved records from other employees cannot affect the target employee.
- [x] Use index-friendly date comparisons in balance-to-date calculations.
- [x] Stop `TimeTable` from reloading yearly timesheet data on every render.
- [x] Remove production debug logging from `TimeTable` employee switching.
- [x] Remove broken resource create/edit routes that conflicted with employee-specific timesheet routes.
- [x] Timesheet authorization service integration exists and focused tests pass.
- [x] Create a central timesheet mutation service for create/revise/delete/audit/balance/approval behavior.
- [x] Define action-specific permissions for fill, revise, approve, and delete.
- [x] Normalize and validate attendance values in one mutation path.
- [x] Validate schedule ratios and overtime in the mutation path.
- [x] Prevent unauthorized timesheet employee reassignment explicitly.
- [x] Make approval operations transactional with conditional updates/locks where justified.
- [x] Make monthly approval-step creation idempotent under concurrent requests by locking employee rows.
- [x] Convert remaining state-changing approval GET operations to POST with CSRF where applicable.
- [x] Reconcile duplicate `employee_id + day` rows before adding a uniqueness constraint with `timesheets:reconcile-duplicates`.
- [x] Add concurrency/double-approval/reassignment protection and tests.
  - Mutation transactions lock rows, writes are idempotent, approvals are conditional, and the database now enforces unique employee/day rows.

## Phase 5 — Remove Measured Query Inefficiencies

- [x] Eager-load relationships on employee list/directory where used.
- [x] Eager-load room relationships and use `withCount` for employee counts.
- [x] Remove query-heavy employee total day accessors from global `$appends`.
- [x] Make `Room::available` use preloaded `employees_count` when available.
- [x] Improve dashboard chart queries by grouping timesheet aggregates instead of issuing repeated monthly queries.
- [x] Improve report queries with eager loading, bounds, and half-open date ranges.
- [x] Improve clinic employee listing query with targeted eager loading.
- [x] Improve flight detail select queries with ordering and duplicate-safe sync.
- [x] Add timesheet `employee_id, day` index migration.
- [x] Add non-unique flight pivot lookup index migration.
- [~] Some repeated dropdown queries remain in CRUD controllers.
- [~] Some query-producing accessors/methods still need a second pass.
- [x] Add formal query-budget tests for employee, timesheet, report, dashboard, and clinic screens.
- [ ] Record before/after query count, SQL time, request time, and memory measurements.
- [ ] Run `EXPLAIN` for the major optimized queries against representative data.

## Phase 6 — Bound Reports and Background Work

- [x] Bound timesheet report date range to one year.
- [x] Limit large timesheet report result sets.
- [x] Limit employee balance/run report result sets.
- [x] Validate report filters with FormRequests.
- [x] Chunk room, user, and archived employee imports.
- [x] Configure backup job timeout/tries/backoff and queue retry-after values.
- [~] Yearly appraisal aggregation is validated and safer; large MVC-triggered aggregations now queue when the configured threshold and worker configuration justify it.
- [~] Queue large exports/reports where needed; current bounded exports remain synchronous until production volume justifies a queued download workflow.
- [x] Queue yearly appraisal aggregation only when production data size justifies it.
- [ ] Add failed-job/retry/idempotency tests for large jobs.
- [ ] Add memory tests for large imports/exports/reports.

## Phase 7 — Make Backups Recoverable

- [x] Validate backup type.
- [x] Use table allowlist from database metadata.
- [x] Quote identifiers and values safely in SQL export.
- [x] Use unique backup filenames.
- [x] Check storage write result.
- [x] Prevent concurrent backup conflicts with cache lock.
- [x] Avoid cleanup outside the expected backup filename pattern.
- [x] Hide restore/import exception details from users while reporting exceptions internally.
- [x] Configure backup retry timing to respect long backup timeout.
- [~] Backup status logging exists but verification lifecycle is incomplete.
- [x] Verify restores against a disposable MySQL database (49 tables and 68 migration rows restored successfully).
- [ ] Add backup verification status: verified/failed verification.
- [ ] Keep previous verified backup until the new backup passes verification.
- [ ] Add tests for partial table failure, storage failure, corrupted backup, restore failure, and concurrent backup requests.

## Phase 8 — Correct Appraisal Lifecycle

- [x] Make appraisal score updates transactional.
- [x] Make finalization transactional and lock relevant submitted reviews.
- [x] Make yearly aggregation process only employees with submitted/locked reviews or existing official rows.
- [x] Lock official rows during aggregation.
- [x] Make active-version activation transactional.
- [x] Make active-version creation transactional when created as active.
- [x] Validate yearly aggregation year input.
- [x] Avoid one query per row in version-item bulk update.
- [x] Fix official appraisal search to use real employee columns.
- [~] Scoring behavior still needs explicit business-rule confirmation for required/text/partial items.
- [x] Freeze appraisal version items after the version is referenced by a review or official appraisal.
- [~] Re-finalization business behavior is still not formally decided.
- [~] Add appraisal scoring tests for required items, text items, numeric bounds, percentages, and competing activation requests; closed-period, mixed-version, and yearly-aggregation idempotency cases remain.

## Phase 9 — Dependency and Frontend Maintenance

- [x] Run Composer audit and record applicable advisories.
  - Reduced from 62 advisories to 3 Laravel 10 framework advisories that require the Laravel 12 line to resolve.
- [x] Run NPM audit and record applicable advisories.
  - Production dependencies report zero vulnerabilities; remaining development advisories are Vite 5/esbuild issues requiring a Vite 7/8 major upgrade.
- [x] Patch compatible security updates after approval.
  - Updated Laravel 10.50.3, Livewire 3.8.8, Guzzle 7.15.5, PhpSpreadsheet 1.30.6, Laravel Excel 3.1.70, PsySH 0.12.24, and compatible Symfony dependencies.
  - Removed unused Dompdf, Intervention Image, PHPWord, and TinyMCE packages after confirming there are no active code references; HugeRTE is the active editor.
  - Updated Vite 5.4.21, Laravel Vite Plugin 1.3.0, Axios 1.20.0, HugeRTE 1.0.14, Tabler Core 1.5.0, PostCSS 8.5.28, Sass 1.104.0, Tom Select 2.6.2, and Signature Pad 5.1.4.
- [x] Review unused dependencies before removal.
  - Removed unused direct packages `@popperjs/core`, `resolve-url-loader`, `sass-loader`, and `rfs`; retained `signature_pad` because the application assigns it globally for the signature component.
- [x] Plan Laravel upgrade only after security/test stabilization.
  - Laravel remains on the 10.x line for behavior stability; Laravel 12 is the next security upgrade track after regression coverage and compatibility review.
- [x] Consolidate duplicate frontend assets where safe.
  - Removed CDN/runtime duplicates for Notyf, HugeRTE, and Tom Select; archived unused generated copies under `legacy-frontend/`.
  - Removed the stale `resources/js/app.css` Vite reference and consolidated active CSS through `resources/sass/app.scss`.
- [x] Lazy-load editors and page-specific assets where safe.
  - HugeRTE is now a separate `resources/js/editor.js` entry loaded only by clinic/editor pages.
  - Main JavaScript dropped from about 2.1 MB to about 466 KB uncompressed in the production build.
- [x] Run production asset build.
  - `npm run build` passes with Vite 5.4.21.
- [~] Add browser smoke tests for editor/Livewire/upload flows.
  - Livewire behavior is covered by focused PHPUnit tests and the production bundle builds successfully; an authenticated browser smoke run remains pending because no application host/session is available in this environment.

## Phase 10 — Production and Operational Hardening

- [x] Confirm route cache works after the current changes.
- [~] Queue retry/timeout settings improved for backups.
- [~] Verify production `.env` values outside this code review: the current local environment is correctly `APP_ENV=local`, `APP_DEBUG=true`, `LOG_LEVEL=debug`, `FILESYSTEM_DISK=local`, and `QUEUE_CONNECTION=sync`; deployment must override these for production.
- [x] Verify config cache and view cache locally; deployment-environment verification remains operational.
- [~] Verify durable shared storage/object storage needs before horizontal scaling; local storage is currently configured and production object storage remains a deployment decision.
- [~] Configure production log level, rotation, and centralized collection; Laravel daily and external channels are available, but deployment values remain environment-specific.
- [ ] Add sensitive-data redaction where required.
- [~] Monitor slow queries, request duration, queue duration, failed jobs, memory, backups, authorization failures, and app errors; query budgets now cover core MVC pages, while external monitoring is deployment-specific.
- [ ] Add structured audit events for role changes, impersonation, medical edits, imports, approvals, restores, and backup failures.
- [~] Schedule restore drills and authorization reviews; one disposable MySQL restore drill is complete and recurring scheduling remains operational work.

## Phase 11 — Static Analysis and Code Quality

- [x] Run Pint on dirty PHP files.
- [x] Confirm `vendor/bin/phpstan` is not installed.
- [x] Confirm Composer has no static-analysis script configured.
- [~] Add PHPStan/Larastan after dependency approval; Composer installation is currently blocked by the Laravel 10 security-advisory policy, so no dependency changes were made.
- [ ] Establish a realistic static-analysis baseline.
- [ ] Enforce static analysis on new/touched code after baseline exists.
- [ ] Gradually reduce baseline issues.

## Phase 12 — Reassess Laravel Octane

- [x] Remove mutable request-static helper state from `Rules`.
- [x] Make nullable user relation accessors safer for long-lived workers.
- [~] Some Octane-readiness risks have been reduced as part of normal fixes.
- [x] Do not install Octane yet; PHP-FPM remains the measured baseline.
- [~] Finish security/correctness/query/report/queue/dependency/production work first before considering Octane; current remediation is substantially complete but deployment monitoring and static-analysis setup remain.
- [ ] Benchmark realistic PHP-FPM workloads.
- [x] Audit remaining static mutable state, singleton/request state, memory growth, and package compatibility; no application static mutable state was found.
- [ ] Benchmark Octane only after the app is stable under PHP-FPM.
- [ ] Adopt Octane only if measurements prove meaningful benefit.

## Verification Completed

- [x] `vendor/bin/pint --dirty`
- [x] `php artisan route:cache`
- [x] Focused regression tests for current remediation batch:
  - `tests/Feature/OperationsAuthorizationTest.php`
  - `tests/Feature/ClinicAuthorizationTest.php`
  - `tests/Feature/InjuryReportAuthorizationTest.php`
  - `tests/Feature/ReportAuthorizationTest.php`
  - `tests/Feature/SearchEmployeeLivewireTest.php`
  - `tests/Feature/FlightDetailLivewireAuthorizationTest.php`
  - `tests/Feature/TimeTableLivewireTest.php`
  - `tests/Unit/TimeSheetBuilderTest.php`
  - `tests/Unit/UserRelationshipAccessorTest.php`
  - `tests/Feature/AppraisalAuthorizationTest.php`
  - `tests/Feature/RegistrationDisabledTest.php`
  - `tests/Feature/Controllers/EmployeeControllerTest.php`
  - `tests/Feature/Controllers/RoomControllerTest.php`
  - `tests/Feature/Controllers/UserControllerTest.php`
  - `tests/Feature/MaintenanceAuthorizationTest.php`
  - `tests/Feature/TimeSheetAuthorizationServiceTest.php`
- [x] Phase 4 focused verification:
  - `tests/Feature/TimeSheetMutationServiceTest.php`
  - `tests/Feature/TimeSheetApprovalRouteTest.php`
  - 10 tests passed, 26 assertions.
- [x] Result: `54 passed`, `1 risky existing RoomController test`, `111 assertions`.
- [ ] PHPStan/Larastan could not be run because it is not installed.

## Highest-Value Remaining Implementation Order

1. Add query-budget/performance measurements for employee, timesheet, report, dashboard, and clinic pages.
2. Finish backup restore verification using a disposable database.
3. Finish appraisal scoring business-rule tests and concurrent activation tests.
4. Queue large exports/yearly appraisal aggregation only where production size justifies it.
5. Verify production environment, storage, logging, monitoring, and audit-event requirements.
6. Add PHPStan/Larastan baseline after dependency approval.
7. Reassess Octane only after the PHP-FPM app is secure, stable, and measured.
