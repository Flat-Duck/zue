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

- [ ] **The intermittent failure now has a name: `TimeSheetMutationServiceTest ::
  revise preserves employee id and records audit fields`.** Seen three times across roughly
  sixty full-suite runs, always alone, always passing on the next run and always passing in
  isolation. Ruled out so far: the permission cache (`array` store in tests), test ordering
  (twelve runs with `--order-by=random`, all clean), and the performance benchmarks (a separate
  suite). Knowing which test it is narrows it considerably — that test revises a time sheet onto
  a different day, and `time_sheets` carries a unique constraint on `(employee_id, day)` — but
  the failure output was not captured the one time it named itself, so the cause is still
  unproven. The next occurrence needs the full output kept.

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

- [x] 3.4 **Decided: keep both tables and keep `departments.code`.** The measurement stands —
  132 centres against 126 departments across 153 distinct pairings, so a centre does not
  determine a department and the two are not interchangeable. Owner's answer: the department code
  is a data necessity and stays. No schema change; the cost centre remains the budget line a
  department reports against.

  What is left is a data question rather than a modelling one: 1,115 staff sit under the
  codeless legacy departments imported from the dump, and the export departments carry codes.
  Reconciling those two generations belongs to the fresh import, not to a migration.

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

- [x] **Deferred by the owner: the four permissionless roles are test scaffolding.**
  `supervisor`, `fieldcoordinator`, `superintendent` and `campboss` hold no permissions because
  they were created to exercise the management scope model, not to be granted to anyone yet.
  `RoleAccessTest` asserts that as it stands, so the day someone is given one of them on its own
  and reaches nothing, the test says why. What each should reach gets settled when they are set
  up for real.

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

- [x] **The date picker's ceiling was about to expire; the horizon is now one month, by decision.**
  The inline script restricted the fill screen to `from: "2024-01-01", to: '2026-10-10'` — a date
  literal. Past it the picker refuses every day and attendance cannot be entered at all, and on
  2026-09-10 it had **one month left**.

  Owner's answer: one month ahead is right. That is what the derived bound gives, computed from
  the current date so it cannot expire again, and a test travels the clock forward to prove the
  bound moves with it.

- [x] 5.4 **Editor kept, and fixed.** Owner's answer was to keep it or find something smaller.
  Investigating turned up something better than a swap: `hugerte-init.js` imported **all 28
  plugins** the package ships, while the clinic screens configure 17 between them. Eleven were
  bundled that nothing asked for — emoticons and its emoji data, codesample and its syntax
  highlighter, template, accordion, autoresize, autosave, directionality, nonbreaking, pagebreak,
  quickbars, save, visualchars. Bundle **1,688 KB → 1,394 KB**, with no feature lost.

  Not swapped, deliberately. Trix cannot do alignment, background colour or headings, all of
  which the clinic toolbar uses. Quill can, but stores alignment as its own `ql-*` classes, so
  existing content would need migrating — and these fields hold diagnoses and prescriptions.
  Migrating clinical text to save a megabyte on four pages is a bad trade.

- [x] **The clinic editor's content stylesheet was 404ing, and only the browser found it.**
  HugeRTE fetches its skin and content CSS from a base URL unless told they are bundled. Nothing
  told it, so every clinic screen requested
  `/clinic/diagnosis//skins/ui/oxide/content.min.css` — a relative path resolved against the
  current URL, doubled slash and all — and got a 404. The editor worked; the text inside it was
  styled by browser defaults rather than by the editor, so what a doctor saw while typing was not
  what the record would look like. `skin_url` and `content_css` are `default` at all seven init
  sites now, and `ClinicEditorTest` fails on any failed request on that page.

- [x] 5.5 **Decided: two languages, Arabic and English. Both are complete.** Owner's answer
  overrode my recommendation to leave it, so the interface is genuinely bilingual rather than
  English-with-Arabic-documents.

  Done:

  - `config/locales.php` names both languages, each with its direction.
  - `SetLocale` middleware picks one: a choice the person made wins, then the browser's
    `Accept-Language`, then the default — so an Arabic browser gets Arabic without hunting for a
    switch.
  - A language picker in the navigation, each language in its own script: someone looking for
    Arabic is looking for العربية. It replaced Tabler's sponsor link, which had no business in a
    private HR system.
  - **`lang/ar/crud.php`: all 217 keys translated**, none left in English, asserted by a test
    comparing the two files key for key. Field names follow the wording of the company's own
    personnel export, so a clerk reads the same word on screen as on the form they type from.
  - Right-to-left is a real layout, not an attribute: Tabler ships a mirrored stylesheet, so
    `app-rtl.scss` is a second entry and the layout serves whichever matches. Shared application
    styles live in one partial both import.
  - Nine tests, one a browser test that switches to Arabic and checks the page actually lays out
    right to left, on the mirrored stylesheet, with no console errors.

  **Now done: every view.** 796 keys across twelve catalogues, both languages in sync. The whole
  interface — every screen, every menu, every confirmation dialog — reads from a lang file.

  - Twelve catalogues: `nav`, `ui`, `auth`, `crud`, `notifications`, `appraisals`, `clinic`,
    `flights`, `maintenance`, `operations`, `reports`, `timesheets`.
  - **All 35 sidebar labels**, the user menu, and the footer. The footer was Tabler's own —
    their documentation link, a sponsor button, "Copyright © 2023 Tabler", and links to
    `./license.html` and `./changelog.html`, neither of which exists here.
  - Confirmation dialogs too. They lived in `onsubmit="return confirm('…')"`, so they stayed
    English no matter the interface. They now read `confirm({{ Js::from(__('…')) }})` — `Js::from`
    escapes for JavaScript *and* for the attribute, which `addslashes` did not: the one dialog
    that interpolated a person's name broke on any name containing an apostrophe.
  - Views written in Arabic got a hand-written key and a real English translation rather than a
    slug of the Arabic, which would have produced `text_7` and left Arabic in the English file.
  - Three strings were rewritten while being translated: two were colloquial (`مافيش تقييمات`)
    and one exposed a column name to the user (`لازم الموظف يكون مربوط بـ appraisal_form_id`).

  **`TranslationCoverageTest` locks it in.** Four assertions: no view carries a readable label, no
  confirmation dialog is hard-coded, the two catalogues carry identical keys, and no Arabic value
  is a copy of its English one. It strips Blade before looking, so it sees what a reader sees, and
  it is mutation-checked both ways — putting back an English label fails it, and so does putting
  back an Arabic one.

  **The printed documents, and a decision I made rather than guessed at.** The airport manifest is
  a facsimile of the paper form checked against at the gate: right-to-left, Arabic, company name
  in Latin capitals. Making it follow the interface language would mean the sheet changes language
  depending on who pressed print — `FlightManifestPrintTest` caught exactly that. Its labels are
  now pinned with `__('…', [], 'ar')`, so the strings live in the catalogue but the paper does not
  move. The report signature blocks keep the side-by-side wording they already had
  (`حافظ الوقت / Timekeeper`).

  **Still your call:** two other printed forms now *follow the interface language* rather than
  staying Arabic — `appraisals/official/show.blade.php` (the signed appraisal form) and the
  printed half of `time_sheets/approve.blade.php` (the monthly control sheet). Your decision 5.5
  said reports should be bilingual, so that is what I built; but if either of those is a facsimile
  of a paper original the way the manifest is, say so and I will pin it the same way — the strings
  are already in the catalogue, so it is a one-line change per label.

- [x] **Four views were dead, and one of the live ones was showing Tabler's demo data as real
  records.** `employees/directory.blade.php` is routed and rendered every employee in the company
  with the initials `SA` and the job title `Nuclear Power Engineer`, with mail and phone buttons
  pointing at `#`. It now shows each person's own job title, initials derived from their name, and
  `mailto:`/`tel:` links that disable themselves when there is nothing to link to.
  `EmployeeDirectoryTest` covers it, including the Arabic-name fallback.

  The four dead ones were left in place and excluded rather than translated, since a string nobody
  can see does not belong in the catalogue: `time_sheets/print.blade.php` (a prototype that queries
  the database from the template with a hard-coded month, and whose controller action renders
  `approve` instead), `components/print-header.blade.php` with its `PrintHeader` class,
  `components/inputs/radio.blade.php` (a checkbox with a stray Excel radio group inside it), and
  `livewire/time-sheeter.blade.php`, referenced only from commented-out Blade. **Say the word and
  I will delete them.**

- [x] **Defects found and fixed while reading every view.** None of these were translation work;
  they were only visible because translating meant reading all 185 views.

  - `December` was spelled `Decembe` in both month selectors.
  - `Available` was spelled `Avalible` on the rooms screen, `New Appointment` was `New Ppointment`
    in the clinic.
  - `auth/passwords/confirm.blade.php` contained a `<# … #>` block — neither Blade nor HTML, so
    both markers rendered on the page — wrapping Tabler's demo avatar and the author's name.
  - The login page carried Tabler's GitHub and Twitter sign-in buttons, both pointing at `#`.
  - Every page's `<title>` was the literal string `zue`, and the logo's `alt` text was too. The
    title is now `@yield('title', config('app.name'))`.
  - Three views built a badge as a PHP string inside `{!! … !!}`, which is why the substitution
    landed inside the expression and broke them — rewritten as ordinary `@if`/`@else` markup.

- [x] **The stylesheet was still fetching a Google font, and my earlier claim was too broad.**
  I said no view loads from another host. True of Blade, not of the build: `app.scss` opened with
  `@import url('https://fonts.googleapis.com/css?family=Nunito')`, so every page fetched Nunito —
  a font **nothing uses**, since Tabler resolves its own `--tblr-body-font-family` and the Sass
  variable naming Nunito sits in a file nothing imports. Removed. `FrontendAssetTest` now scans
  the built CSS and JS for fetch-shaped references — `@import`, `url()`, dynamic `import()`,
  `fetch()` — not just views, and is mutation-checked by putting the font import back.

- [x] No view references an external host — asserted by `FrontendAssetTest`, not assumed

## Interface work — requested outside the roadmap

- [x] **Dark mode works.** Tabler keeps its theme switcher in a module of its own and the
  application never imported it, so the two toggle links set a `?theme=` parameter that nothing
  read. Importing it is the fix. On top of that the choice is now remembered in the session and
  the attribute is rendered by the **server**, so a fresh page is already the right colour rather
  than flashing light while a deferred script catches up — and it needs no inline script, which
  matters for phase 7's content security policy. The links also keep the current query string, so
  switching theme on a filtered list no longer throws the filter away.

  `DarkModeTest` proves it in a browser by reading the computed background colour, not by
  trusting the attribute. It sets an explicit starting theme first: Tabler's default is `auto`,
  and the headless browser asks for dark, so a test that assumed light would pass or fail
  depending on the machine.

- [x] **A malformed `viewport` meta tag** in both layouts —
  `content="width=device-width, initial-scale=1" viewport-fit=cover"` — a stray quote that made
  the browser read the attribute as `viewport-fit="cover""`. Fixed.

- [x] **Broadcasting: Laravel Reverb, Echo, and a notification bell that is actually live.**

  Reverb rather than Pusher or Ably: it is Laravel's own server, runs beside the application, and
  nothing leaves the network. Employee data on an internal HR system has no business travelling
  to a third party to be echoed back.

  - `config/broadcasting.php` gains a `reverb` connection; `BroadcastServiceProvider` was
    commented out in `config/app.php` and is enabled.
  - `resources/js/echo.js` configures Echo, and **degrades rather than throwing** when no key is
    configured — a single office with no queue worker needs none of this and should not get a
    broken page for it.
  - The transport follows the *page* protocol rather than the configured scheme: a browser will
    not open an insecure socket from a secure page. `REVERB_SCHEME` describes how the server
    listens, and behind a TLS proxy the two differ.
  - `TimeSheetApproved` is written to the database *and* broadcast, with both channels built from
    one payload — a notification that reads differently depending on how it arrived is a bug that
    gets reported as a mystery. The message follows the interface language.
  - The bell was Tabler's demo markup with "Example 1" hardcoded. It is a Livewire component now,
    on Tabler's `dropdown-menu-card` with a flush hoverable list group and animated status dots,
    subscribed to the signed-in user's private channel.

- [x] **`@livewireScripts` loaded before the bundle, so Echo was never found.** Livewire looks for
  `window.Echo` as it boots and warns "Laravel Echo cannot be found" if it is missing — a
  `console.warn`, not an error, so the browser-error assertions never saw it. The component
  subscribed to nothing. The bundle loads first now.

- [x] **Channel authorization was proving nothing.** `/broadcasting/auth` answers **200 with an
  empty body for anybody** under the `null` broadcaster, which is what `.env.testing` fell back
  to — so a test asserting who may subscribe would have passed no matter what. `phpunit.xml` now
  configures a real broadcaster, and the tests assert 403 for another user's channel and for a
  guest.

- [x] **The whole chain is proven end to end in a browser.** `LiveNotificationTest` sits on an
  unrelated page, has the *server* send a notification, and waits for the badge to appear:
  notification → broadcast → Reverb → WebSocket → channel authorization → Livewire → DOM, with
  nothing polling. It skips with a clear message when no socket server is running, and CI starts
  one so it actually runs there.


## Phase 6 — Quality gates

- [x] 6.1 **Baseline 212 → 113 at level 5**, all through fixes rather than deletions: relation
  generics, `@property` blocks, model return types, and the trait rewrite below.

- [x] 6.2 **PHPStan is at level 6.** Raising it surfaced **712** findings; **451 were fixed** and
  the remaining 261 baselined. A new violation is caught immediately — verified by dropping an
  untyped method in and watching it fail.

  Note the two baseline numbers are not comparable: 113 is the level-5 figure, 368 is the level-6
  one. The debt did not grow; more of it is now being looked for, and it is enumerated rather
  than unreported. Level 7 is the next step and has not been attempted.

  What the 451 actually were: 77 untyped Eloquent relations, 30 form request rule sets, 48
  controller return types (with the 11 missing imports that came with them), and the trait below.

- [x] **`Searchable` defined two scopes that could not work.** `scopeWithArchived()` and
  `scopeWithoutArchived()` filtered on `archived_at` — a column only `Employee` has. On the other
  **fourteen** models using the trait, calling either raised `Unknown column 'archived_at'`;
  confirmed by running it. On `Employee` they never ran at all: `SoftArchivingScope` registers
  builder macros of the same names, and a macro takes precedence over a local scope. So one
  definition was dead and the other was a crash waiting for a caller. Both removed, with
  `SearchableScopeTest` holding the line. `scopeSearchLatestPaginated()` went too — it declared
  `: Builder` and returned a paginator, and nothing called it.

- [x] 6.3 **Balance recalculation moved into a `TimeSheetObserver`.** It was dispatched by hand
  from four places in one service, so any write that did not go through that service left the
  balance quietly wrong.

  Measuring it first turned up something worse: **filling a month dispatched thirty identical
  recalculations** — one per day, each a full pass over the employee's attendance history, and
  with `QUEUE_CONNECTION=sync` every one of them inline. The batch paths now suppress the
  observer and recalculate once. Asserted, both the count and that the balance is still right.

  Audit logging was left where it is, deliberately. Those calls record intents —
  `impersonation.started`, `database.restored`, `user.roles_changed` — and an observer watching a
  model cannot know which of those it is looking at.

- [x] 6.4 **CI runs on every push and pull request.** Three jobs: formatting and static analysis;
  the suite on PHP 8.3 and 8.4 against a MySQL service; and the browser tests, which build the
  front end, serve the app and upload failure screenshots.

  There was already a `tests.yml` — the Laravel skeleton's. It tested PHP 8.1 and 8.2 against a
  `^8.3` requirement, installed SQLite extensions for a MySQL application, never ran a migration,
  and **had never once executed**: the repository shows zero workflow runs, ever. Three more
  workflows called `laravel/.github` reusable jobs for the framework repository's own issue
  triage. All four removed.

  CI also **rehearses the rollback** — `migrate:reset` then `migrate` — because three `down()`
  methods were broken until this week and nothing would have noticed until a release needed
  backing out.

- [x] **The formatter now passes on the whole project.** `pint --test` was red on 96 files: the
  project had only ever been formatted with `--dirty`. A gate that cannot go green is not a gate,
  so the whole project was formatted once. No behaviour change — suite and analysis both clean
  either side of it.

- [~] A red gate blocks a merge without anyone remembering to look — **the workflow is written
  and every step was dry-run locally, but it has not yet run on GitHub.** Branch protection also
  has to be switched on for a red gate to actually block a merge; that is a repository setting,
  and yours to make.

- [x] **Decided: a part-day of leave settles to the nearest whole day, half away from zero.**
  Owner's rule: 3.4 is 3, 3.5 is 4, 3.9 is 4. Implemented in one place —
  `TimeSheetBuilder::roundToWholeDays()` — and applied by all three ways of asking for a balance,
  so the running figure, the to-a-date figure and the bulk report figure can no longer disagree
  about the same person. Seven cases pinned by a data provider, including the negative side:
  a debt of half a day is a whole day owed.

  A correction to what I said earlier: I described the report and the record as disagreeing. In
  live code they do not — the balance report reads `employees.total_balance`, the stored integer.
  The disagreement was between `calculateBalance()` and what got stored, and between the helper
  functions themselves, neither of which had a production caller. The rule now applies to all of
  them either way.

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

After phase 6, **Code quality 7 → 8** (PHPStan at level 6, 451 findings fixed, the whole project
formatted, two dead scopes removed) and **Operations 6 → 7** (CI runs formatting, analysis, the
suite on two PHP versions, a rollback rehearsal and the browser tests — where before, nothing had
ever run). **Performance 8 → 9**: filling a month went from thirty balance recalculations to one.
Overall **8.1**.

After the owner's decisions landed, **Frontend 8 → 9** (the interface is bilingual with a real
right-to-left layout, the editor bundle is 294 KB lighter and its content stylesheet finally
loads, and the last external font request is gone). Overall **8.2**.

With dark mode working, a bilingual right-to-left interface and a real broadcasting foundation,
**Frontend 9 → 9** holds and **Scalability 5 → 6**: the application can now push to a browser
instead of being polled, which is what a queue worker and a field site need. Overall **8.3**.

Still short of target: **Scalability** — phase 7 is where load and Octane are decided.
