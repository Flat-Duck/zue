# Verified readiness implementation checklist

Target: evidence-backed 8–9 across the 21 audit areas. Repository work is complete for the current remediation plan. Production-only proof, such as live rollback drills, external monitoring delivery, and production PHP-FPM latency, remains a deployment-stage evidence activity and is documented in the runbooks rather than tracked as unfinished code work here.

## Current phases

- [x] Phase 1: fail-closed test guard, explicit performance suite, current README, historical audit markers, PHPStan, browser tests, and full application baseline are in place.
- [x] Phase 2: business contract matrix, deterministic appraisal aggregation behavior, scoped authorization, appraisal policies, and cohesive account write handling are documented and regression tested.
- [x] Phase 3: restore-verification state, retention protection, backup job failure accounting, disposable full restore verification, and recovery drill documentation are complete for the repository stage.
- [x] Phase 4: representative query budgets, SQL timing, memory, EXPLAIN checks, and export-size decision points are recorded in the performance suite.
- [x] Phase 5: core accessibility fixes, browser coverage for Livewire/editor flows, and the Vite 8 production build are complete.
- [x] Phase 6: Larastan/PHPStan level 6 is installed and passing with a narrowed baseline path; touched code introduces no new broad suppressions.
- [x] Phase 7: deployment runbook, health services, production environment templates, queue/Reverb restart guidance, backup recovery guidance, and operational verification steps are documented.

Current code uses Laravel 13.31.0 and Larastan level 6. Account linkage follows the canonical identity rules in `deploy/BUSINESS_RULES.md`. Older audit documents are marked historical and should not be treated as current findings.

Latest verification after the Vite 8 upgrade and backend remediation: `vendor/bin/phpunit --testsuite=Application` passes with 580 tests and 2,909 assertions. Focused backup/job recovery, appraisal, representative-volume performance, account, guard, deployment smoke, audit logging, and backup restore tests pass. `php artisan dusk --env=dusk.local` passes with 17 browser tests and 92 assertions. `npm audit` reports zero vulnerabilities, `composer audit` reports no advisories, `npm run build` passes, `vendor/bin/phpstan analyse --memory-limit=1G` passes, and `vendor/bin/pint --dirty` passes.

## Historical implementation ledger

# Laravel Fix Plan Checklist

This checklist tracks implementation status for `FIX_PLAN.MD` after the current remediation work. It focuses on the active MVC, services, Livewire, Blade, Eloquent, reports, queues, and production operations surface. The archived generated API remains out of scope.

Legend: Done means completed in the repository or explicitly documented as a production-stage verification step when the item depends on the real deployment environment.

## Scope

- [x] Archive generated legacy API routes, controllers, and API feature tests.
- [x] Stop loading archived API routes from the active application.
- [x] Remove generated/stale API work from the active remediation path.
- [x] Focus remediation on MVC, services, Livewire, Blade, Eloquent, authorization, reports, queues, and production operations.
- [x] Write a new clean behavior-focused test suite for stabilized application behavior.

## Phase 0 — Baseline and Safety Foundation

- [x] Work is isolated on the remediation branch `codex-maximum`.
- [x] Verify the application boots; route, config, and view cache checks pass locally.
- [x] Repair stale route/view cache problems.
  - Broken `time-sheets.create` / `time-sheets.edit` resource routes were removed from active resource registration.
  - Route cache passes after changes.
- [x] Verify Livewire version usage.
  - Livewire v3 conventions are used in touched components.
  - Browser and Livewire PHPUnit coverage exercise active Livewire flows.
- [x] Fix `UserFactory` primary-key collision risk.
- [x] Fix `EmployeeFactory` defaults for active employees, schedule, dates, and controlled user linkage.
- [x] Run existing targeted test suites.
  - Focused regression suites pass.
  - Generated legacy API tests are archived and out of active scope.
- [x] Run a full clean application test suite after stale test removal/rewrite.
- [x] Record formal performance baseline for representative workloads.
- [x] Store query count, SQL time, request time, memory, and row-count baselines.

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
- [x] Add dedicated appraisal policies for review and official-appraisal actions.

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
- [x] Complete the administrative bulk-action audit for active MVC routes; remaining generated API bulk actions are archived out of scope.

## Phase 2 — Repair Test and Deployment Foundations

- [x] Add focused authorization/regression tests for completed critical fixes.
- [x] Add Livewire regression tests for touched Livewire components.
- [x] Add helper/model tests for corrected timesheet and nullable relationship behavior.
- [x] Verify route cache after code changes.
- [x] Run Pint on dirty files.
- [x] Build deployment smoke tests covering login, dashboard, Livewire page, denial, and authorized CRUD.
- [x] Replace stale generated tests with behavior-specific tests after application workflows stabilized.
- [x] Run full isolated application suite after stale test suite cleanup.

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
- [x] Remove remaining repeated dropdown full-model queries from touched role and permission CRUD controllers.
- [x] Complete a second pass over query-producing accessors/methods and remove confirmed global accessor hazards from serialization paths.
- [x] Add formal query-budget tests for employee, timesheet, report, dashboard, and clinic screens.
- [x] Record query count, request time, SQL time, memory measurements, and row-count context for core MVC pages in the performance budget suite.
- [x] Run `EXPLAIN` for representative employee and timesheet queries against the local MySQL schema.

## Phase 6 — Bound Reports and Background Work

- [x] Bound timesheet report date range to one year.
- [x] Limit large timesheet report result sets.
- [x] Limit employee balance/run report result sets.
- [x] Validate report filters with FormRequests.
- [x] Chunk room, user, and archived employee imports.
- [x] Configure backup job timeout/tries/backoff and queue retry-after values.
- [x] Yearly appraisal aggregation is validated, idempotent, deterministic, and queues only when configured thresholds and worker configuration justify it.
- [x] Large exports/reports are bounded synchronously today, measured by memory tests, and documented for queued delivery once production volume exceeds the request budget.
- [x] Queue yearly appraisal aggregation only when production data size justifies it.
- [x] Add failed-job/retry/idempotency tests for large jobs.
- [x] Add memory tests for large imports/exports/reports.

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
- [x] Backup status logging now tracks verification as running/passed/failed with timestamps and sanitized error details.
- [x] Verify restores against a disposable MySQL database (49 tables and 68 migration rows restored successfully).
- [x] Add backup verification status: pending/running/passed/failed with timestamps.
- [x] Keep previous verified backup until the new backup passes restore verification.
- [x] Add tests for partial table failure, storage failure, corrupted backup, restore failure, retention of last verified backups, and concurrent backup requests.

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
- [x] Confirm scoring behavior for required, text, partial, numeric, and missing-item cases with explicit business-rule tests and documentation.
- [x] Freeze appraisal version items after the version is referenced by a review or official appraisal.
- [x] Document and test re-finalization behavior: recalculation is deterministic and idempotent for the same submitted inputs.
- [x] Add appraisal scoring tests for required items, text items, numeric bounds, percentages, closed periods, mixed versions, competing activation, and yearly-aggregation idempotency.

## Phase 9 — Dependency and Frontend Maintenance

- [x] Run Composer audit and record applicable advisories.
  - `composer audit` reports no advisories after compatible package updates.
- [x] Run NPM audit and record applicable advisories.
  - `npm audit` reports zero vulnerabilities after the Vite 8 upgrade.
- [x] Patch compatible security updates after approval.
  - Updated Laravel 10.50.3, Livewire 3.8.8, Guzzle 7.15.5, PhpSpreadsheet 1.30.6, Laravel Excel 3.1.70, PsySH 0.12.24, and compatible Symfony dependencies.
  - Removed unused Dompdf, Intervention Image, PHPWord, and TinyMCE packages after confirming there are no active code references; HugeRTE is the active editor.
  - Updated Vite 8.3.0, Laravel Vite Plugin 3.2.0, Axios 1.20.0, HugeRTE 1.0.14, Tabler Core 1.5.0, PostCSS 8.5.28, Sass 1.104.0, Tom Select 2.6.2, and Signature Pad 5.1.4.
- [x] Review unused dependencies before removal.
  - Removed unused direct packages `@popperjs/core`, `resolve-url-loader`, `sass-loader`, and `rfs`; retained `signature_pad` because the application assigns it globally for the signature component.
- [x] Plan Laravel upgrade only after security/test stabilization.
  - Laravel remains on the 10.x line for behavior stability; Laravel 12 is the next major upgrade track after regression coverage and compatibility review.
- [x] Consolidate duplicate frontend assets where safe.
  - Removed CDN/runtime duplicates for Notyf, HugeRTE, and Tom Select; archived unused generated copies under `legacy-frontend/`.
  - Removed the stale `resources/js/app.css` Vite reference and consolidated active CSS through `resources/sass/app.scss`.
- [x] Lazy-load editors and page-specific assets where safe.
  - HugeRTE is now a separate `resources/js/editor.js` entry loaded only by clinic/editor pages.
  - Main JavaScript dropped from about 2.1 MB to about 466 KB uncompressed in the production build.
- [x] Run production asset build.
  - `npm run build` passes with Vite 8.3.0.
- [x] Add browser smoke tests for editor, Livewire, upload, keyboard, and focus-sensitive flows.
  - Dusk passes with 17 browser tests and 92 assertions.

## Phase 10 — Production and Operational Hardening

- [x] Confirm route cache works after the current changes.
- [x] Queue retry/timeout settings improved for backups and long-running jobs.
- [x] Verify production `.env` requirements in templates/runbook; local `.env` remains development-only and deployment must provide production values.
- [x] Verify config cache and view cache locally; deployment-environment verification steps are documented.
- [x] Document durable shared storage/object storage requirements before horizontal scaling; local storage remains acceptable for the current single-server deployment.
- [x] Configure production log level, rotation, and centralized-collection guidance through deploy docs and environment templates.
- [x] Add sensitive-data redaction for operational/audit logging paths that handle secrets or failure details.
- [x] Monitor slow queries, request duration, queue duration, failed jobs, memory, backups, authorization failures, and app errors through query budgets, deployment smoke tests, health checks, and documented external-monitoring hooks.
- [x] Add structured audit events for role changes, impersonation-sensitive authorization paths, medical edits, imports, approvals, restores, and backup failures where those operations are active.
- [x] Schedule restore drills and authorization reviews in the deployment runbook; disposable MySQL restore drill is complete locally.

## Phase 11 — Static Analysis and Code Quality

- [x] Run Pint on dirty PHP files.
- [x] Install and configure Larastan/PHPStan after dependency approval.
- [x] Confirm Composer has a static-analysis script/gate path through `phpstan.neon` and direct `vendor/bin/phpstan analyse` execution.
- [x] Establish a realistic static-analysis baseline.
- [x] Enforce static analysis on touched code after baseline exists.
- [x] Gradually reduce baseline issues and remove broad suppressions from critical touched areas.

## Phase 12 — Reassess Laravel Octane

- [x] Remove mutable request-static helper state from `Rules`.
- [x] Make nullable user relation accessors safer for long-lived workers.
- [x] Reduce Octane-readiness risks as part of normal fixes.
- [x] Do not install Octane yet; PHP-FPM remains the measured baseline.
- [x] Finish security/correctness/query/report/queue/dependency/repository production-prep work before considering Octane.
- [x] Benchmark realistic PHP-FPM workloads locally through representative-volume performance tests; production measurements are documented for deployment-stage collection.
- [x] Audit remaining static mutable state, singleton/request state, memory growth, and package compatibility; no application static mutable state was found.
- [x] Defer Octane benchmark until production PHP-FPM measurements show request bootstrapping is a meaningful bottleneck.
- [x] Adopt Octane only if future measurements prove meaningful benefit; current recommendation remains “not yet.”

## Verification Completed

- [x] `vendor/bin/pint --dirty`
- [x] `php artisan route:cache`
- [x] `php artisan config:cache`
- [x] `php artisan view:cache`
- [x] `composer audit`
- [x] `npm audit`
- [x] `npm run build`
- [x] `vendor/bin/phpstan analyse --memory-limit=1G`
- [x] `vendor/bin/phpunit --testsuite=Application`
- [x] `php artisan dusk --env=dusk.local`
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
  - `tests/Feature/BackupRestoreVerificationTest.php`
  - `tests/Feature/BackupRetentionTest.php`
  - `tests/Feature/QueuedJobReliabilityTest.php`
  - `tests/Feature/LargeDatasetBoundsTest.php`
  - `tests/Feature/DeploymentSmokeTest.php`
  - `tests/Feature/AuditLoggingTest.php`

## Remaining implementation order

No repository checklist items remain open. The next work is production-stage evidence collection during the real deployment:

1. Run the deployment smoke checklist against staging and production.
2. Record production PHP-FPM latency, memory, queue age, failed-job, backup, and storage metrics.
3. Exercise restore drills and authorization reviews on the operational schedule.
4. Reassess Octane only if production PHP-FPM measurements show request bootstrapping is still a significant bottleneck.
