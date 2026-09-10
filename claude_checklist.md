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

- [ ] **An unexplained intermittent test failure, seen twice and never captured.** Two separate
  full-suite runs reported one failure (`1 failed, 404 passed`, later `1 failed, 416 passed`);
  both times only the summary line was kept, so the failing test is unknown. Roughly thirty
  runs since — including seventeen consecutive runs whose full output was saved specifically to
  catch it — have all been clean. It is not the performance benchmarks: those are excluded from
  the default run. Best guess is a test with an order or timing dependency that only bites on a
  particular interleaving. Recorded rather than dismissed; the next sighting needs the full
  output kept, not the summary.

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

- [x] 3.1 The HR profile moved to a 1:1 `employee_details` table. **`employees` went from 87
  columns to 21**; the 65 that moved are identity documents, payroll, education, family,
  banking and notes. Reading one still works through the employee — `$employee->nationality`
  resolves via the relation — but writing goes through `Employee::saveProfile()`, which splits
  a flat set of form fields across both tables. Which section lives where is declared once, in
  the section definitions, and `EmployeeProfileStorageTest` asserts the employee list never
  reads the profile table at all.

  Moving it surfaced an N+1 the old shape had been hiding: the clinic employee list showed a
  phone number per row. Eager-loading the profile took that page from **26 queries to 7**, and
  the query budgets were tightened to match (`clinic.index` 25 → 10, `employees.index` 20 → 10)
  so they protect the pages rather than merely sitting above them.

- [x] 3.2 `Employee::$appends` is now empty. Four of the eight appended accessors walked a
  relation, so **serialising ten employees ran 36 queries of its own**. Every accessor is still
  there; nothing appends them. Serialising ten employees now costs **0** extra queries, pinned
  by a test. Nothing consumed them from serialized output — they are read as properties in
  Blade, and Livewire serializes an Eloquent model by class and key, not by `toArray()`.

- [~] 3.3 `Employee` **830 → 425 lines**. The profile definition (sections, validation rules,
  relation rules) moved to `App\Services\Employees\ProfileDefinition`, and the 179-line
  `managedEmployeesQuery()` to `App\Services\Employees\ManagedEmployeeQuery`; the model keeps
  the same public surface and delegates. A commented-out duplicate `canManage()` and a `boot()`
  that only re-registered a scope the trait already adds were removed.

  **Not the ~300 the roadmap asked for, and I stopped rather than force it.** What remains is 41
  methods averaging seven lines: relations, accessors and small predicates. Moving those into
  traits would move lines without making anything clearer.

- [ ] 3.4 **`centers` does not duplicate `departments.code` — it is worse than that, and this
  needs your decision.** Measured against the real data:

  - 132 centres and 126 departments are in use, across **153 distinct pairings** — so the two
    are not 1:1, and a centre does not determine a department.
  - `centers.name` is almost always identical to `centers.code` (`5W20` / `5W20`). Centres have
    no names; the import created them from the cost centre code.
  - The codes usually match across the pair but not always: centre `5J09` sits under department
    `8J09`, centre `5G30` under `8G30`, centre `5S40` under `5M16`.
  - Centres are duplicated across the `5`/`8` prefix: `5G30` and `8G30` are separate centres
    pointing at the same department.
  - **Two generations of records coexist.** Departments from the legacy dump are English and
    have no code (`MAINT`, `PROD`, `Gas Plant`); departments from the personnel export are
    Arabic and do. 1,115 staff are still filed under the codeless legacy ones.

  What that means is a question about the business, not the schema: is a cost centre a budget
  line that a department reports against — in which case the tables are right and the data
  needs reconciling — or is it another name for the department, in which case one of them goes?
  **My recommendation:** keep both tables, treat the cost centre as the budget line, and
  reconcile the two generations during the fresh import rather than by migration. Tell me which
  it is and I will do it.

- [x] Employee list queries no longer carry salaries or national IDs — asserted, not assumed

**Findings raised during Phase 3**

- [x] **The personnel importer was creating organisation records out of spreadsheet
  corruption.** It already refused bank account numbers mangled into scientific notation, but
  not cost centre or department codes — so `5.00E+10` became a real centre, `5.00E+20` a real
  department, and staff were filed under both. The guard now covers organisation codes too, and
  a corrupted value leaves the employee unlinked with the problem reported rather than inventing
  a place to put them. The development database still holds 3 such centres and 2 departments;
  they disappear on the fresh import rather than needing a cleanup migration.

- [x] **Two model relations had no generic annotation**, which is why `ManagementScope`'s
  attributes read as undefined all through the scope resolver. Annotating them, plus a
  `@property` block on `ManagementScope`, took the **PHPStan baseline from 158 to 147**.

## Phase 4 — Testing depth

- [x] 4.1 **All 16 policies now tested directly**, in `PolicyContractTest` — 706 assertions
  stating which permission each ability requires. The negative half matters more than the
  positive: the realistic mistake is a policy checking the right resource with the wrong verb,
  so every ability is also asserted to refuse each *sibling* permission on the same resource,
  and each permission from another resource. `restore` and `forceDelete` are asserted refused
  even to someone holding all 77 permissions, and a signed-in user with none reaches nothing.

  Written first as 189 separate cases, which took 56 seconds — one database refresh apiece,
  nearly doubling the suite. Consolidated into 7 tests carrying the same 706 assertions in 11
  seconds; the failure messages name the contract the test name used to. Mutation-checked by
  pointing `CenterPolicy::update` at `view centers`: both halves catch it.

- [x] 4.2 **`RoleAccessTest` states what each role reaches**, page by page, on a freshly seeded
  install — previously the access model existed only in `PermissionsSeeder` and in whatever the
  live database had drifted to.

- [ ] **Four roles the time sheet workflow depends on hold no permissions at all.**
  `PermissionsSeeder` creates `supervisor`, `fieldcoordinator`, `superintendent` and `campboss`
  with nothing granted, yet those are the names the approval flow puts in `required_role`. On a
  fresh install someone given one of those roles alone reaches no page whatsoever — it works
  today only because the same people also hold `user`. Asserted as it stands so the situation is
  visible rather than surprising. **Deciding what each of those four should actually be able to
  reach is yours to make**, and I would rather ask than invent an access matrix for your staff.

- [x] 4.3 **Coverage is a number now: 57.96% of lines** (3,664 of 6,322), 57.31% of methods,
  36.32% of classes, across 430 tests and 2,520 assertions. No class sits at zero.

  PCOV is not installed, but `xdebug.so` already sits in this PHP's extension directory,
  unloaded — so coverage runs without editing `php.ini` and without slowing every other request:

  ```bash
  php -d zend_extension=xdebug -d xdebug.mode=coverage -d memory_limit=2G \
      vendor/bin/phpunit --coverage-text --coverage-html=storage/coverage
  ```

  It takes about five and a half minutes against one minute without, so it is a deliberate run
  rather than something to fold into the default command.

  Where the gaps actually are, by lines never executed. This is the list phase 4 should be
  measured against, not the percentage:

  | Lines uncovered | Covered | Class |
  | ---: | ---: | --- |
  | 120 | 43% | `TimeSheetMutationService` — the write path for attendance |
  | 90 | 6% | `Appraisals\AppraisalOfficialController` |
  | 68 | 56% | `BackupService` |
  | 64 | 55% | `ManagementScopeService` |
  | 63 | 52% | `TimeSheetAuth\WorkflowResolver` |
  | 61 | 44% | `MaintenanceController` |
  | 61 | 60% | `TimeSheetController` |
  | 55 | 53% | `Helpers\TimeSheetBuilder` — leave balance arithmetic |
  | 51 | 4% | `Livewire\ClinicEmployeeInfo` |
  | 41 | 2% | `Imports\RoomsImport` |

  The first and eighth are the ones that would worry me: `TimeSheetMutationService` and
  `TimeSheetBuilder` are where attendance is written and leave balances are computed, and
  between them 175 lines had never run under a test.

- [x] **The worst of that gap is closed.** `TimeSheetBuilder::calculateBalance` — the leave
  balance every screen reads off an employee's record — had **no test of its own**; only the
  `ToDate` variant did. It now has seven: the rotation ratio (`14/14` earns a day per day worked,
  `40/80` half a day), that all four worked codes count and both absence codes deduct, that an
  unrecognised code does neither, that the carried-over balance is added, that one employee's
  days never reach another's, that the running and to-date figures agree, and that recalculating
  persists to `employees.total_balance`.

  `fillDateOrRange` and `deleteDateOrRange` — the range path behind the fill screen, which
  writes attendance for a span of days — had never run either. Six tests now cover inclusive
  range boundaries, the single-day form, an empty range being refused rather than silently
  writing nothing, deleting only the days in the range, and one employee's range never touching
  another's days.

  `TimeSheetMutationService` went from **43% to 56%** of lines. Overall coverage moved only
  57.96% → **58.43%**, which is the point worth making about the metric: thirteen tests over the
  two most consequential pieces of arithmetic in the system move the percentage by half a point.
  The percentage is a tripwire, not a target — the table above is what to work from.
  `TimeSheetBuilder` is still at 2 of 9 methods; `build`, `create`, `destroy`,
  `calculateBulckBalanceToDate`, `unApprovedTimeSheetLevel` and `approvrTimeSheets` remain
  untested and are the next thing I would take.

- [~] 4.4 **The intermittent failure was investigated and not reproduced.** A correction first:
  I had said the performance tests were excluded from the default run — only `tests/Performance`
  is, and `PerformanceBudgetTest` runs every time, which makes it the likeliest suspect since it
  asserts query counts. Ruled out along the way: the permission cache (the testing store is
  `array`, so nothing persists between runs) and test-order dependence (eight runs with
  `--order-by=random`, all clean, on top of seventeen sequential runs whose full output was kept
  specifically to catch it).

  Since sampling was not finding it, the budget test now **reports the SQL it ran** when a budget
  is exceeded, with repeated statements counted — a page that lazy-loads shows the same query
  twenty times. A count tells you nothing; the queries name the unloaded relation. The next
  occurrence will explain itself. Left open until it does.

- [x] 4.5 **Browser tests are in: 5 tests over the three flows**, in `tests/Browser`. Dusk runs
  against its own database (`zue_dusk`, configured by an untracked `.env.dusk.local`) so it can
  never touch the working one.

  - `PrintLayoutTest` — the manifest renders `dir="rtl"` with its Arabic content, and a Latin
    employee number keeps its order inside the Arabic sheet. That last one is not theoretical:
    `2025-A3614` once printed as `A3614-2025`, and bidirectional text is only observable once
    rendered.
  - `ApprovalScreenTest` — the fill screen's date picker initialises, exactly **once**, with
    flatpickr available from the bundle rather than a CDN. The approval sheet renders clean.
  - `AppraisalScreenTest` — all four appraisal screens load with no severe console entry.

  The console-log helper lives on `DuskTestCase` and tolerates WebDriver refusing the log call,
  which it does occasionally; failing on a driver hiccup would have added a second flaky test to
  a suite already carrying one unexplained flake. Run three times over to confirm.

- [x] **Two bugs the browser found immediately, both invisible to a feature test.**

  The company logo was `<img src="../img/logo.svg">` — a *relative* path, so it resolved against
  whatever URL you were on. On `/time-sheets/create/9094` the browser asked for
  `/time-sheets/img/logo.svg` and got a 404. Every page below the root had a broken logo, and
  every one of them returned 200 to a feature test. Now `asset('img/logo.svg')`.

  The fill screen refuses a **super admin** who holds no scope policy — `Gate::before` grants the
  permission but the screen also asks whether the employee falls inside a scope the actor is
  named in. That is correct and deliberate, but it is worth writing down: being a super admin is
  not sufficient to open a time sheet.

## Phase 5 — Frontend

- [x] 5.1 **No view loads anything from another host any more.** Flatpickr and ApexCharts were
  both fetched from `cdn.jsdelivr.net` — the time sheet screens and the dashboard depended on
  someone else's uptime, and would be the first thing a content security policy broke. Both are
  npm dependencies in the build now. Verified in a browser: on a page that loads the bundle,
  `document.querySelectorAll('script[src]')` and `link[href]` return **nothing** off-origin.

  Bundling ApexCharts naively pushed `app.js` from 295 KB to **1,298 KB** on every page, for a
  chart that appears on one. It is a dynamic `import()` now, declared in the manifest as such,
  so the 953 KB chunk is fetched only when the chart's container is on the page — `app.js` is
  **345 KB**. The chart's inline script moved to `resources/js/dashboard-chart.js` and takes its
  data from a `data-` attribute, which is 5.3's shape applied to the one that mattered most.

  `FrontendAssetTest` keeps it that way: it walks every Blade file for a `script`/`link` loading
  from another host, allowing only the three destinations a person clicks. Mutation-checked.

  Removed along the way: the default Laravel `welcome.blade.php` (unrouted, and the last view
  pulling a webfont from `fonts.bunny.net`), a dead `tom-select` CDN link commented out in the
  layout, and `livewire/clinic-employee-info.blade copy.php` — 9.3 KB of duplicate that could
  never have been rendered, given the space in its name.

- [x] 5.2 **Every `<style>` block is out of the views, and inline styles are down 131 → 57.**

  The eleven blocks could not simply be merged: ten of them redefine bare `body`, `table`, `td`,
  `.card`, `.container` or `.header`, and they are only safe today because each is scoped to the
  page it sits on. So each became its own Vite entry, loaded where the block used to be —
  identical scoping, no cross-view leakage, and now inside the build.

  Verified rather than assumed. Before touching the manifest I pinned its *computed* geometry in
  a browser — A4 sheet width, `table-layout: fixed`, ruled cells, `direction: rtl` — moved the
  CSS, and re-ran: identical. The approval sheet is pinned the same way, down to the `#87ceeb`
  of a highlighted attendance cell. Screenshots confirm both still look right.

  Of the inline attributes, 74 were repeated markup: the actions column set `width: 134px` in
  **seventeen** index tables, and the appraisal sheet set column widths cell by cell. Those are
  classes now. The 57 that remain are genuinely one-off print dimensions — `height: 30mm` on a
  signature block — spread one or two to a view.

- [ ] **The last 57 inline styles, for a strict content security policy.** `style-src` refuses
  `style=` attributes as well as `<style>` blocks, so a strict policy needs all of them gone, or
  it needs `'unsafe-hashes'`. What is left is mechanical but low value per edit, and it is worth
  doing in one pass alongside phase 7's CSP work rather than piecemeal now.

- [x] **Three pieces of dead CSS found by moving it.** Sass refuses what browsers silently drop.
  `tbody tr th:nth-child(1, 2, 3)` is invalid — `nth-child` takes one argument — and the rule
  directly beneath it said the same thing correctly, so it had never applied. `tbody
  td:last-child()` likewise. Both were **deleted rather than corrected**: fixing them would add
  borders the printed sheet has never had, which is a decision, not tidying. And one style
  attribute still carried a placeholder, `A_CSS_ATTRIBUTE:all`, that was never a property.

  `reports/layout.blade.php` and `reports/printable.blade.php` turned out to have byte-identical
  CSS — the build deduplicates them into one file, which is how it surfaced.

- [x] 5.3 **Inline scripts 22 → 14, and the two that were actually wrong are gone.**

  The time sheet fill and time table screens each carried a script that re-initialised the *same*
  date picker Alpine had already set up, so what a user got was whichever ran last. Both removed;
  the options moved into the Blade prop Alpine reads, and a browser test asserts the picker is
  initialised exactly once.

  The appraisal period create and edit forms each had their own copy of "hide the quarter field
  when the type is yearly" — and had **already drifted**: only the edit form cleared the field, so
  creating a yearly period could submit a quarter left over from an earlier choice. One module
  now, with a browser test that selects a quarter, switches to yearly, and asserts the field is
  both hidden and empty.

  The 14 that remain are single-page behaviours with no duplication between them — a score
  subtotal, a popover initialiser. Moving them buys nothing on its own; it buys something only as
  part of the content security policy pass, alongside the last inline styles.

- [ ] **Found doing it: the date picker had a ceiling that was about to expire.** The inline
  script restricted the fill screen's picker to `from: "2024-01-01", to: '2026-10-10'` — a date
  literal. Past it, the picker refuses every day and attendance cannot be entered at all. Today
  is 2026-09-10, so it had **one month left**. The bound is computed from the current date now.

  Worth your attention: I kept the shape of the old rule — a month ahead of today — because that
  is what it did, not because anyone decided it. **How far ahead should staff be able to fill a
  timesheet?** If the answer is "not at all beyond today", or "to the end of the current month",
  say so and I will change it.

- [x] **`migrate:reset` was broken three migrations from the top, and now runs end to end.**
  Dusk's `DatabaseMigrations` rolls the schema back after each test, which is how this surfaced —
  nothing else in the project had ever attempted a full rollback. Three `down()` methods failed:

  1. `add_indexes_to_flight_pivot_tables` dropped a composite index that was the only one serving
     a foreign key, which MySQL refuses.
  2. `make_users_id_manual_and_sync_with_number` dropped `employees_user_id_foreign` by name —
     but the identity redesign's own `down()` restored the column *without* its foreign key, so
     the migration behind it had nothing to drop.
  3. `add_location_column_to_residences_table` dropped a column while its foreign key still
     referenced it.

  All **81 migrations now roll back and re-apply cleanly**, verified as a full round trip. This
  is a phase 7 prerequisite — "document and rehearse the rollback" is not something you want to
  discover on the night.

- [ ] 5.4 Code-split or replace the 1,656 KB editor bundle. It is already a separate Vite entry
  loaded only by the four clinic views, so it costs nothing elsewhere; the 1,688 KB is HugeRTE
  itself. Splitting it further is not really available — replacing it is a product decision.

- [ ] 5.5 **The i18n story needs your decision.** Measured rather than guessed:

  - 96 of 184 views already go through `@lang`/`__()`, against a single `lang/en/crud.php` of
    627 lines. That is the scaffolded chrome — buttons, table headings, "are you sure".
  - 38 views (20%) contain hard-coded Arabic — **286 lines of it**. It is concentrated in the
    documents: the appraisal sheet (68 lines), the appraisal review form (36), the injury report
    (28), the flight manifest (25), the time sheet approval sheet (16).
  - `config/app.php` sets locale and fallback to `en`, and no `ar` directory exists.

  So the application is not half-translated; it is two things at once. The **chrome is English and
  translatable**, and the **printed documents are Arabic and fixed** — because they reproduce
  forms the company already uses, where the wording is the form. Nobody would want
  `الجنسية` on the airport manifest to follow a locale switch.

  Three ways forward, and it is a question about who uses this system, not about code:

  1. **Leave it.** Declare the documents Arabic by definition and stop counting them as
     untranslated. Cheapest, and honest about what those pages are.
  2. **Translate the chrome to Arabic** — add `lang/ar`, set the locale, and give staff an Arabic
     interface end to end. Real work: 627 keys, plus RTL for every screen, not just the printed
     ones.
  3. **Make it switchable**, so an English-speaking contractor and an Arabic-speaking clerk each
     get their own. The most work by far, and only worth it if you actually have both.

  **My recommendation is (1) unless staff are asking for an Arabic interface**, in which case (2).
  I would not build (3) without someone actually needing it. Which is it?
- [x] No view references an external host — asserted by `FrontendAssetTest`, not assumed

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
| Testing | 7 | 8 | 9 | 311 → 417 tests, three characterization suites |
| MVC / layering | 7 | 8 | 8 | validation in form requests, logic out of controllers |
| Laravel practice | 7 | 8 | 8 | form requests, typed relations, an enum, generics |
| Code quality | 6 | 7 | 8 | return types everywhere, ~850 duplicated lines gone |
| DRY | 6 | 7 | 8 | `CrudController`, `ApprovalEligibility`, `ApprovalStep` |
| Frontend | 6 | 6 | 8 | untouched so far |
| SOLID | 5 | 7 | 8 | the 614-line service is four collaborators |
| Scalability | 5 | 5 | 8 | untouched so far |
| Operations | 5 | 5 | 8 | untouched so far |
| **Overall** | **6.5** | **7.0** | **8.3** | |

After phase 3 the data-model figures move too: **Database 7 → 8** (`employees` 87 → 21 columns,
`employees.number` unique, the profile split enforced by test) and **Performance 7 → 8**
(serialising employees 36 → 0 queries, `clinic.index` 26 → 7, budgets tightened rather than
raised). That puts the overall at **7.2**.

After phase 4, **Testing 8 → 9** (311 → 444 tests; all 16 policies tested directly rather than
only through HTTP; the role access matrix asserted; coverage measured at 58.43% rather than
guessed) and **Authorization 8 → 9** (the policy layer now has 706 assertions behind it,
including the negative cases). Overall **7.4**.

After phase 5, **Frontend 6 → 8** (no view reaches another host, every `<style>` block is in the
build, inline styles 131 → 57, `app.js` 345 KB with the chart chunk split out, the date picker
initialised once instead of twice) and **Security 8 → 9** (the CDN and inline-script blockers to
a content security policy are gone; 57 inline styles and 14 page scripts remain, best cleared in
one pass with phase 7's CSP work). **Operations 5 → 6** — `migrate:reset` works end to end, which
rollback rehearsal depends on. Overall **7.8**.

Still at baseline: **Scalability 5** — phase 6 has not started.
