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

- [x] 2.1 Contracts extracted and bound for the three genuine seams — `AuditLoggerContract`,
  `BackupServiceContract`, `FlightDispatchContract`. **Revisited after 2.4:** the timesheet
  services are still not interfaced, and that is now a decision rather than a deferral. What an
  interface was going to buy — something small enough to reason about and to substitute in a
  test — the split already delivered: four constructor-injected collaborators, each with one
  job. Adding four interfaces with exactly one implementation each, that nothing swaps and no
  test doubles, would be ceremony. Revisit if a second implementation ever appears.
- [x] 2.2 Service location reduced 32 → 9. Controllers use constructor injection; Livewire uses method injection. The 9 that remain are in an Eloquent model and a static helper, where there is nothing to inject into — documented in place.
- [x] 2.3 `getApprovalData` 225 → **30 lines**, orchestrating `paginateForPrinting` (25), `collectStageSignatures` (45), `resolveApprovalAvailability` (34) and `stageIsWaiting` (23). Behaviour pinned first by 13 characterization tests.
- [x] 2.4 `TimeSheetAuthorizationService` **614 → 153 lines**, and now a facade over four
  collaborators in `App\Services\TimeSheetAuth`: `ApprovalStageResolver` (where the month has
  reached), `ApprovalCommitter` (signing a stage), `ApprovalSheetBuilder` (the printed sheet),
  and `ApprovalEligibility` — the four questions that decide whether one person may sign one
  employee's stage. Listing stages and committing them used to ask those questions in two
  separate copies, so what a screen showed and what the write path enforced could drift; they
  now share one implementation. The step-key vocabulary (`coordinator` vs `fieldcoordinator`,
  the Arabic labels, the legacy `time_sheets` columns) collapsed into an `ApprovalStep` enum.
  No method exceeds 60 lines. Behaviour pinned first by 21 characterization tests.
- [x] 2.5 Nine scaffolded resource controllers collapsed onto `CrudController`: **1,026 → 632
  lines**. Authorize, search, paginate, render, flash and redirect now live in one place, so
  changing how a resource paginates or where a save redirects is one edit rather than nine.
  Names are derived from the model (`Center` → `center`/`centers`/`app.centers`/`centers.*`),
  which is what the scaffolding did by hand. Departments and Rooms keep their own behaviour
  through three hooks — `indexQuery`, `formOptions`, `afterSave` — rather than by copying the
  other seven. Each subclass still declares its four route-bound actions itself: implicit route
  model binding and form request validation both work off the concrete type hints, and both are
  worth the five lines. `PlaneController` had no test; it has seven now.

  The audit said 18. Nine is the real number: the rest (flights, users, time sheets, employees,
  clinic, appraisals) share the *shape* of CRUD but not its behaviour, and forcing them into a
  base class would mean a hook per controller.
- [x] 2.6 **Zero inline `$request->validate()` calls left in `app/`** — 24 call sites across 13
  controllers moved into 21 Form Requests, in the array rule style the existing requests use.
  Two `unique:...,{id}` rules built by string concatenation became `Rule::unique()->ignore()`.

  Moving validation into a form request moves it *before* the controller body, so ten policy
  checks that used to run first would have started running second — an outsider would have got
  a 422 telling them which values the form accepts instead of a 403. Those checks moved into
  the form requests' `authorize()` alongside the rules, which restores the original order.
  `MaintenanceController::import` was already the wrong way round — it validated a 500 MB SQL
  upload before checking the caller could import at all — and is now right. Covered by
  `RequestAuthorizationTest` (13 tests), which asserts 403 rather than merely "not 200".
- [x] No method over ~60 lines; the three genuine seams resolve through interfaces (see 2.1)

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

- [x] **`RegisterController` was dead and broken.** Nothing routed to it — `routes/web.php` says
  `Auth::routes(['register' => false])` — and it validated `unique:users,number` against a column
  the identity redesign dropped, and wrote `User::create(['number' => ...])` to a field that is
  now an accessor. Its view called `route('register')`, which would have thrown. Both removed.
  Self-registration cannot be right for this system anyway: every user must be an existing
  employee, which is the whole point of [[the identity redesign]].

- [x] **27 model relation methods had no return type**, which is why Larastan could not see them
  and why so much of the baseline was `Relation 'x' is not found`. Adding the return types (and
  `@property-read` on `User` and `TimeSheet`) took the **PHPStan baseline from 194 to 158**.

- [ ] **One unexplained test failure.** A single full-suite run reported `1 failed, 404 passed`
  and I did not capture which test it was; nine consecutive runs since have been clean, and the
  performance benchmarks are excluded from the default run so it was not those. Recorded rather
  than dismissed. If it recurs, capture the failing test name before re-running.

**Phase 2 result (2026-09-10).**

| Measure | Before | After |
| --- | ---: | ---: |
| `TimeSheetAuthorizationService` | 614 lines | 153 |
| `TimeSheetService::getApprovalData` | 225 lines | 30 |
| Scaffolded CRUD controllers | 1,026 lines | 632 |
| Inline `$request->validate()` | 24 sites | **0** |
| Service location (`app(Concrete::class)`) | 32 | 9 |
| PHPStan baseline | 206 | **158** |
| Tests | 311 passing | **405 passing** |

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

| Area | Baseline | After phase 2 | Target | What moved it |
| --- | :---: | :---: | :---: | --- |
| Security | 8 | 8 | 9 | ordering fixed, not improved — see 2.6 |
| Authorization | 8 | 8 | 9 | the identity fix restored intended behaviour |
| Database | 7 | 7 | 8 | `employees.number` is unique now; the rest is phase 3 |
| Performance | 7 | 7 | 8 | untouched this phase |
| Testing | 7 | 8 | 9 | 311 → 405 tests, two characterization suites |
| MVC / layering | 7 | 8 | 8 | validation in form requests, logic out of controllers |
| Laravel practice | 7 | 8 | 8 | form requests, typed relations, an enum, generics |
| Code quality | 6 | 7 | 8 | return types everywhere, ~850 duplicated lines gone |
| DRY | 6 | 7 | 8 | `CrudController`, `ApprovalEligibility`, `ApprovalStep` |
| Frontend | 6 | 6 | 8 | untouched this phase |
| SOLID | 5 | 7 | 8 | the 614-line service is four collaborators |
| Scalability | 5 | 5 | 8 | untouched this phase |
| Operations | 5 | 5 | 8 | untouched this phase |
| **Overall** | **6.5** | **7.0** | **8.3** | |
