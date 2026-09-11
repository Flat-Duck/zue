> Historical audit snapshot. See FIX_PLAN_CHECKLIST.md for current status; findings require revalidation against current code.

# Claude Audit — Laravel Application (`zue`)

**Date:** 2026-09-09  
**Status:** all findings below have since been remediated — see `claude_check_list.md`
**Branch:** `claude-improvements`
**Baseline commit:** `b8da5e4`
**Method:** All findings below were verified by execution against the current working tree — running the suite, resolving dependencies, compiling Blade templates, and querying the database. Claims that could not be reproduced are marked as corrections.

---

## 1. Executive summary

The application is a Laravel 10 MVC + Blade + Livewire HR system. Prior remediation (Phases 0–9 of `FIX_PLAN.MD`) is real and substantial: authorization gates, timesheet mutation centralisation, appraisal transactionality, backup hardening, and dependency cleanup are all present in the tree.

Three claims in `FIX_PLAN_CHECKLIST.md` did not survive verification, and one phase had no work at all:

1. The full test suite is **not** green — it had never been run end to end.
2. Tests execute against the **live MySQL database** and drop every table.
3. One **live production bug** was introduced by the prior route cleanup.
4. Phase 10 (Laravel 13 upgrade) was untouched; its feasibility is now established.

### Verified state at audit time

| Signal | Value |
| --- | --- |
| Laravel | 10.50.3 |
| PHP | 8.4.24 |
| Registered routes | 234 (0 `api/*`) |
| Full suite | 8 failed, 1 risky, 149 passed (393 assertions) |
| Failing files | `TimeSheetControllerTest` (7/7), `ExampleTest` (1) |

---

## 2. Findings

### F-1 — Tests run against the live MySQL database (Critical)

`phpunit.xml` has its isolation lines commented out, and no `.env.testing` existed:

```xml
<!-- <env name="DB_CONNECTION" value="sqlite"/> -->
<!-- <env name="DB_DATABASE" value=":memory:"/> -->
```

`php artisan test` therefore resolved `DB_CONNECTION=mysql` / `DB_DATABASE=zue`. Every feature test uses `RefreshDatabase`, which runs `migrate:fresh` and drops all tables.

Database state observed during the audit:

| employees | time_sheets | users | migrations |
| ---: | ---: | ---: | ---: |
| 0 | 0 | 0 | 69 |

The 2026-09-08 audit recorded **1,245,598 timesheets and 1,115 employees** in this same database. The loss predates this session — commit `b8da5e4` (2026-09-09 00:39) already documented "zero employees/timesheets" — but the mechanism remained live and unguarded.

This maps to the Phase 0 item "Isolate test database", which was still open.

> The owner has since confirmed this database is a disposable demo replica, so no production data was at risk. The isolation defect is still fixed, because an unisolated suite makes every future test run destructive on any machine that has real data.

### F-2 — Live 500 on the timesheet show page (High)

The prior remediation removed the `create` and `edit` resource routes:

```php
// routes/web.php:99
Route::resource('time-sheets', TimeSheetController::class)->except(['create', 'edit']);
```

but left a live reference behind:

```php
// resources/views/app/time_sheets/show.blade.php:96
@can('create', App\Models\TimeSheet::class)
<a href="{{ route('time-sheets.create') }}" class="btn btn-primary">
```

Because the reference sits inside `@can('create', ...)`, the page renders correctly for unprivileged users and **throws `RouteNotFoundException` (HTTP 500) for exactly the users who hold `fill timesheets` or `create timesheets`** — the staff who actually use the page. Reproduced by the suite as a `ViewException` naming `show.blade.php`.

**Correction to the initial reading.** Grep found five references to the deleted routes. Compiling the Blade templates and inspecting the compiler output proved four of them are dead code:

| Location | Status |
| --- | --- |
| `show.blade.php:96` | **Live — the actual bug** |
| `index.blade.php:284`, `:336` | Dead — inside `{{-- --}}` block spanning lines 243–379 |
| `edit.blade.php:60` | Dead — inside `{{-- --}}` block spanning lines 26–71 |
| `IconTimeSheet.php:19` | Dead — component only invoked from commented-out Blade |

One live breakage, not five.

### F-3 — `TimeSheetControllerTest` is entirely stale (Medium)

All seven tests fail. Two distinct causes:

- Tests call `route('time-sheets.create')` / `route('time-sheets.edit')`, deleted in the prior remediation.
- `it_displays_index_view_with_time_sheets` asserts `assertViewHas('timeSheets')`, but `TimeSheetController::index()` now compacts `employees`, `search`, `scopeOptions`, `selectedScopePolicyId`, `groupedEmployees`. The page is employee-centric now; the test still describes the old timesheet-centric page.

`ExampleTest` asserts `/` returns 200; the application redirects unauthenticated users to login (302).

None of these are application defects — they are tests that were never updated when the application changed. They do, however, mean the suite could not function as a regression gate.

### F-4 — Sanctum is dead weight (Low)

`laravel/sanctum` is installed and required, but has **zero live usage**:

- no `createToken` / `tokenCan` calls anywhere in `app/` or `routes/`
- no `auth:sanctum` middleware on any route
- `EnsureFrontendRequestsAreStateful` is commented out in `app/Http/Kernel.php:42`
- only residue is `use HasApiTokens` on `App\Models\User`

This is consistent with the archived legacy API surface. It is a removal candidate, not an upgrade candidate.

---

## 3. Laravel 13 upgrade feasibility (Phase 10)

Laravel **13.31.0** is current (13.0.0 released 2026-08-12). Feasibility was established by resolving a complete dependency set in a scratch copy of `composer.json`, leaving the project untouched.

**The graph resolves cleanly.**

| Package | Current | L13 target | Note |
| --- | --- | --- | --- |
| laravel/framework | 10.50.3 | **13.31.0** | |
| laravel/tinker | 2.x | **3.0.2** | new major line |
| spatie/laravel-permission | 5.11 | **6.25.0** | breaking: config + migration |
| phpunit/phpunit | 10 | 11.5.56 | |
| nunomaduro/collision | 7 | 8.9.5 | |
| laravel/sanctum | 3.3.3 | 4.3.3 *(or remove — F-4)* | |
| livewire/livewire | 3.8.8 | **unchanged** | already L13-compatible |
| maatwebsite/excel | 3.1.70 | **unchanged** | |
| laravel/ui | 4.6.3 | **unchanged** | |
| guzzle, phpspreadsheet | — | **unchanged** | |

**Blocker: `barryvdh/laravel-debugbar`.** Latest 3.16.5 caps at `illuminate/support ^10|^11|^12`. It is a `require-dev` package, so it can simply be removed.

> **Correction found during implementation.** The scratch resolution had `laravel/boost` removed from the manifest, which hid a second blocker: `laravel/boost ^1.8` also caps at `illuminate/support ^12`. It was resolved by upgrading to `laravel/boost ^2.8`. A dry run is only as complete as the manifest it is run against.

### Favourable conditions

- **PHP 8.4.24 already satisfies** Laravel 13's PHP 8.3+ requirement — no runtime migration.
- **No skeleton rewrite required.** `app/Http/Kernel.php`, `app/Console/Kernel.php` and `app/Exceptions/Handler.php` are present, and Laravel 11+ continues to support the legacy structure. This preserves the "no big-bang rewrite" rule.
- Only **9 direct production dependencies**, of which 4 need no change at all.

### Known code-level work

- **98 `/** @test */` annotations across 14 test files** — deprecated in PHPUnit 11, removed in PHPUnit 12. Must become `#[Test]` attributes.
- Spatie Permission 6 changes config keys and the permission-table migration.
- `protected $casts = []` remains valid; no forced move to the `casts()` method.

---

## 4. Scope and evidence limits

- Findings come from the local working tree at commit `b8da5e4`, PHP 8.4.24, local MySQL.
- Dependency resolution used `composer update --dry-run` in an isolated scratch directory; no packages were installed during the audit and `composer.json` was not modified.
- The Blade dead-code determination came from invoking the Blade compiler and inspecting compiled output, not from reading source alone.
- No production infrastructure, queue supervision, HTTP latency, or browser behaviour was examined.
- Performance baselines from the 2026-09-08 audit could not be re-measured: the database is empty.

---

## 5. Additional defects found during remediation

### F-5 — Unclosed Blade section in `rooms/create.blade.php` (Low)

`resources/views/app/rooms/create.blade.php` opens `@section('content')` on line 3. Its only `@endsection` sits on line 60 *inside* a `{{-- --}}` comment block, so the Blade compiler strips it and the section's output buffer is never closed. PHPUnit had been reporting this as the suite's one persistent "risky" test. Fixed by closing the live section.

### F-6 — Sidebar permission check can 500 the whole application (Low, not changed)

`resources/views/layouts/sidebar.blade.php` uses bare `@can('list employees')`. Spatie Permission throws `PermissionDoesNotExist` when the named permission row is absent, so a deployment whose permissions table has not been seeded returns HTTP 500 on **every** authenticated page, not a degraded menu.

The application's own policies already avoid this pattern by routing through `hasAnyExistingPermission()`. The sidebar does not. This was left unchanged because it is a behavioural decision rather than a defect in the upgrade's path, and seeded deployments are unaffected. Recorded for a decision.

---

## 6. Performance baseline (added 2026-09-09)

The original audit could not re-measure performance because the database was empty. `tests/Performance/RepresentativeVolumeBaselineTest.php` now seeds representative volume and measures the same pages at two table sizes, which is the only way to tell an expensive page apart from one whose cost *scales*.

| page | 101 employees / 9k timesheets | 1,101 employees / 99k timesheets | query growth |
| --- | --- | --- | ---: |
| `home1` | 21 q / 29.2 ms | 19 q / 119.1 ms | **-2** |
| `employees.index` | 18 q / 29.1 ms | 18 q / 37.2 ms | **0** |
| `time-sheets.index` | 30 q / 17.6 ms | 30 q / 32.8 ms | **0** |
| `reports.index` | 28 q / 6.1 ms | 28 q / 8.9 ms | **0** |

**Eleven times the rows produced no additional queries.** This settles the 2026-08 finding that employee relationship access grew from 4 queries for one employee to 61 for twenty: that N+1 class is gone and stays gone under load.

The one figure that does grow is `home1` wall time (29 → 119 ms) with *fewer* queries. That is aggregate work across the timesheet table, not an N+1, so it is a query-tuning question rather than an eager-loading one — the first place to look if the dashboard feels slow in production.

These are single-request measurements on a development machine, not a concurrency benchmark. They establish that cost is flat with respect to data size; they do not establish throughput under load, which is what an Octane decision would need.
