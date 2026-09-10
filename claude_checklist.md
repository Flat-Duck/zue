# Roadmap checklist — zue

Tracking for [`claude_roadmap.md`](claude_roadmap.md). Evidence in
[`claude_codebase_audit.md`](claude_codebase_audit.md).

> Not to be confused with [`claude_check_list.md`](claude_check_list.md), which tracks the
> earlier remediation and Laravel 13 upgrade programme. That work is complete; this file
> tracks what comes next.

Legend: `[x]` done · `[~]` partial / follow-up needed · `[ ]` not started

**Status:** Phase 1 complete; Phase 2 in progress (2.1, 2.2, 2.3 done + identity redesign)
**Baseline:** 311 tests passing · PHPStan level 5 clean (212-item baseline) · Laravel 13.31.0
**Last updated:** 2026-09-10

---

## Phase 1 — Clear the ground

- [x] 1.1 Delete `CLinicalExamController` (no registered routes)
- [x] 1.2 Delete root debug scripts: `debug_appraisal.php`, `debug_finalize.php`, `verify_sync.php`
- [x] 1.3 Delete `legacy-api/` (52 files, zero registered API routes)
- [x] 1.4 Delete the 26 unused `Http/Resources` classes
- [x] 1.5 Remove commented-out Blade blocks from the 41 views that carry them
- [x] 1.6 Delete `IconTimeSheet` if still unreferenced
- [x] Suite green, `route:list` unchanged, no dangling references

**Phase 1 result (2026-09-10).** 28 files and 1,114 lines removed with zero behaviour change:

| Measure | Before | After |
| --- | ---: | ---: |
| `app/` files | 233 | 205 |
| `app/` lines | 17,917 | 17,385 |
| Blade lines | 15,616 | 15,034 |
| Registered routes | 245 | **245 (unchanged)** |
| PHPStan baseline | 212 | 206 |
| Tests | 311 passing | 311 passing |

Removed: `CLinicalExamController` (no routes), 3 root debug scripts, `legacy-api/` (52 files),
26 unused `Http/Resources`, `IconTimeSheet` and its view, and 23 blocks of commented-out
markup totalling 573 lines across 16 views. Prose comments were left intact.

## Phase 2 — Structure

- [~] 2.1 Contracts extracted and bound for the three genuine seams — `AuditLoggerContract`, `BackupServiceContract`, `FlightDispatchContract`. The timesheet services are deliberately **not** interfaced yet: an interface mirroring 11 public methods of a 614-line class is ceremony, not a seam. Deferred until 2.4 splits them.
- [x] 2.2 Service location reduced 32 → 9. Controllers use constructor injection; Livewire uses method injection. The 9 that remain are in an Eloquent model and a static helper, where there is nothing to inject into — documented in place.
- [x] 2.3 `getApprovalData` 225 → **30 lines**, orchestrating `paginateForPrinting` (25), `collectStageSignatures` (45), `resolveApprovalAvailability` (34) and `stageIsWaiting` (23). Behaviour pinned first by 13 characterization tests.
- [ ] 2.4 Break up `TimeSheetAuthorizationService` (614 lines; `approve` 115, `approvalStages` 103)
- [ ] 2.5 Collapse the 18 duplicated CRUD controllers onto a shared base
- [ ] 2.6 Replace inline `$request->validate()` in 30 files with Form Requests
- [ ] No method over ~60 lines; core services resolved through interfaces

**Findings raised during Phase 2**

- [x] **Identity redesigned at the root (2026-09-10).** The legacy Windows system linked
  everything by employee number: `users.num` held it, and `time_sheet.revised_by` held it too.
  To import `revised_by` directly, this application forced `users.id = users.number =
  employees.id` — three id spaces pretending to be one, enforced only by a `User::booted()`
  hook and a migration that made `users.id` manual. It had already drifted: employee id 6718
  carries number 6716.

  The consequence was severe and silent: `ActorResolver` looked up `employees.user_id`,
  which was **NULL for every row**, so `managedEmployeesQuery()` returned **0 employees
  despite 58 configured management scopes**. Timesheet authorization was non-functional.

  Fixed by separating the three identities and declaring one link:

  | | Before | After |
  | --- | --- | --- |
  | `users.id` | manual, = employee number | auto-increment surrogate |
  | `users.number` | duplicated the employee's | **dropped** (accessor reads the employee) |
  | `employees.user_id` | nullable, unpopulated | **dropped** |
  | link | id coincidence | **`users.employee_id` NOT NULL UNIQUE FK** |
  | approvers | FK to employees, resolved as users | employees, end to end |

  `managedEmployeesQuery()` went from **0 to 11** employees for the admin account.

  Two further instances of the same assumption surfaced and were fixed: the supervisor
  lookup in `getManagedApprovalBuckets` matched **employee ids against user ids**, and
  `WorkflowResolver` selected the dropped column. Dead re-login logic in `UserController`
  — which existed only because user ids used to change — was removed.

- [x] **Legacy importer built (2026-09-10).** `php artisan legacy:import` streams the 25
  converted dump files (60 MB, 1.25M time sheet rows) and reshapes them into the current
  schema on the way in. Full run: **22 seconds**.

  Employee ids are carried across untouched — every other foreign key in the dump points at
  one, so translating them would rewrite 1.2M rows for nothing. Only two things change shape:
  users get a database-assigned id plus a real `employee_id`, and `time_sheets.admin_id`
  (an employee *number* in the old schema) becomes an employee id.

  The dump does not record which employee a user is — `employees.user_id` is NULL on all
  1,115 rows — so the link is derived, by strongest available match:

  | | Rule | Users |
  | --- | --- | :---: |
  | 1 | `users.number` matches `employees.number` | 32 |
  | 2 | `users.id` matches `employees.number` | 23 |
  | 3 | `users.id` matches `employees.id` | 1 |

  Rule 3 exists for exactly one row and would otherwise be lost: user 6718 was keyed on the
  employee's *id*, and that employee's number is 6716. **All 57 legacy users now resolve.**
  Rank order also settles the one genuine collision — users 9676 and 10841 are the same
  person entered twice, and the row carrying an explicit number keeps the account. The
  displaced row is named in the report rather than dropped in silence.

  Every reviser resolves: **61,354 time sheet rows across 9 named employees**, which was the
  whole point of the exercise.

  Not imported, deliberately: roles and permissions (owned by `PermissionsSeeder`, so
  `model_has_roles` is re-pointed **by role name** instead), `backup_logs`, and passwords
  unless `--with-passwords` is passed. Note the plaintext passwords are in the old C# table,
  which is commented out; the Laravel table's are `$2y$12$` bcrypt and safe to carry over.
  A password on an account that already exists is never overwritten.

  `--dry-run` resolves every identity and validates every reference without writing.

- [x] **The dump references 12 employees it does not contain** (3,556 time sheet rows). The
  legacy database never enforced that foreign key, so staff were deleted out from under their
  own attendance history. These rows are skipped and reported by employee id and row count —
  re-exporting the employee table including deleted staff recovers them. `--strict` turns any
  such finding into a failed run.

- [x] **`employees.number` is now `UNIQUE`.** It is the key the whole actor model rests on, and
  it was unenforced. The migration folds any duplicate pair back into the referenced row first.

- [x] **The CSV profile importer was creating duplicate employees.** `EmployeeProfilesImport`
  looked up by number through the global archived-employee scope, so anyone who had left the
  company read as a new hire — **10 duplicate rows** in the development database. Fixed with
  `withArchived()`, covered by a regression test, and now structurally impossible thanks to
  the unique index above.

- [x] **`php artisan db:seed` was broken.** `DatabaseSeeder` called the dump loader in
  `sql_dump/converted/all.php`, which reads `$seeder->command` — a *protected* property — from
  outside the class, a guaranteed fatal. `DatabaseSeeder` now calls `SuperAdminSeeder`, and
  historical data loads through `legacy:import` instead.

- [x] **`SuperAdminSeeder` added.** Creates the one account the system can be signed into,
  keyed by employee number rather than email, because every actor is an employee. Safe to run
  before or after the import: run first it creates a placeholder under the id the dump uses,
  so the import fills in the real record instead of duplicating the person.

  ```
  php artisan migrate
  php artisan db:seed
  php artisan legacy:import --with-passwords
  ```
- [x] `TimeSheetService::getApprovalData` had **zero test coverage** before this phase —
  225 lines feeding three routes. Now covered by `TimeSheetApprovalDataTest` (13 tests).

## Phase 3 — Data model

- [ ] 3.1 Move the HR profile to a 1:1 `employee_details` table (`employees` is 87 columns)
- [ ] 3.2 Review `Employee::$appends` — 4 of 8 traverse relations
- [ ] 3.3 Split `Employee` (791 lines) once its profile has moved
- [ ] 3.4 Decide whether `centers` duplicates `departments.code`
- [ ] Employee list queries no longer carry salaries or national IDs

## Phase 4 — Testing depth

- [ ] 4.1 Policy unit tests for all 16 policies
- [ ] 4.2 A smoke test per role (`timekeeper`, `campboss`, `flightdispatcher`, …)
- [ ] 4.3 Enable PCOV; record a coverage baseline
- [ ] 4.4 Investigate the flaky `PerformanceBudgetTest` failure (observed once, unreproduced)
- [ ] 4.5 Browser tests: flight manifest, timesheet approval, appraisal
- [ ] Every policy directly tested; coverage is a measured number

## Phase 5 — Frontend

- [ ] 5.1 Bundle flatpickr; delete the 4 CDN references across 5 views
- [ ] 5.2 Move 162 inline `style=` attributes and 12 `<style>` blocks into the stylesheet
- [ ] 5.3 Move the 22 inline `<script>` blocks into modules
- [ ] 5.4 Code-split or replace the 1,656 KB editor bundle
- [ ] 5.5 Decide the i18n story (38 views hard-code Arabic; only `en` exists)
- [ ] No view references an external host

## Phase 6 — Quality gates

- [ ] 6.1 Reduce the PHPStan baseline from 212
- [ ] 6.2 Raise PHPStan to level 6, then 7
- [ ] 6.3 Adopt model observers for audit logging and balance recalculation
- [ ] 6.4 CI running Pint, PHPStan and the suite on every push
- [ ] A red gate blocks a merge without anyone remembering to look

---

## Phase 7 — Production readiness *(final phase, immediately before go-live)*

The application is not deployed yet, so this phase is last. **None of it is optional at
go-live**, and 7.1 is the highest-risk item in the whole roadmap.

### 7a. Security configuration

- [ ] 7.1 `APP_ENV=production`, `APP_DEBUG=false`; confirm Ignition routes disappear
- [ ] 7.2 Rate-limit login, password reset and import endpoints (1 of 241 routes is throttled today)
- [ ] 7.3 Security headers and a Content Security Policy
- [ ] 7.4 Rotate `APP_KEY` and every credential for production
- [ ] 7.5 Force HTTPS; secure and `SameSite` cookies

### 7b. Infrastructure

- [ ] 7.6 `QUEUE_CONNECTION=redis` with a supervised worker (currently `sync`)
- [ ] 7.7 Sessions and cache to Redis (currently file-based)
- [ ] 7.8 Uploads to S3-compatible object storage (currently `local`)
- [ ] 7.9 Verify `config:cache`, `route:cache`, `view:cache` and `npm run build` in the deploy

### 7c. Observability

- [ ] 7.10 Error tracking
- [ ] 7.11 Failed-job and queue-depth alerting
- [ ] 7.12 Log aggregation; production log level and rotation
- [ ] 7.13 Slow-query and request-duration monitoring (watch `home1` aggregate cost)
- [ ] 7.14 Uptime checks

### 7d. Go-live

- [ ] 7.15 Document and rehearse the rollback
- [ ] 7.16 Restore drill against production-shaped data
- [ ] 7.17 Seed production permissions and roles; verify each role's access
- [ ] 7.18 Load test, then decide on Octane

---

## Score tracking

| Area | Baseline | Current | Target |
| --- | :---: | :---: | :---: |
| Security | 8 | 8 | 9 |
| Authorization | 8 | 8 | 9 |
| Database | 7 | 7 | 8 |
| Performance | 7 | 7 | 8 |
| Testing | 7 | 7 | 9 |
| MVC / layering | 7 | 7 | 8 |
| Laravel practice | 7 | 7 | 8 |
| Code quality | 6 | 6 | 8 |
| DRY | 6 | 6 | 8 |
| Frontend | 6 | 6 | 8 |
| SOLID | 5 | 5 | 8 |
| Scalability | 5 | 5 | 8 |
| Operations | 5 | 5 | 8 |
| **Overall** | **6.5** | **6.5** | **8.3** |
