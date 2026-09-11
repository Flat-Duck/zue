> Historical audit snapshot. See FIX_PLAN_CHECKLIST.md for current status; findings require revalidation against current code.

# Laravel application audit — 8 September 2026

## 1. Executive summary

**The application has a workable Laravel foundation, but is not ready for broader exposure or increased load without targeted security and integrity fixes.** Preserve its conventional Laravel structure. The highest-value work is authorization enforcement, reliable identity and approval semantics, bounded reports, and eliminating repeated database work—not a rewrite or a repository layer.

Three urgent findings dominate: public registration leads to an authenticated account that can access SQL maintenance operations; Livewire timesheet and clinic actions do not enforce the controller's access boundaries; appraisal administration/finalization and the manager-signature branch lack effective authorization. These are code-proven access-control defects. No destructive exploit was executed.

Performance concerns are material: the configured local MySQL database contains **1,245,598 timesheets**, **1,115 employees**, and **1,090 active employees**. Employee relationship access grew from 4 queries for one employee to 61 for twenty. Employee attendance appends add two queries per employee. V2 authorization repeatedly resolves users/roles and dependencies, while an unfiltered report can hydrate the entire timesheet table.

The test suite contains useful V2 authorization tests, but legacy factories and deployment caches undermine confidence. Five targeted authorization tests passed; seven employee-controller tests errored. Dependency scanners reported affected versions, including spreadsheet parsers on upload paths. These findings do not establish that every advisory is exploitable in this application.

### Health scores

These are qualitative audit assessments, not benchmark measurements or calculated coverage percentages.

| Area | Score | Reason |
| --- | ---: | --- |
| Architecture | 6/10 | Conventional Laravel organization; useful services; competing authorization models |
| Laravel practices | 5/10 | Policies, Form Requests, Eloquent, queues present; action enforcement inconsistent |
| Performance | 4/10 | Measured N+1 growth, repeated scope work, unbounded reporting |
| Database | 5/10 | Many useful FKs/indexes; missing business uniqueness and unsafe delete semantics |
| Security | 2/10 | Critical authorization gaps and public account provisioning |
| Code quality | 5/10 | Understandable CRUD; drift and incomplete business workflows |
| Testing | 3/10 | Useful focused tests; broken legacy fixtures and major coverage gaps |
| Scalability | 3/10 | Synchronous jobs, report memory risk, approval query amplification |

### Scope and evidence limits

Application code, route files, models/scopes, services, controllers, Livewire, Blade, requests, resources, policies, migrations, factories/seeders, jobs, commands, configuration, assets, and tests were mapped and searched systematically, with detailed tracing of the principal workflows. This is not a claim that every possible execution path has been tested.

Laravel Boost's connected tool surface was unavailable. Its installed `boost:execute-tool` executor was used for ApplicationInfo, ListArtisanCommands, SearchDocs, DatabaseQuery, Tinker, and an attempted ListRoutes call. Database access was read-only; Tinker probes returned counts/configuration, not personnel records. ListRoutes failed because the loaded cache references a removed Livewire controller; a read-only router inspection established the route counts instead.

No application source, dependencies, migrations, or business records were changed. This report is the deliverable. Existing changes to Composer files and setup files predated the audit and were preserved. Test execution used an isolated in-memory SQLite database; normal runtime logging/view-cache side effects may occur. Production infrastructure, HTTP latency distributions, production EXPLAIN plans, queue supervision, restore drills, and browser behavior were not independently verified.

## 2. Application map

### Stack and inventory

- Runtime: PHP **8.4.24**; Composer declares `^8.1`. Installed Laravel **10.49.1**, Livewire **3.6.4**, Sanctum **3.3.3**, Spatie Permission **5.11.1**, PHPUnit **10.5.58**, Pint **1.25.1**, Boost **1.8.5**.
- Other direct production packages: Laravel UI, Guzzle, Dompdf wrapper, Intervention Image, Laravel Excel/PhpSpreadsheet, PHPWord, TinyMCE.
- Frontend: Blade, Livewire/Alpine, Vite 4 constraint, Tabler, Bootstrap 4 dependency, HugeRTE, Tom Select, signature pad; additional CDN assets.
- Inventory: 237 PHP files under `app`; 66 controllers including 26 API controllers and 8 appraisal controllers; 33 Form Requests; 26 resource/collection classes; 14 policies; 64 PHP migrations; 14 factories; 47 test/support files with approximately 185 `test_`/`it_` methods. The 40 files under `app/Models` include scope/trait files, not just models.
- Loaded routes: **237 including vendor routes; zero `api/*` routes**. API declarations in [routes/api.php](/Applications/ServBay/www/zue/routes/api.php) are commented out. API findings below concern dormant code unless explicitly stated otherwise.
- No application repository layer, custom notification classes, custom mail classes, observers, or custom event/listener directories were found. The standard registration listener is configured. Their absence is not itself a deficiency.

### Domain flow

```text
Browser → web middleware (session, CSRF, bindings) → auth
        → CRUD controller → policy + Form Request → Eloquent → Blade/redirect
        → timesheet controller → TimeSheetService / TimeSheetAuthorizationService
                              → ActorResolver / ScopeResolver / WorkflowResolver
                              → employees, policies, steps, time_sheets → Blade
        → Livewire action → direct helper/model calls → database → component render
        → appraisal controller → scoring/finalization/aggregation services → database
        → maintenance controller → BackupService or PerformBackupJob
Scheduler → period status sync + optional database backup
```

Simple CRUD queries in controllers are appropriate here. Services are justified for approvals, scoring, backups, and image processing. Livewire legitimately bypasses controllers, but it must enforce equivalent authorization and validation. The direct `/rr` import closure is an unjustified exception. Approval-display code also initializes database rows, so some apparent reads perform writes.

### Local runtime configuration

Boost reports `APP_ENV=local`, debug enabled, file cache/session stores, **sync queue**, V2 reads and writes enabled, configuration uncached, and routes cached. These describe this local checkout, not production. CLI OPcache was not loaded; that does not establish PHP-FPM's OPcache configuration.

## 3. Top 20 improvements

Effort: S = narrow change, M = several related paths/tests, L = staged migration or broader workflow work. IDs refer to the detailed findings.

| Priority | Problem | Location | Impact | Effort | Recommendation |
| --- | --- | --- | --- | --- | --- |
| P0 | Public account creation plus unrestricted database maintenance (F01) | RegisterController, MaintenanceController, web routes | Database disclosure/destruction | S–M | Restrict maintenance to a dedicated administrative ability; close or verify provisioning |
| P0 | Livewire timesheet/clinic authorization bypasses (F02) | TimeTable, TimeSheetApprove, ClinicEmployeeInfo | Unauthorized records and approvals | M | Authorize every action and target; validate all mutable inputs |
| P0 | Appraisal administration/signature authorization missing (F03) | Appraisal controllers | Forged official approvals and altered reviews | M | Enforce policies, hierarchy, ownership, lifecycle rules |
| P1 | Auxiliary web actions bypass permissions (F04) | Operations, Reports, UsersImport, flights.approve, `/rr` | Bulk changes/data disclosure | M | Explicit abilities and scoped targets on each route/action |
| P1 | Timesheet capabilities/target reassignment not enforced (F05) | TimeSheetController, requests, policy | Cross-scope changes | M | Distinguish fill/revise/view; check validated destination employee |
| P1 | Incompatible route and compiled-view caches (F06) | bootstrap/cache, compiled Blade | Broken Livewire and deployments | S | Rebuild caches with installed packages in a controlled deployment |
| P1 | Affected dependency versions and unsupported framework (F07) | Composer/npm lockfiles | Security/compatibility exposure | L | Patch reachable dependencies; stage a supported Laravel upgrade |
| P1 | Daily timesheet uniqueness/composite access path missing (F08) | time_sheets schema | Races and expensive lookups | M | Unique employee/day plus range predicates after data checks |
| P1 | V2 role/dependency N+1 and duplicate resolution (F09) | Authorization services | Hundreds of avoidable queries | M | Batch users/roles/steps; reuse request-local results |
| P1 | List/accessor N+1 (F10) | Employee, Room, CRUD views | Slow lists and serialization | S–M | Targeted eager loading and aggregate counts |
| P1 | Editing legacy scopes does not update active V2 policies (F11) | ManagementScopeService, ScopeResolver | Grants/revocations do not take effect consistently | M–L | Define one authoritative policy write path |
| P1 | Identity mismatch, mutable user PKs, cascading deletions (F12) | User, Employee, TimeSheet, FKs | Wrong actor/signature or lost HR history | L | Canonical identity mapping; stable keys; retention-safe constraints |
| P1 | Approval writes not atomic/concurrency safe (F13) | TimeSheetAuthorizationService, WorkflowResolver | Divergent approval records | M | Transactions, conditional updates/locks, retry-safe step creation |
| P1 | Backup timeout exceeds queue retry interval (F14) | PerformBackupJob, queue config | Duplicate/overlapping backups | S–M | Dedicated queue; retry interval above timeout; overlap protection |
| P1 | Backup correctness and restore guarantees weak (F15) | BackupService, MaintenanceController | Incomplete backups reported successful | M–L | Snapshot-consistent dump, verified restore, fail on partial export |
| P1 | Reports hydrate unrestricted timesheet history (F16) | ReportController::timesheets | Worker memory exhaustion | M | Required bounded range; streaming/queued export |
| P1 | Balance calculations and update paths diverge (F17) | TimeTable, CRUD, TimeSheetBuilder | Stale/invalid balances | M | Shared mutation service, validated schedule, consistent recomputation |
| P1 | Appraisal score/finalization lifecycle inconsistent (F18) | Score/Finalize/Aggregation services | Incorrect or overwritten official results | M–L | Required-item validation, immutable versions, transaction/idempotency tests |
| P1 | Test fixtures and coverage do not protect key workflows (F19) | UserFactory, EmployeeFactory, tests | Regressions undetected | M | Repair factories; add denial, concurrency, query-budget tests |
| P2 | Repeated dashboard scans and broad wildcard search (F20) | DashboardChart, HomeController, Searchable | Slow or misleading dashboards/search | S–M | Consolidate aggregates; align populations; restrict searchable fields |

## 4. Detailed findings

### F01 — P0 · Security · Public provisioning and database maintenance

**Files/classes/methods:** [routes/web.php](/Applications/ServBay/www/zue/routes/web.php) (`Auth::routes`, maintenance routes); `Auth/RegisterController::validator/create`; `MaintenanceController::__construct/export/import/restore/download/delete/updateSettings`.

**Problem/evidence:** Registration is enabled in both source and loaded routes. A caller chooses an unused positive employee number without verification of employment or invitation. Maintenance has only `web, auth`; its constructor adds no administrative authorization. `import()` executes uploaded SQL using `DB::unprepared`; export/download disclose backups; restore and delete can alter or remove data. CSRF protects POSTs but does not authorize an authenticated registrant.

**Why it matters/impact:** An ordinary account can reach database-wide operations. This is a concrete critical access-control failure, not a hypothetical raw-SQL style concern.

**Fix:** Dedicated maintenance policy/gate on every action, admin-only provisioning or verified invitations, explicit backup-table allowlist, and reauthentication for restore/import. Preserve the legitimate administrator workflow. **Risk:** Medium: approved operator roles must be defined and tested. Do not test this by importing SQL into the existing database.

### F02 — P0 · Security · Livewire action boundaries missing

**Files/classes/methods:** `Livewire/TimeTable::save/destroy/employee`; `Livewire/TimeSheetApprove::approveAsTimekeeper/approveAsSupervisor/approveAsSuperintendent`; `Livewire/ClinicEmployeeInfo::mount/load_apointment/save_apointment/new_apointment/employee`.

**Evidence:** TimeTable only checks that range/value are non-null, and its employee event accepts a target without a scope check. Destroy directly deletes dates. Approval component trusts public `employee_id` and performs bulk updates without roles, dependencies, or month bounds. Clinic loads any supplied appointment ID and reads diagnosis/prescription without checking clinic ability or ownership; write actions likewise lack validation/authorization.

**Impact:** Payroll/attendance integrity and medical confidentiality. Typed Livewire model properties protect some direct ID tampering, but do not authorize public actions/event parameters. Controller checks on the initial page do not replace action checks.

**Fix:** Apply capabilities and scoped lookup at each action, validate date ranges/value/overtime and clinic content, reject unrelated appointment IDs. Remove or route legacy approval actions through the canonical service after checking reachability. **Risk:** Medium; add Livewire forged-argument and revoked-access tests. The loaded stale route cache currently impedes Livewire execution; that is not a security control and does not eliminate this defect after deployment repair. The TimeSheetApprove class lacks a discovered active Blade mount, so its immediate exposure is lower than TimeTable/ClinicEmployeeInfo.

### F03 — P0 · Security · Appraisal approvals and administration insufficiently protected

**Files/classes/methods:** `Appraisals/AppraisalOfficialController::finalize/approve/show/index`; form/item/version/period controllers; `EmployeeAppraisalFormController::update`; `AppraisalReviewController::store`.

**Evidence:** The appraisal group has only `auth`. Most administration actions have no policies. In manager approval, the failed role check has an empty body and execution still sets `manager_user_id` and `manager_signed_at`. Finalization assumes permission without checking it. Review creation validates an employee's existence, not authority over that employee. Employee acknowledgement checks `$user->employee_id`, unlike the application's actual mapping.

**Impact:** Unapproved users can change evaluation rules and create/finalize/sign records. HR approval does contain an explicit role denial; preserve it while making the checks consistent.

**Fix:** Separate configuration, review, finalize, and sign abilities; enforce assigned manager/employee identity and period state server-side. **Risk:** Medium; establish intended HR roles and whether multiple managers are allowed before changing aggregation semantics.

### F04 — P1 · Security · Auxiliary actions escape normal CRUD authorization

**Files/classes/methods:** `OperationsController` archive/unarchive methods; `ReportController` reports; `UserController::import`; `EmployeeController::importArchivedEmployees`; `FlightController::approve`; [routes/web.php](/Applications/ServBay/www/zue/routes/web.php) `/rr`; `OccupationalInjuryReportController` CRUD and `ClinicApointmentController` reads.

**Evidence:** Most have only authentication. Employee archive import checks `view-any` despite mutating employees. User import has no create/import authorization. `/rr` has neither auth nor file validation and imports rooms before attempting to render undefined variables. Injury report Form Requests authorize unconditionally and their controller supplies no replacement check.

**Impact:** Medical/HR disclosure and bulk modifications outside normal permissions; anonymous room import. **Fix:** Explicit policy abilities and organization/employee scopes, restrictive upload validation, remove the abandoned `/rr` route or replace it with an authorized import endpoint. **Risk:** Low–medium; do not assume all authenticated users should have access because navigation hides the links.

### F05 — P1 · Authorization/data integrity · Visibility is treated as write permission

**Files/classes/methods:** `TimeSheetController::create/edit/update/show/ensureManageableForTimeSheet`; `TimeSheetUpdateRequest::rules`; `TimeSheetPolicy::view`.

**Evidence:** V2's visible access map includes actors with any of `can_fill`, `can_approve`, or `can_revise`. The controller checks visibility, not the required capability. Update checks the original employee, then accepts a different validated `employee_id`. The caller can also supply `user_id`, `old_value`, and `revised_at`. Show uses a generic view permission without management scope.

**Impact:** Approval-only visibility can become fill access; a permitted source record can be moved outside scope; audit attribution can be falsified. **Fix:** Action-specific capabilities, immutable employee assignment unless explicitly authorized, actor/revision metadata generated server-side. **Risk:** Medium; test original and destination ownership and all capability combinations.

### F06 — P1 · Deployment · Stale framework-generated caches

**Files/classes/methods:** [bootstrap/cache/routes-v7.php](/Applications/ServBay/www/zue/bootstrap/cache/routes-v7.php); compiled Blade under `storage/framework/views`; deployment process.

**Evidence:** Cached routes reference `Livewire\Controllers\HttpConnectionHandler`; installed Livewire 3 does not provide it. Boost ListRoutes fails with that missing class. A controller test hit `LivewireManager::styles()` from stale compiled output. Source uses normal `@livewireStyles`; do not rewrite the directive to work around a stale cache.

**Impact:** Broken reactive routes/rendering and misleading inspection results. **Fix:** Controlled rebuilding of route and view caches with the deployed vendor tree; clean isolated compile/cache paths for tests; smoke-test login and Livewire after deployment. **Risk:** Low if performed as a coordinated deployment; caches were not cleared during this audit.

### F07 — P1 · Dependencies · Unsupported framework and reported vulnerabilities

**Files:** `composer.json`, `composer.lock`, `package.json`, `package-lock.json`.

**Evidence:** Composer audit returned 61 advisory entries across 17 packages, including duplicate feeds for one Laravel advisory; npm reported 14 affected dependency nodes. See dependency section for interpretation. Laravel 10 security support ended 4 February 2025. PHP 8.4 passes installed Composer constraints, but Laravel 10's published support matrix lists PHP 8.1–8.3.

**Impact:** Ongoing security fixes may require a framework upgrade; upload parsers deserve immediate triage. **Fix:** Patch compatible affected versions first, verify active call paths, and plan a supported Laravel upgrade with the repaired tests. **Risk:** High for blanket upgrades; do not jump all packages to scanner-reported latest majors.

### F08 — P1 · Database · No unique employee/day timesheet invariant

**Files/classes/methods:** [database/migrations/2023_11_16_000014_create_time_sheets_table.php](/Applications/ServBay/www/zue/database/migrations/2023_11_16_000014_create_time_sheets_table.php); `TimeSheetBuilder::create`; `TimeSheetController::store/update`.

**Evidence:** Live schema has separate employee, day, and value indexes; no `(employee_id, day)` unique index. `firstOrCreate` and plain `create` both exist. Local duplicate-pair count is zero.

**Impact:** Concurrent submissions can create duplicates; reports/counts/balances then disagree. **Fix:** Recheck production duplicates, establish one entry per day as the invariant, add unique `(employee_id, day)`, and handle duplicate attempts deterministically. Use half-open date ranges. **Risk:** Medium: index creation on 1.25M rows requires an operational plan and confirmed business semantics.

### F09 — P1 · Query performance · V2 authorization amplification

**Files/classes/methods:** `TimeSheetAuthorizationService::groupedManagedEmployees/buildApprovalData/approvalStages/approve`; `WorkflowResolver::hasRole/ensureMonthlyStepsForEmployees/dependencyIsSatisfied`; `ScopeResolver::resolveEmployeeAccessMap`.

**Evidence:** Grouping calls role resolution in both `filter` and `reject`. Each resolution re-fetches a user and may fetch roles. A 20-employee sample incurred 40 queries for the two passes even where user lookups were misses. Workflow matching repeats the same lookup for role-bearing flows. Dependencies query a flow step already loaded by the caller, then query its predecessor. Display calls step initialization twice and rebuilds access maps repeatedly.

**Impact:** Query volume scales with employees × matching rules/steps before pagination can help. **Fix:** Resolve identity/roles once per employee batch; partition once; index preloaded steps by employee/month/order; reuse scope and flow results within the request. **Risk:** Medium: preserve specificity, priority, exclusion, and dependency rules; never cache authorization globally without invalidation.

### F10 — P1 · Eloquent · Hidden list and accessor queries

**Files/classes/methods:** `EmployeeController::index/dir`; Employee relationship/name/count accessors; `RoomController::index`, `Room::getAvailableAttribute`; Department/User/ManagementScope list views.

**Evidence:** Dedicated N+1 report below traces each collection to its relationship access. Employee counters call relationship builders, so adding `with('timeSheets')` would not eliminate those count queries.

**Impact:** Measured linear query growth; dormant API serialization also exposes unrelated fields and invokes appends. **Fix:** Minimal eager-loading graph and `withCount`/grouped aggregates, with endpoint-specific serialization. **Risk:** Low–medium: preserve active/archive filtering and response contracts; avoid loading all timesheets merely to count them.

### F11 — P1 · Architecture/authorization · Two policy stores drift

**Files/classes/methods:** `ManagementScopeController::store/update/destroy`; `ManagementScopeService::createScopes/updateScope`; `ScopeResolver::resolveEmployeeAccessMap`; `Console/Commands/ImportManagementScopesToV2`.

**Evidence:** UI CRUD writes `management_scopes` and its manager pivot. Enabled V2 reads `scope_policies/scope_policy_actors`. The conversion is a separate command; no automatic synchronization was found in CRUD, observers, or services.

**Impact:** An administrator can change/revoke legacy access while active V2 authorization continues using old assignments. **Fix:** Choose one authoritative editable store and migrate the UI or implement a well-tested transitional synchronization path. **Risk:** High: compare grants and revocations before cutover; do not rerun an import blindly or remove legacy behavior without parity checks.

### F12 — P1 · Identity/retention · Inconsistent keys and destructive cascades

**Files/classes/methods:** `User::employee/booted`; `Employee::user`; `ActorResolver`; TimeSheet signer relationships; user update/delete controller; user-ID and foreign-key migrations.

**Evidence:** User PK equals editable `number`; `User::employee` joins employee ID to user ID, whereas ActorResolver first uses `employees.user_id`. Timesheet signer FKs reference employees, but Eloquent signer relationships point to users. V2 writes actor employee IDs; legacy writes auth user IDs. Mutable user PKs do not automatically update Spatie polymorphic role links or Sanctum token ownership. Live FKs cascade user deletion to employees and authored timesheets, and employee deletion to timesheets; deleting location/department/center can also cascade into employees. Restrictive signer FKs may block some deletions rather than make them safe.

**Impact:** Wrong identities/signatures, lost roles, misattributed history, or irreversible loss of HR records. Also, `ActorResolver::resolveEmployeeByUserId` passes a failed user lookup as null to a method that defaults null to the current authenticated user; an explicit invalid user ID should fail closed rather than inherit the current actor. **Fix:** Stable user primary keys and explicit canonical employee linkage; align signer FKs/models; use retention-safe restrict/set-null/deactivation semantics where required. **Risk:** High; migrate IDs and dependent data incrementally, including polymorphic links, with reconciliation and rollback planning.

### F13 — P1 · Concurrency · Approval state can split

**Files/classes/methods:** `TimeSheetAuthorizationService::approve`; `WorkflowResolver::ensureMonthlyStepsForEmployees`; `buildApprovalData`.

**Evidence:** Steps are selected pending, checked, then saved individually; legacy timesheets update afterward without a shared transaction. Another request can approve the same rows concurrently. Initialization checks existing rows then creates them despite a unique monthly-step constraint. Display initializes steps, and the controller approval mutation uses a GET route.

**Impact:** Partial approvals, overwritten actor attribution, duplicate-key exceptions, GET-triggered state changes. New or revised days can also diverge from monthly approvals because there is no centralized invalidation/freeze path.

**Fix:** POST with CSRF, transaction and conditional pending-state writes/locks, idempotent step insertion, authoritative rules for post-approval changes, and read-only display where feasible. **Risk:** Medium–high: define approval granularity and revision behavior first; test two concurrent actors and failures between stores.

### F14 — P1 · Queues · Backup timing and execution configuration

**Files/classes/methods:** `PerformBackupJob` timeout; [config/queue.php](/Applications/ServBay/www/zue/config/queue.php); `Console/Kernel::schedule`; job classes.

**Evidence:** Backup timeout is 1,800 seconds; database/Redis `retry_after` is 90. Local queue is `sync`, so dispatch currently runs work inline. No explicit tries/backoff or overlap protection on backups; scheduled backups lack `withoutOverlapping`. Balance jobs carry an Employee with potentially loaded relations.

**Impact:** HTTP blocking locally; concurrent workers can retry a still-running backup after 90 seconds when asynchronous queues are enabled. **Fix:** Dedicated backup queue/worker, retry interval safely above timeout, explicit attempts/backoff, unique operation ID, overlap lock and monitored failures. Use minimal model payloads/IDs and deliberate after-commit dispatch. **Risk:** Medium; enabling asynchronous queues changes when results become visible.

### F15 — P1 · Backups/observability · Incomplete exports can appear successful

**Files/classes/methods:** `BackupService::performBackup/cleanupOldBackups`; `MaintenanceController::export/import/restore`.

**Evidence:** Per-table exceptions are written as SQL comments and execution continues to a completed status. No consistent database snapshot or FK-aware restore strategy. Row export uses offset chunks ordered by first column, including pivots whose first column is non-unique. Concurrent changes can skip/duplicate rows. Filenames have only second precision. Download export rereads the whole temporary SQL file into memory; restore/import load the entire file and execute it. Storage writes return booleans with `throw=false` but are unchecked. Retention cleanup runs without a verified-restorable artifact.

**Impact:** Recovery failure may remain unnoticed until needed, and overlapping backups can collide. **Fix:** Database-native consistent dump/restore or a proven equivalent, fail/report partial exports, unique temporary files, streamed response, validated table allowlist, checked storage writes, and restore verification in a disposable MySQL database. **Risk:** Medium–high; retain existing backups until replacements are verified. Do not attempt to make MySQL DDL rollback-safe merely by wrapping it in a Laravel transaction.

### F16 — P1 · Memory/API-like web responses · Unbounded report hydration

**Files/classes/methods:** `ReportController::timesheets/balances/run`; `EmployeeBalanceExport::sheets`; `EmployeeSheetExport::collection`.

**Evidence:** Timesheet date filters are nullable and query ends in `get()`. With no filters it can hydrate 1.25M rows, eager-load employees, and render one huge document. Employee exports collect and regroup entire result sets. Monthly approval pages use collection `chunk`/grouping after the SQL result is already fully loaded.

**Impact:** PHP memory exhaustion, long response times, occupied workers. **Fix:** Require bounded report periods, cap interactive results, paginate previews, queue large exports and stream/chunk by stable key. Keep printable page grouping, but perform it over bounded batches. **Risk:** Medium: full exports must remain available through an asynchronous/download workflow; silently truncating them would be wrong.

### F17 — P1 · Business integrity · Balance paths and validation disagree

**Files/classes/methods:** `TimeSheetBuilder::create/calculateBalance/calculateBalanceToDate`; `TimeTable::save/destroy`; `TimeSheetController::store/update/destroy`; Employee requests; `CalculateBalance::handle`; `ReportController::to_date`.

**Evidence:** Livewire dispatches recalculation; standard controller mutations do not. `firstOrCreate` leaves an existing day unchanged even on the revise UI path. Requests accept lowercase attendance values while UI/helpers use uppercase. Schedule validation accepts arbitrary/null strings, but calculation divides parsed components without validating denominator; quick creation leaves schedule unset. Existing active local schedules passed a basic ratio check, so current bad data is not asserted. `to_date()` delegates to current balances and ignores an as-of calculation.

**Impact:** Stale or silently incorrect balances and revision behavior. **Fix:** Shared minimal mutation service preserving audit metadata, one canonical value set, valid schedule contract, explicit existing-day behavior, consistent recomputation and as-of reporting. **Risk:** Medium–high: financial/HR semantics need characterization tests, including fractional balances, empty history, leap dates, and post-approval edits.

### F18 — P1 · Appraisal integrity · Scoring and lifecycle gaps

**Files/classes/methods:** `AppraisalReviewController::update/submit/store`; `UpdateAppraisalReviewRequest`; `AppraisalScoreService::updateScores`; `AppraisalFinalizeService::finalizeForEmployee/guessFormVersionId`; `AppraisalAggregationService::aggregateForEmployee`; version-item administration.

**Evidence:** Every input score must be numeric although service/UI support text. Required items are not checked on submit; update only checks draft status, not open period. Percentage denominator includes only answered numeric items. Score-row updates and total updates are not one transaction. Finalize processes only submitted reviews and then locks them: later submitted reviews can replace an existing official result using only the newer subset. No-review fallback picks any active form version, ignoring employee form. Version activation uses separate deactivate/activate writes without locking the parent form, so concurrent activation can violate the intended single-active-version rule. Yearly delete/recreate of scores lacks an encompassing transaction and uses an unordered `last()` as latest version. Version items remain editable even when referenced by historical reviews.

**Impact:** Incomplete evaluations, changed historical interpretation, lost partial results, and non-idempotent re-finalization semantics. **Fix:** Validate by item type and required state, freeze referenced versions, define whether official results include all locked/submitted reviews, and make per-employee finalization atomic/idempotent. **Risk:** High: define the scoring contract with stakeholders before modifying totals; do not infer that unanswered items should automatically count as zero.

### F19 — P1 · Tests · Broken factories and insufficient safeguards

**Files/classes/methods:** `UserFactory::definition`; `EmployeeFactory::definition`; `phpunit.xml`; controller/API tests; V2 resolver/service tests.

**Evidence:** UserFactory omits `number`, while User's creating hook casts it to primary key zero. EmployeeFactory creates linked users, defaults to archived employees, and generates arbitrary schedule text. Seven employee-controller tests errored; five explicit-number V2 tests passed. Generated controller tests largely use a super-admin and do not exercise denials. Many API tests target routes that are currently disabled. PHPUnit's DB isolation overrides are commented out.

**Impact:** Tests fail before checking behavior and can target the configured non-test database if run naively with RefreshDatabase. **Fix:** Realistic factory defaults/states and unique numbers, explicit isolated test DB/cache paths, route-aligned tests, and critical workflow coverage. **Risk:** Low–medium: update fixtures without weakening assertions or deleting tests.

### F20 — P2 · Query performance/correctness · Dashboard and search

**Files/classes/methods:** `DashboardChart::getChartData/updatedSelectedYear/render`; `HomeController::index`; `Searchable::scopeSearch/getAllModelTableFields`; `TimeSheetController::index`.

**Evidence:** Dashboard issues 12 employee counts plus 12 distinct employee counts per call; changing year calls it in the update hook and render. Archived-history conditions conflict with Employee's always-active archive scope. Filled timesheet counts do not use the same employee population. Home labels monthly stats but selects six months ago. Search introspects table columns and applies `%term%` to every field, even an empty search; timesheet index reads search but does not apply it.

**Impact:** Avoidable scans, inconsistent statistics and ineffective UI filters. **Fix:** Define shared population/date semantics; aggregate yearly counts in SQL and reuse results; use explicit searchable fields and skip empty search; apply filters before pagination. **Risk:** Low–medium; correct displayed semantics before caching results.

### F21 — P1 · Security · Role assignment and initial passwords

**Files/classes/methods:** `UserController::store/update/import`; `UserStoreRequest/UserUpdateRequest::rules`; `UserPolicy`; `UsersImport::model`; `ProfileController::update`.

**Evidence:** `roles` is only validated as an array and passed to `syncRoles`; create/update-user permission is not separated from granting super-admin. Imported passwords derive from phone numbers with a fixed fallback. Profile changes accept an unvalidated password, update it before a second validation step, and do not require current-password confirmation.

**Impact:** A delegated user administrator may grant stronger privileges; imported accounts have guessable credentials; a failed profile request can still change a password. Actual delegated-role assignments were not assessed. **Fix:** Grantable-role allowlist/ability, secure one-time onboarding/reset, cohesive validation before writes, password rules/current-password checks appropriate to the flow. **Risk:** Medium; preserve authorized administration while preventing privilege escalation.

### F22 — P2 · Upload/storage · Signature handling is inconsistent

**Files/classes/methods:** `UserSignature::save/storeOrUpdateSignature`; `SignatureService::saveSignature`; `ImageProcessingService::makeWhiteTransparent`; public disk configuration.

**Evidence:** Upload path validates image type/2MB; canvas path accepts arbitrary base64 without decoded size/type checks. No pixel dimension bound. GD fallback loops every pixel and samples coordinate 5 even on tiny images. Signature files use public storage, predictable user/time names, and delete old files before successful replacement is assured. Errors in image conversion are swallowed or return original bytes.

**Impact:** Resource exhaustion, invalid images, lost signatures, and potentially inappropriate public availability of approval signatures. **Fix:** Validate both paths consistently, strict decoding and dimensions, private authorized serving if signatures are confidential, random filenames, checked write then DB update then cleanup. **Risk:** Medium: private serving must preserve authorized print/render workflows. Raw SVG execution or path traversal is not established here.

### F23 — P2 · Code correctness · Incomplete routes and model contract drift

**Files/classes/methods:** `TimeSheetController::edit/store`; `ClinicApointmentController`; `ManagementScopeController`; `AppraisalOfficialController::index`; appraisal Blade templates; `SearchEmployee::searchEmployees`; `Employee` role helpers.

**Evidence:** Resource edit route binds `time_sheet` but edit accepts Employee; separate custom employee edit route exists. Clinic route references absent `history`; several resource actions are empty, and store calls a differently cased ClinicalExam class without data. ManagementScope resource exposes show without a show method. Official search uses absent `first_name/last_name`; views reference `name`, `administration`, `costCenter` absent from Employee. SearchEmployee dereferences a missing result after displaying an alert. Employee role helpers call `hasRole` without the HasRoles trait. Some are unused/stub paths and should not be presented as universally failing screens.

**Fix/impact:** Reconcile routes/model contracts and deliberately retire stubs; removes real 404/500/silent-data defects. **Risk:** Low–medium; verify consumers before deleting methods/routes.

### F24 — P2 · Models/scopes · Archive and date semantics are fragile

**Files/classes/methods:** `Employee::getStartDateAttribute/getLastDateAttribute/boot`; `Scopes/SoftArchivingScope::extend`; `Searchable` archive scopes and `scopeSearchLatestPaginated`; historical reports.

**Evidence:** Date accessors turn stored/null values into formatted strings and overlap date casts. Model relationships to archived employees are automatically excluded, yet historical reports dereference employees. SoftArchivingScope installs an `onDelete` callback while Employee also uses SoftDeletes, so builder deletes can differ from instance soft-deletes. The Searchable trait duplicates archive scope names with different OR semantics; registered macros may take precedence. Its paginated scope declares Builder but returns a paginator.

**Fix/impact:** Explicit presentation formatting and null behavior; deliberate history scopes; characterize archive/delete behavior before simplifying it; correct return types. **Risk:** Medium: archive behavior is domain-specific and cannot be replaced blindly with standard soft deletion.

### F25 — P2 · Import performance/data integrity · Row-by-row, non-idempotent imports

**Files/classes/methods:** `ArchivedEmployeesImport::collection`; `RoomsImport::collection/getResident`; `UsersImport`; user/employee import endpoints.

**Evidence:** Full collection imports, per-row lookup/write, duplicate residence lookup twice per row, unconditional pivot `attach`, no unique pair constraints, and `$room` can remain unset or refer to a previous row when columns are missing. No chunk-reading/batch-import concerns are configured.

**Fix/impact:** Validate normalized rows, batch reads/writes, chunk reading and queued imports for large files, deterministic re-import behavior, row-level error reporting. **Risk:** Medium: retain explicit partial-success versus all-or-nothing policy rather than silently skipping bad rows.

### F26 — P2 · Frontend performance · Duplicate and global assets

**Files:** [resources/js/app.js](/Applications/ServBay/www/zue/resources/js/app.js), `hugerte-init.js`, [resources/sass/app.scss](/Applications/ServBay/www/zue/resources/sass/app.scss), `tabler.scss`, [resources/views/layouts/app.blade.php](/Applications/ServBay/www/zue/resources/views/layouts/app.blade.php), `vite.config.js`.

**Evidence:** Tabler styles enter via prebuilt CSS and compiled Sass; Tom Select enters via bundle and preview CDN; HugeRTE with many plugins is imported globally and a separate public script is also loaded. Existing main JS is approximately 2.13MB uncompressed, a CSS artifact about 742KB, WOFF2 icons about 779KB. These are artifact sizes, not network transfer measurements. The existing manifest includes `resources/js/app.css`, so that Blade reference is not currently a proven missing-manifest error.

**Fix/impact:** Consolidate each library to one source, load the editor only on clinic pages, trim plugins/fonts, pin CDN versions or bundle them. Review unused webpack loaders and Bootstrap 4 before removal. **Risk:** Medium; regression-test modals, selects, signature canvas and Livewire navigation. No asset build was run against the current public output.

### F27 — P2 · Observability · Failures and sensitive actions poorly surfaced

**Files/classes/methods:** `BackupService`; `ImageProcessingService`; `MaintenanceController::import/restore`; `UserController::import`; `ActorResolver`; [config/logging.php](/Applications/ServBay/www/zue/config/logging.php).

**Evidence:** Partial backup errors can be masked; image failures swallowed; DB exception text returned to users; actor fallback logs every lookup; default stack uses a single log file. No explicit audit events for medical edits, role changes, imports, or approval changes were found. The exception handler's empty reportable callback does not itself suppress Laravel reporting.

**Fix/impact:** Structured operation IDs, accountable action logs without medical/password payloads, safe error messages, rotated centralized logs, slow-query/job-duration/memory monitoring, and alerts on partial backup or reconciliation failures. **Risk:** Low; avoid logging full request/model snapshots.

### F28 — P1 · Data confidentiality · Personnel/account dumps are tracked

**Files:** `database/seeders/data/*.sql`, `database/seeders/sql_dump/bak/*`, [database/seeders/sql_dump/converted/000026_users.sql](/Applications/ServBay/www/zue/database/seeders/sql_dump/converted/000026_users.sql) and related converted dumps.

**Evidence:** Git tracks these files. The converted users dump contains 57 email-like values and 57 bcrypt hashes. Real-world provenance was not established and individual values were not included in this report. `.env` itself is not tracked; `.env.example` is.

**Fix/impact:** Determine whether records are real, restrict repository access, replace seed data with synthetic fixtures, and plan history cleanup/credential response if exposure is confirmed. **Risk:** Medium: history rewriting affects collaborators and requires a separate approved plan; do not delete the only data recovery source.

### F29 — P2 · Constraints · Additional business uniqueness gaps

**Files:** pivot migrations, signatures migration, employee migration, appraisal-period migration; related `attach/firstOrCreate/updateOrCreate` paths.

**Evidence:** Employee number and user link are nonunique; signatures.user_id is nonunique despite hasOne/upsert use. Three assignment pivots lack pair uniqueness. Appraisal periods' unique `(year,type,quarter)` permits multiple yearly records when quarter is NULL under MySQL. Local duplicate employee numbers/user links are currently zero.

**Fix/impact:** Add confirmed business invariants after production duplicate checks; normalize yearly period identity or use a suitable enforced key. **Risk:** Medium; uniqueness rules for archived/re-hired employees and historical room assignments must be explicitly decided.

### F30 — P2 · Architecture · Unnecessary hydration and repeated setup

**Files/classes/methods:** `ManagementScopeController::create/edit/index`; `ScopeResolver`; `TimeSheetAuthorizationService::groupedManagedEmployees`; `AppraisalAttendanceService::getAttendanceStats`; `SyncEmployees::handle`.

**Evidence:** Management scope forms execute identical all-employee queries twice. V2 loads all visible employees to produce department counts then separately paginates. Attendance hydrates rows only to count three values. SyncEmployees aggregates SQL correctly but holds all employee models and all groups in memory. At 1,115 employees these are secondary to timesheet reports.

**Fix/impact:** Reuse one minimal dropdown collection, aggregate counts in SQL, select required columns, and batch larger administrative processing. **Risk:** Low–medium; do not cache huge Eloquent collections or replace small bounded lookups with unnecessary infrastructure.

## 5. N+1 query report

**Definitions:** Confirmed = executed read-only measurement or direct collection-to-query trace in active code. Code-confirmed entries are not latency benchmarks. Highly likely = conditional behavior requiring representative records/runtime validation. Potential = dormant or unverified consumer. N is collection size; E employees; S candidate approval steps; P actor policies. Counts exclude auth middleware, pagination count, and unrelated layout queries unless stated.

### Confirmed

| Location / file / method | Relationship or repeated lookup | Current behavior / estimated queries | Recommended fix | Priority |
| --- | --- | --- | --- | --- |
| EmployeeController `index`, `dir`; employee index lines 143–146, directory lines 60–63 | user, location, department, center | Parent query plus up to 4N. Measured 4/16/61 total for N=1/5/20; nullable users explain fewer queries | `with` the four displayed relations; select needed keys | P1 |
| Employee `getTotalWorkingDaysAttribute`, `getTotalOffDaysAttribute` | timeSheets builder counts | Exactly 2N extra queries when read; measured 2/10/40 for N=1/5/20 | Conditional `withCount` or grouped aggregate; opt-in resource fields | P1 |
| TimeSheetAuthorizationService `groupedManagedEmployees` → WorkflowResolver `hasRole` → ActorResolver | user and roles | Two complete employee passes; each re-fetches user, possibly canonical and fallback users, then roles. Measured 40 queries for 20 sampled employees | Batch canonical/fallback user map with roles; partition once | P1 |
| TimeSheetAuthorizationService `approve`, `approvalStages` → `dependencyIsSatisfied` | flow step and predecessor | Up to 2 queries per evaluated pending step; display may short-circuit, writes inspect all eligible steps | Reuse flow-step map; load monthly predecessors once; transactional dependency check on write | P1 |
| RoomController `index` → room index lines 71–74 → Room availability accessor | residence, employees, occupancy count | Parent + up to 3N reads, plus pivot writes only in mutations | `with('residence','employees')` and constrained occupancy `withCount`; stop accessor re-query | P1 |
| DepartmentController `index` → department index line 70 | administration | Parent + up to N relationship reads | Eager-load administration | P2 |
| UserController `index` → users index line 73 | signature | Parent + N reads | Eager-load signature | P2 |
| ManagementScopeController `index` → management_scopes index line 100 | managers | Eager-loads singular manager but loops plural managers: N additional pivot queries | Add plural managers; retain singular fallback only where needed | P2 |
| FlightController `approve` | employees.rooms | Employee query + E room queries, then per-pivot writes | Load rooms in one batch or scoped pivot updates with explicit semantics | P1 |
| ScopeResolver `resolveEmployeeAccessMap` → `matchedEmployeeIdsForPolicy` | employees per actor policy | P employee-ID queries followed by candidate hydration; repeated resolution repeats all of them | Reuse request results; batch matching without losing winning-policy logic | P2 |

### Highly likely / conditional

| Location / method | Relationship | Estimated behavior and condition | Recommended fix | Priority |
| --- | --- | --- | --- | --- |
| WorkflowResolver `ensureMonthlyStepsForEmployees` → `flowMatchesEmployee` | employee's user.roles | Up to O(E × role-bearing flows × checked roles) queries; even already-initialized months resolve flows again | Preload roles and flows once; skip fully initialized employees where semantics permit | P1 |
| EmployeeCollection/EmployeeResource and SearchEmployee event serialization | department.administration, location, center plus two counts | Raw serialization invokes global appends; up to 4 relationship queries + 2 counts per employee, depending on loaded/null relations | Explicit resource/event payload of required scalar fields; eager-load only needed relations; aggregate counters | P1 if activated / P2 current event path |
| AppraisalAggregationService `aggregateYearly/aggregateForEmployee` | reviews and official results per employee/quarter | At least one review lookup per employee/quarter plus result/score queries; currently processes employees without appraisals | Queue and restrict eligible employees; batch per-quarter reads and aggregate writes | P2 |

### Potential / dormant consumers

| Location / method | Relationship | Estimated behavior / caveat | Recommended fix | Priority |
| --- | --- | --- | --- | --- |
| ClearRoomVacant `handle` | employees.rooms | 1+E reads on dispatch; no active dispatch found | Batch eager-load if reactivated | P2 |
| FillRoomVacant `handle` | ownRoom query + rooms | 1+E existence reads and up to E room reads; no active dispatch found | Use constrained preloaded owner-room data | P2 |
| API room collections | Room.available | N occupancy count queries from appends; API routes disabled | Constrained aggregate and explicit RoomResource before enabling | P2 |
| User `canManageUser`, Employee `canManage` if called in collection policies | employee/scope resolution | Per-target actor/scope/existence work; no active collection caller demonstrated | Request-local/batch map only if a consumer needs this | P3 |

**Not N+1 findings:** ReportController preloads department/center/location used by its reports and EmployeeSheetExport; appraisal edit/finalize preload nested item relationships; V2 flows preload steps; management scope location/department/center relations are already eager-loaded. First-row approval headers and up to four signature stages are bounded extra queries, not employee-scale N+1. Commented Blade relationship cells were not counted. Bulk writes per item/day are discussed separately from relationship N+1.

## 6. Database performance and index report

### Measured/planned queries

- Read-only EXPLAIN for `MONTH(day)=1 AND YEAR(day)=2025` without employee restriction: full scan, estimate **1,179,747 rows**, no usable date index.
- Equivalent `day >= '2025-01-01' AND day < '2025-02-01'`: range access on existing `time_sheets_day_index`, estimate **42,744 rows**. These are optimizer estimates, not measured runtimes. Actual approval queries also restrict employee IDs and may choose their employee index; do not apply the full-table estimate to every approval request.
- Single-employee plans could use the existing employee index; only the range form also offered the day index as a candidate. A composite employee/day index should improve that workload after measurement on representative production records.
- `whereDate(day, '<', cutoff)` in balance helpers wraps an already-DATE column; compare the DATE directly when behavior is equivalent.
- Dashboard distinct employee counts over date ranges use the existing day index but may benefit from a covering date/employee index.
- Broad `%search%` across every model column is not fixed by adding conventional B-tree indexes to every field.

### Recommended indexes and constraints

| Table | Columns / constraint | Actual queries benefiting / why | Expected benefit | Downside / prerequisite |
| --- | --- | --- | --- | --- |
| time_sheets | **UNIQUE(employee_id, day)** | First-or-create, calendar, approval date ranges, max(day) per employee | Enforce one daily row; narrower employee/date scans | Validate invariant and duplicates on production; index build/write/storage cost; do not add a second identical nonunique index |
| time_sheets | Candidate `(day, employee_id)` | Home/Dashboard distinct employee counts by period | Cover count query without loading table rows | Benchmark after range rewrite; may replace simple day index, not necessarily coexist |
| time_sheets | Candidate `(employee_id, value)` | Balance grouped counts and conditional working/off counts | Cover employee history aggregates | Add only if remaining aggregate time justifies write/storage cost after batching |
| employees | UNIQUE(number), if personnel number is globally unique | SearchEmployee, registration/linkage/import/quick creation | Deterministic lookup and duplicate prevention | Rehire/archive numbering rules; NULL handling; current simple number index becomes redundant |
| employees | UNIQUE(user_id), if relationship is truly one-to-one | ActorResolver first lookup / canonical user mapping | Prevent ambiguous identity resolution | Confirm whether shared accounts are allowed; nullable entries remain possible |
| signatures | UNIQUE(user_id) | hasOne and updateOrCreate | Prevent duplicate signatures under concurrency | Reconcile duplicate rows/files before adding |
| employee_flight | UNIQUE(flight_id, employee_id) | Flight participant attach/sync/list | Duplicate prevention and parent lookup | Retain reverse employee-leading access/FK index |
| flight_passenger | UNIQUE(flight_id, passenger_id) | Passenger attach/list | Same | Retain passenger-leading index |
| employee_room | UNIQUE(room_id, employee_id), if table represents current assignments | Room sync/attach/occupancy | Prevent duplicate counts and attachments | Not appropriate without redesign if historical repeated stays belong in this table |
| appraisal_periods | Non-null normalized yearly identity / enforced unique year-type-quarter key | firstOrCreate and period store/update | Prevent duplicate yearly periods under concurrency | Existing NULL quarter defeats current unique invariant; choose a schema compatible with business semantics |

**Already correct:** FK indexes on employee organization/user links and room residence; date/value/employee indexes on timesheets; composite unique review identity, official period/employee, review-score item pairs, manager pivots, monthly approval-step identity; Spatie/Sanctum indexes. Do not add duplicate single-column foreign-key indexes.

**Redundancy:** No exact duplicate index definitions were identified in the inspected live index metadata. Prospective composite/unique replacements may make employee_id, day, or number indexes redundant; check FK support and query plans before removing them. Low-cardinality policy flags currently index tiny tables; adding more is not a priority.

**Constraint/retention concerns:** Cascade and identity mismatches in F12 take precedence over extra indexes. Audit snapshot/form item deletion semantics before changing appraisal cascades. Some migration `down()` methods, including approval columns, are empty; rollback behavior needs tests. Never assume a schema rollback can reverse destructive data changes.

## 6A. API performance review

The loaded router contains **zero active API routes**. Do not expose or version the dormant API automatically. Its resource and child-collection controllers already paginate and usually call policies; this is positive. No active massive API collection was demonstrated.

Before reactivation, replace pass-through EmployeeResource/RoomResource with explicit response contracts. Employee defaults include address, passport, ID-card and contact fields plus query-producing appends. Room serialization adds an occupancy query per row. `whenLoaded` can prevent a resource from requesting an unloaded relationship, but it does not repair a query-producing accessor still invoked through `parent::toArray()`.

The dormant login controller performs `auth()->attempt` and then fetches the user again by email before issuing a token. Reuse the authenticated user and define token abilities/expiry/revocation according to client requirements. Default API throttle exists, but login-specific protection should be tested if enabled. Standard JSON resources and no-content delete responses are already used; no wholesale response-format rewrite is necessary. Cursor pagination is a candidate for future large sequential timesheet feeds, with stable ordering, not a replacement for every existing CRUD paginator.

## 7. Architecture report

### Keep

- Standard Laravel 10 kernels/providers/routes and Eloquent CRUD. Existing controller queries are usually appropriate for small CRUD operations.
- Form Requests with `$request->validated()` and fillable attributes. A Form Request returning `authorize=true` is acceptable when the controller performs authorization; the gap is missing enforcement on specific paths.
- Spatie policies/permission integration and the super-admin Gate hook. Existing permissions are reusable; avoid a second unrelated authorization framework.
- Separate approval actor/scope/workflow resolvers and scoring/backup services. Their decomposition is useful even though some orchestration is repetitive.
- SQL grouping already used by balance helpers, yearly score averaging, and SyncEmployees; AppraisalFinalizeService's existing transaction; ManagementScopeService transactions.
- Pagination on standard CRUD/API collection code. Do not replace every numbered page with cursor pagination.
- Blade and Livewire. `layouts.app` is a configured convention here; it does not need renaming simply to match a default example.

### Refactor selectively

Make one small timesheet mutation service the enforcement point for fill, revise, delete, audit metadata, approval-state effects, and balance recomputation. Keep HTTP/Livewire validation and authorization explicit. Choose one authoritative scope store. Align employee/user identity. Separate approval presentation/page grouping from permission resolution and write orchestration; the same printable grouping is duplicated in legacy/V2 services. Move large exports/aggregation to queued work with explicit operation state.

The largest services are TimeSheetAuthorizationService (583 lines), TimeSheetService (344), ScopeResolver (334), and Employee (496). Size alone is not the finding: duplicated workflow/page construction, hidden database access, and conflicting identity rules are the reasons to extract code.

### Do not change without evidence

No repository pattern, microservices, CQRS, event sourcing, broad DTO hierarchy, global eager-loading defaults, Redis-for-everything, database partitioning, or Octane installation is justified by the current evidence. A million timesheets is compatible with a well-indexed conventional relational design. Enums or a small schedule value object can help centralize proven business invariants later, but are not substitutes for authorization or tests.

## 8. Security report

Immediate containment order is F01 → F02/F03 → F04/F05/F21. Add denial tests for guests, ordinary accounts, unrelated managers, read-only actors, and revoked permissions.

Additional distinctions:

- Most Eloquent filters bind values. Fixed `selectRaw` aggregations, `FIELD` ordering, and `whereRaw('0 = 1')` are not SQL injection findings. BackupService interpolates a caller-selected table identifier into raw SQL: whitelist identifiers; actual injection impact was not executed. Maintenance's arbitrary SQL execution is already critical because of missing authorization.
- Observed raw Blade output is paginator HTML or application-constant markup. No direct stored-XSS rendering of arbitrary clinic HTML was proven. Clinic content is nevertheless unvalidated, and editor/Livewire advisories require review; client editor filtering is not server authorization/sanitization.
- Web middleware includes CSRF. The GET approval mutation bypasses the normal CSRF-protected mutation pattern. `/rr` having CSRF does not make its anonymous import acceptable.
- No path-traversal exploit was confirmed. Route segment restrictions and Flysystem normalization provide some defenses; restore/download should still operate on a validated backup identifier/allowlist, not trust a filename as authority.
- User passwords/remember tokens are hidden, and normal account creation hashes passwords. Employee serialization has no equivalent field allowlist; dormant resources expose personnel fields unless redesigned before activation.
- Public signatures and tracked data dumps need explicit confidentiality decisions. No claim is made that the repository is public or that `.env` was leaked.
- Profile role input is not persisted by ProfileController; it is not a proven self-service role escalation. User administration `syncRoles`, separately, needs a grantable-role boundary.
- Role/permission CRUD invokes controller authorization, with super-admin access supplied by the existing Gate hook; preserve those protections rather than describing all administration as unguarded.

## 9. Caching, queues, production, and Octane

### Caching

No explicit application `Cache::remember` strategy was found. Spatie's permission cache is configured with a 24-hour expiry and should be retained; it does not eliminate repeated user retrieval in WorkflowResolver. Maintenance settings currently query individually.

Good candidates after correctness fixes: short-lived dashboard aggregates keyed by year and authorized scope; small organization dropdowns with write invalidation; one request-local scope/actor/role map. Do not persistently cache mutable grants without immediate invalidation, cache full employee/timesheet models, or hide bad queries behind forever caching.

`Cache::flexible()` is **not present** in the installed Laravel 10 cache repository (verified by method inspection). Use Laravel 10-supported `remember`, explicit invalidation, and locks only where an observed expensive regeneration warrants them. File-cache locks/state are not a shared coordination mechanism across multiple hosts.

### Background work

Backup, balance and room jobs implement ShouldQueue; yearly aggregation is exposed synchronously over HTTP despite a console command. Imports lack queued/chunk concerns; image conversion happens inline. Prioritize large reports/imports/backups/yearly generation for background execution. Small, bounded signature processing may remain synchronous after dimension limits.

Use per-operation IDs and idempotent retries. SerializesModels stores model identifiers, but loaded relations can be restored by workers; avoid unnecessarily carrying them. Verify dispatch after commit when the job depends on committed data. No job-batch table migration was found, so do not introduce Laravel batches without their schema/workflow setup.

### Production readiness

| Area | Evidence / recommendation |
| --- | --- |
| Environment/debug | Local debug=true is expected for development. Production values were not inspected; require debug=false and production environment there |
| Config/routes/views | Config uncached locally; route cache provably stale; compiled-view failure observed. Build and smoke-test caches as part of deployment |
| Event caching | Only a small standard listener map; not a meaningful current bottleneck |
| OPcache | CLI extension not loaded; inspect FPM separately before claiming a production issue |
| Workers | Sync locally; no checked-in worker supervision evidence. Configure/restart/monitor async workers deliberately |
| Scheduler | Period sync at 00:05; optional backup cadence from DB settings; no overlap protection. Verify actual cron/service execution and timezone |
| Files/cache/sessions | Local/file storage adequate for one host; shared durable storage/session/cache needed before horizontal replicas |
| Logging | Default single file/debug level; choose production level, rotation, aggregation and redaction |
| Frontend | Ship production build, do not expose development Vite server as the production asset server |
| Assets | Existing manifest is present; rebuild after dependency changes, with asset-size and UI smoke checks |

### Octane recommendation: NOT YET

**Reason:** The major costs are queries, repeated authorization work, synchronous processing and memory-heavy reports. Octane will not fix these.

**Risks:** BackupService modifies process-level memory/time settings; Rules has mutable static collections (currently reinitialized on each use, so no demonstrated user leak); dormant UnderSupervisionEmployees captures auth-derived state at construction and would be dangerous if registered as a long-lived global scope. No active application singleton retaining Request/User was found. Large report allocations increase long-lived-worker risk.

**Required changes before trial:** Fix critical security/data issues, remove request work from backup processing, confirm scoped service lifetimes, test sequential requests for different users/scopes and worker memory growth, confirm package compatibility, benchmark an FPM baseline.

**Expected benefit:** Potentially lower framework bootstrap CPU on short requests; no defensible percentage can be estimated from this audit. Keep conventional FPM until measurements justify a trial.

## 10. Tests, dependencies, and standards

### Executed verification

| Check | Result |
| --- | --- |
| Boost application/runtime, route metadata, schema/index/FK queries | Read successfully; ListRoutes specifically failed on stale Livewire class |
| Employee relationship and counter query probes | Confirmed linear query growth; no personnel values returned |
| V2 role lookup probe | 40 reads for two passes over 20 employees |
| Duplicate timesheet employee/day check | 0 duplicate groups locally |
| Duplicate employee number / nonnull user link checks | 0 duplicate groups locally |
| Active schedule basic ratio validation query | 0 invalid active local schedules; creation paths can still admit invalid ones |
| `tests/Unit/TimeSheetAuth` | **4 passed, 11 assertions** |
| [tests/Feature/TimeSheetAuthorizationServiceTest.php](/Applications/ServBay/www/zue/tests/Feature/TimeSheetAuthorizationServiceTest.php) | **1 passed, 4 assertions** |
| [tests/Feature/Controllers/EmployeeControllerTest.php](/Applications/ServBay/www/zue/tests/Feature/Controllers/EmployeeControllerTest.php) | **7 errors, 0 assertions**; factory primary-key collision and stale view compilation |
| PHP syntax check | 385 application/routes/config/migration/factory/test PHP files passed (`php -n -l`); this is syntax validation, not static type analysis |
| Composer platform requirements | Passed on local PHP 8.4.24 |
| Composer security audit | Nonzero exit: 61 advisory entries / 17 packages |
| npm security audit | Nonzero exit: 14 affected nodes (8 high, 5 moderate, 1 low) |

Tests used explicit `DB_CONNECTION=sqlite DB_DATABASE=:memory:`, testing/array/sync stores, a nonexisting isolated route-cache path, and `--do-not-cache-result`. An initial `php artisan test ... --no-interaction` attempt was rejected because this installed test wrapper forwards that flag to PHPUnit; the same targeted tests were then run directly with vendor/bin/phpunit. No application DB refresh/migration was run. SQLite success does not prove MySQL-specific cascade, enum, concurrent-write, or dump behavior.

The entire suite was not run. Repair shared fixtures/caches first, then run affected HTTP/Livewire tests followed by the full isolated suite. No coverage percentage is asserted.

### Meaningful missing tests

1. Registration/provisioning, maintenance and import authorization, ordinary-user denials, privilege assignment, impersonation session boundaries.
2. Livewire forged event/action IDs, unrelated clinic appointments, scope revocation between requests, bounded input/ranges.
3. Fill versus revise versus approve capabilities; target reassignment; concurrent daily writes; post-approval revision; failure between V2 and legacy updates.
4. User/employee IDs deliberately different, missing canonical/fallback links, mutable-number consequences, retention-safe deletion.
5. Required appraisal items/text values/closed periods, repeated finalization, mixed versions, concurrent autosaves, yearly retry behavior.
6. Backup partial failure, filename collision, storage failure, restore round trip in isolated MySQL, queue timeout and retry behavior.
7. Query budgets over multiple list sizes, report date limits, pagination and aggregate correctness across archived employees/year boundaries.

### Dependency audit detail

Composer's 17 reported packages: dompdf/dompdf (6 entries), guzzlehttp/guzzle (9), guzzlehttp/psr7 (4), laravel/framework (3), league/commonmark (12), livewire/livewire (1), phpoffice/phpspreadsheet (9), phpunit/phpunit (1), psy/psysh (1), symfony/http-foundation (2), symfony/mailer (1), symfony/mime (2), symfony/polyfill-intl-idn (1), symfony/process (1), symfony/routing (2), symfony/yaml (3), tinymce/tinymce (3). Composer reported no abandoned packages.

Prioritize spreadsheet parsing reachable from upload routes, Livewire client handling, framework HTTP behavior, and browser assets actually used. The PhpSpreadsheet IOFactory filename advisory does not by itself prove SSRF/RCE here: callers pass uploaded temporary files rather than an arbitrary remote filename. Its malformed-file resource-exhaustion advisories are more directly relevant. Dompdf/PHPWord/Guzzle production usages were not found in the inspected application paths; installed presence is not proof of exploitation. Some Symfony/Vite advisories are Windows- or dev-server-specific. Axios is browser-bundled here; Node HTTP-adapter advisories need separate applicability analysis.

npm affected nodes: axios, browserslist, css, decode-uri-component, esbuild, fast-uri, form-data, immutable, laravel-vite-plugin, nanoid, postcss, resolve-url-loader, source-map-resolve, vite. Counts are dependency nodes, not distinct reachable exploits. Packages under devDependencies can still ship browser code (Axios, styles); conversely build-only advisories are not automatically production browser vulnerabilities.

`composer outdated --direct` reports newer major lines for Laravel, Livewire, Sanctum, Excel, Permission, PHPUnit and others. Compatibility must be resolved as a coordinated matrix; do not use `npm audit fix --force` or broad Composer upgrades as an audit step. Laravel 10's old support status is established by its official support policy. Review potentially unused Intervention/PHPWord/Dompdf/TinyMCE and webpack loaders before removing anything; HugeRTE is actively imported and image processing uses GD/Imagick directly.

### Standards and static analysis

Pint is installed. No PHPStan/Larastan/Rector configuration or dependency was found. `phpcs.xml` exists, but PHPCS is not a declared dependency; its `<file->tests</file->` entry does not express the intended `<file>` target. Its effectiveness is unproven.

Use Pint on subsequent touched PHP files, with repository-required `vendor/bin/pint --dirty`; do not run a formatter over this audit's unchanged code. Add a compatible Larastan/PHPStan version only with dependency approval, initially targeting application code at a practical baseline. Useful catches include missing methods/properties, Builder-versus-paginator return type, nullable relations, case-sensitive autoload paths, and incomplete route handlers. Rector is optional for a later framework migration, not needed for the first fixes. Avoid mass style edits mixed with security fixes.

## 11. Quick wins

- Restrict maintenance/import/operations/appraisal actions with existing policy/gate conventions; remove the anonymous import route.
- Repair route/view cache generation in a controlled deployment.
- Eager-load displayed employee, signature, department-administration, and scope-manager relations.
- Replace date function predicates with equivalent date ranges.
- Skip empty searches; use explicit searchable columns; reuse duplicate dropdown queries.
- Add upload size/pixel/range limits and validate before any profile/password writes.
- Repair UserFactory number defaults and isolate test databases/caches.
- Correct the GET approval route to a CSRF-protected POST while preserving the approval service's behavior.

These are quick in implementation scope, but authorization changes still require denial tests and documented intended operator roles.

## 12. Medium-term improvements

- Canonical timesheet mutation and approval authorization, including transaction/concurrency semantics.
- Batch V2 identity/role/dependency queries and eliminate repeated scope resolution.
- Reconcile the active V2 policy store with management UI writes.
- Add daily uniqueness and measured composite indexes with production data checks.
- Bounded report previews and asynchronous streaming exports/imports.
- Verified, monitored database backup/restore process with correct retry/timeout behavior.
- Appraisal required-item/version/finalization consistency tests and fixes.
- Patch affected dependencies and consolidate heavy frontend assets.

## 13. Long-term improvements justified by this application

- Move to a supported Laravel/PHP/package combination after immediate containment and regression coverage.
- Normalize user/employee identity and retain stable primary keys; migrate audit/signature relationships deliberately.
- Establish retention-safe organizational/account deletion and confidential storage rules.
- Introduce shared sessions/storage/cache and distributed worker coordination only when running multiple hosts.
- Evaluate archived timesheet storage or reporting summaries only if real query/retention measurements justify them. Octane remains a later benchmark-driven option.

## 14. Recommended implementation order

**AUDIT COMPLETE — no implementation changes made.**

1. Contain public provisioning, maintenance, and unguarded sensitive actions (F01–F04, F21).
2. Repair test fixtures and deployment cache reproducibility; add denial tests for the affected endpoints (F06, F19).
3. Unify action-specific timesheet/clinic authorization and target checks (F02, F05).
4. Reconcile identity and authoritative scope mapping before changing workflow semantics (F11–F12).
5. Make timesheet mutations/approvals atomic and idempotent; establish unique daily entries and balance consistency (F08, F13, F17).
6. Remove measured N+1 and redundant resolution; rewrite date predicates and benchmark candidate indexes (F09–F10, F20, F30).
7. Bound reports and move large operations to correctly configured queues; verify backup restore (F14–F16, F25).
8. Correct appraisal authorization/lifecycle and signature handling with focused tests (F03, F18, F22).
9. Patch affected dependencies and reduce frontend duplication; stage the supported framework upgrade (F07, F26).
10. Add operational monitoring, retention controls and realistic CI coverage; address lower-priority contract/dead-code cleanup (F23–F24, F27–F29).

Implementation should proceed only after approval, in small phases with affected tests, available static checks, Pint on changed PHP, and a clear regression summary.

## References

- [Laravel 10 support policy](https://laravel.com/docs/10.x/releases#support-policy): security support dates and PHP support matrix.
- [Laravel 10 Eloquent relationships](https://github.com/laravel/docs/blob/10.x/eloquent-relationships.md): eager loading, counts, lazy-loading prevention; consulted through Boost.
- [Laravel 10 queues](https://github.com/laravel/docs/blob/10.x/queues.md): timeout must be shorter than retry interval; consulted through Boost.
- [Livewire 3 security](https://livewire.laravel.com/docs/3.x/security): action parameters require authorization.
- [Livewire advisory returned by Composer](https://github.com/advisories/GHSA-g3hc-697w-wm82).
- [PhpSpreadsheet malformed XLS/OLE advisory returned by Composer](https://github.com/advisories/GHSA-xh5m-36r6-47m3).
- [Laravel email validation advisory returned by Composer](https://github.com/laravel/framework/security/advisories/GHSA-5vg9-5847-vvmq).

Dependency results are a dated scanner snapshot. Re-run audits when implementing; do not infer a fixed version is compatible merely from the advisory's affected range.

## Evidence navigation

These links identify primary code locations; each detailed finding names additional affected files and methods.

| Finding | Primary source |
| --- | --- |
| F01 | [app/Http/Controllers/MaintenanceController.php::import](/Applications/ServBay/www/zue/app/Http/Controllers/MaintenanceController.php:133) |
| F02 | [app/Livewire/TimeTable.php::save](/Applications/ServBay/www/zue/app/Livewire/TimeTable.php:49) |
| F02 | [app/Livewire/ClinicEmployeeInfo.php::load_apointment](/Applications/ServBay/www/zue/app/Livewire/ClinicEmployeeInfo.php:62) |
| F03 | [app/Http/Controllers/Appraisals/AppraisalOfficialController.php::approve](/Applications/ServBay/www/zue/app/Http/Controllers/Appraisals/AppraisalOfficialController.php:90) |
| F04 | [app/Http/Controllers/OperationsController.php::archiveByTimesheet](/Applications/ServBay/www/zue/app/Http/Controllers/OperationsController.php:30) |
| F05 | [app/Http/Controllers/TimeSheetController.php::update](/Applications/ServBay/www/zue/app/Http/Controllers/TimeSheetController.php:293) |
| F06 | [bootstrap/cache/routes-v7.php](/Applications/ServBay/www/zue/bootstrap/cache/routes-v7.php:1) |
| F07 | [composer.lock](/Applications/ServBay/www/zue/composer.lock:1) |
| F08 | [database/migrations/2023_11_16_000014_create_time_sheets_table.php::up](/Applications/ServBay/www/zue/database/migrations/2023_11_16_000014_create_time_sheets_table.php:11) |
| F09 | [app/Services/TimeSheetAuthorizationService.php::groupedManagedEmployees](/Applications/ServBay/www/zue/app/Services/TimeSheetAuthorizationService.php:91) |
| F09 | [app/Services/TimeSheetAuth/WorkflowResolver.php::dependencyIsSatisfied](/Applications/ServBay/www/zue/app/Services/TimeSheetAuth/WorkflowResolver.php:126) |
| F10 | [app/Models/Employee.php::getTotalWorkingDaysAttribute](/Applications/ServBay/www/zue/app/Models/Employee.php:188) |
| F10 | [app/Models/Room.php::getAvailableAttribute](/Applications/ServBay/www/zue/app/Models/Room.php:29) |
| F11 | [app/Services/ManagementScopeService.php::updateScope](/Applications/ServBay/www/zue/app/Services/ManagementScopeService.php:81) |
| F12 | [app/Models/User.php::booted](/Applications/ServBay/www/zue/app/Models/User.php:87) |
| F12 | [database/migrations/2026_04_09_200000_make_users_id_manual_and_sync_with_number.php::up](/Applications/ServBay/www/zue/database/migrations/2026_04_09_200000_make_users_id_manual_and_sync_with_number.php:10) |
| F13 | [app/Services/TimeSheetAuthorizationService.php::approve](/Applications/ServBay/www/zue/app/Services/TimeSheetAuthorizationService.php:378) |
| F14 | [app/Jobs/PerformBackupJob.php](/Applications/ServBay/www/zue/app/Jobs/PerformBackupJob.php:1) |
| F14 | [config/queue.php](/Applications/ServBay/www/zue/config/queue.php:1) |
| F15 | [app/Services/BackupService.php::performBackup](/Applications/ServBay/www/zue/app/Services/BackupService.php:13) |
| F16 | [app/Http/Controllers/ReportController.php::timesheets](/Applications/ServBay/www/zue/app/Http/Controllers/ReportController.php:34) |
| F17 | [app/Helpers/TimeSheetBuilder.php::calculateBalance](/Applications/ServBay/www/zue/app/Helpers/TimeSheetBuilder.php:58) |
| F18 | [app/Services/Appraisals/AppraisalFinalizeService.php::finalizeForEmployee](/Applications/ServBay/www/zue/app/Services/Appraisals/AppraisalFinalizeService.php:15) |
| F19 | [database/factories/UserFactory.php::definition](/Applications/ServBay/www/zue/database/factories/UserFactory.php:23) |
| F20 | [app/Livewire/DashboardChart.php::getChartData](/Applications/ServBay/www/zue/app/Livewire/DashboardChart.php:33) |
| F20 | [app/Models/Scopes/Searchable.php::scopeSearch](/Applications/ServBay/www/zue/app/Models/Scopes/Searchable.php:45) |
| F21 | [app/Http/Controllers/UserController.php::update](/Applications/ServBay/www/zue/app/Http/Controllers/UserController.php:104) |
| F21 | [app/Imports/UsersImport.php::model](/Applications/ServBay/www/zue/app/Imports/UsersImport.php:19) |
| F22 | [app/Livewire/UserSignature.php::save](/Applications/ServBay/www/zue/app/Livewire/UserSignature.php:25) |
| F23 | [app/Http/Controllers/ClinicApointmentController.php::store](/Applications/ServBay/www/zue/app/Http/Controllers/ClinicApointmentController.php:62) |
| F24 | [app/Models/Scopes/SoftArchivingScope.php::extend](/Applications/ServBay/www/zue/app/Models/Scopes/SoftArchivingScope.php:36) |
| F25 | [app/Imports/RoomsImport.php::collection](/Applications/ServBay/www/zue/app/Imports/RoomsImport.php:14) |
| F26 | [resources/js/hugerte-init.js](/Applications/ServBay/www/zue/resources/js/hugerte-init.js:1) |
| F27 | [app/Services/ImageProcessingService.php::makeWhiteTransparent](/Applications/ServBay/www/zue/app/Services/ImageProcessingService.php:14) |
| F28 | [database/seeders/sql_dump/converted/000026_users.sql](/Applications/ServBay/www/zue/database/seeders/sql_dump/converted/000026_users.sql:1) |
| F29 | [database/migrations/2025_12_29_000005_create_appraisal_periods_table.php::up](/Applications/ServBay/www/zue/database/migrations/2025_12_29_000005_create_appraisal_periods_table.php:8) |
| F30 | [app/Http/Controllers/ManagementScopeController.php::create](/Applications/ServBay/www/zue/app/Http/Controllers/ManagementScopeController.php:60) |
