# Claude Checklist

Tracking for [`claude_plan.md`](claude_plan.md). Evidence in [`claude_audit.md`](claude_audit.md).

Legend: `[x]` done · `[~]` partial / follow-up needed · `[ ]` not started

**Status:** Stages 1–6 complete except the deferred appraisal decision — Laravel 13.31.0, 213 tests green, PHPStan level 5 clean, performance baseline measured at representative volume
**Last updated:** 2026-09-09

---

## Stage 1 — Safety foundation

- [x] Add `.env.testing` targeting a dedicated test database
- [x] Create the `zue_testing` MySQL database
- [x] Prove the suite no longer touches the `zue` database
- [x] Full-suite baseline recorded after isolation

## Stage 2 — Fix the live defect (audit F-2)

- [x] Repoint `show.blade.php:96` to `time-sheets.fill` with the timesheet's employee
- [x] Regression test: show page renders for a user holding `fill timesheets`
- [x] Confirm the four dead references stay dead (no behaviour change)

## Stage 3 — Restore the regression gate (audit F-3)

- [x] Rewrite `TimeSheetControllerTest` against current routes and view variables
- [x] Fix `ExampleTest` to assert the real unauthenticated redirect
- [x] Full suite green

## Stage 4 — Dependency cleanup

- [x] Remove `barryvdh/laravel-debugbar` (dev-only blocker)
- [x] Upgrade `laravel/boost` 1.8 → 2.8 (second blocker, found during the upgrade)
- [x] Remove `laravel/sanctum` (zero live usage — audit F-4)
- [x] Remove `config/sanctum.php` and the `HasApiTokens` trait
- [x] Retain the `personal_access_tokens` migration for history consistency
- [x] Suite green after removals

## Stage 5 — Laravel 13 upgrade

- [x] Group 1 — `phpunit` 10 → 11, `collision` 7 → 8
- [x] Convert 99 `/** @test */` docblocks (14 files) to `#[Test]` attributes — test count unchanged
- [x] Group 2 — `laravel/tinker` 2 → 3
- [x] Group 3 — `spatie/laravel-permission` 5 → 6 (config + migration)
- [x] Group 4 — `laravel/framework` 10 → 13.31.0, `php` `^8.1` → `^8.3`
- [x] Compatibility sweep for removed/changed framework APIs
- [x] Preserve legacy skeleton (no Laravel 11+ skeleton adoption)
- [x] Suite green on Laravel 13
- [x] `route:cache`, `config:cache`, `view:cache` all succeed
- [x] `npm run build` succeeds
- [x] Rollback procedure documented (see `claude_plan.md` § Rollback)

---

## Outcome

| Signal | Before | After |
| --- | --- | --- |
| Laravel | 10.50.3 | **13.31.0** |
| Test suite | 8 failed, 1 risky, 149 passed | **213 passed, 0 failed, 0 risky** |
| `composer audit` | 3 advisories | **0 advisories** |
| Direct dependencies | 17 | 15 |

Also fixed in passing:

- [x] **Rooms import returned a 500 on success.** Route `rr` (the target of the rooms create form) imported the file and then rendered the timesheet approval view with four undefined variables. It now validates the upload and redirects back with a success message, matching the other import endpoints. Found by PHPStan; covered by `RoomImportRouteTest.php`.


- [x] `resources/views/app/rooms/create.blade.php` — unclosed `@section('content')` (its only `@endsection` was inside a Blade comment), which left an output buffer open

Noted, not changed:

- [x] **Removed** `app/Http/Controllers/RunController.php` — nothing routed to it and the view it returned (`app.run.index`) did not exist. Verified unreferenced before deletion.
- [x] **Fixed.** An unseeded permissions table used to return HTTP 500 on every authenticated page: the sidebar runs a policy check per menu section, and the 12 policies called Spatie's strict `hasPermissionTo()`, which throws `PermissionDoesNotExist` for an unknown permission name. All 72 policy call sites now use Spatie's non-throwing `checkPermissionTo()` — identical result when the permission exists, `false` instead of an exception when it does not. Covered by `tests/Feature/UnseededDeploymentTest.php`.

---

## Carried over from `FIX_PLAN.MD` (still outstanding)

### Phase 0 / 2 — Test and deployment foundations

- [x] Deployment smoke tests: login → dashboard → Livewire page → maintenance denial → authorised CRUD (`tests/Feature/DeploymentSmokeTest.php`, 11 tests)
- [x] Record formal performance baselines — no longer blocked; `tests/Performance/RepresentativeVolumeBaselineTest.php` seeds representative volume and measures. Excluded from the default suite; run with `php artisan test --group=performance`.

  **Baseline (2026-09-09).** The question is whether page cost grows with table size:

  | page | 101 employees / 9k timesheets | 1,101 employees / 99k timesheets | query growth |
  | --- | --- | --- | ---: |
  | `home1` | 21 q / 29.2 ms | 19 q / 119.1 ms | **-2** |
  | `employees.index` | 18 q / 29.1 ms | 18 q / 37.2 ms | **0** |
  | `time-sheets.index` | 30 q / 17.6 ms | 30 q / 32.8 ms | **0** |
  | `reports.index` | 28 q / 6.1 ms | 28 q / 8.9 ms | **0** |

  Eleven times the rows, no additional queries. This confirms the Phase 5 N+1 work holds at scale, and directly answers the 2026-09-08 audit finding that employee relationship access grew from 4 queries for one employee to 61 for twenty. `home1` wall time still grows (29 → 119 ms) because the dashboard aggregates across the timesheet table; that is aggregate cost, not an N+1, and is the next thing to look at if the dashboard feels slow.

### Phase 6 — Reports and background work

- [x] Failed-job / retry / idempotency tests for large jobs (`tests/Feature/QueuedJobReliabilityTest.php`, 9 tests)
- [x] `GenerateYearlyAppraisalsJob` given timeout, tries, escalating backoff, and `ShouldBeUnique` overlap protection
- [x] Memory and bounds tests for large imports/exports/reports (`tests/Feature/LargeDatasetBoundsTest.php`) — every import is asserted to read in chunks, the balance report is proven to cap at 5,000 of 5,200 rows, memory stays within budget, and eager loading is pinned by a query-count assertion
- [~] Large exports remain synchronous, bounded to 5,000 rows — reassess with real data volume

### Phase 7 — Backups

- [x] Keep the previous verified backup until the new backup passes verification — retention now protects the newest verified backup, and a file failing verification is discarded instead of occupying a retention slot
- [x] Tests for corrupted backup, empty/missing stored file, invalid type, and retention ordering (`tests/Feature/BackupRetentionTest.php`, 7 tests)
- [x] Concurrent-request refusal, lock release after failure, partial table failure, and allowlist rejection (`BackupRetentionTest.php`, now 11 tests)

### Phase 8 — Appraisal lifecycle

- [x] Required/text/numeric-bound/closed-period scoring rules are covered by `AppraisalScoringBusinessRulesTest` — on inspection these were already decided and encoded, not open
- [ ] **DEFERRED — awaiting owner's decision (2026-09-09).** Do not change this behaviour until the rule is chosen. Re-finalization is **characterized, not decided** (`tests/Feature/AppraisalRefinalizationTest.php`). Current behaviour:
  - Finalization averages every `submitted` review, then locks them.
  - Re-finalizing with no new submissions **preserves** the original result and timestamp.
  - **Open question:** a review submitted *after* finalization is averaged **alone** (the earlier reviews are locked out), replacing rather than revising the result. Needs a decision: replace / combine / reject.

### Phase 10 — Production operations

- [x] Sensitive-data redaction — `AuditLogger` redacts credentials, tokens, medical fields (`diagnosis`, `prescription`) and identity numbers at any nesting depth; `Handler::$dontFlash` extended so medical and identity inputs are never flashed back into the session
- [x] Structured audit events — dedicated `audit` log channel (daily, 365-day retention, separate file) plus `App\Services\AuditLogger`, wired into: database restore + restore failure, backup deletion, backup download, user creation, role changes (only when they actually change), and clinic appointment writes. Covered by `tests/Feature/AuditLoggingTest.php` (7 tests).
- [x] Timesheet approvals are audited on both the v2 and legacy paths, recording month, year, level and rows updated.
- [~] Impersonation is not audited because no impersonation feature exists.
- [~] Production env, storage, logging, monitoring remain deployment-specific

### Phase 11 — Static analysis

- [x] Install PHPStan/Larastan and establish a realistic baseline — `larastan/larastan ^3.11`, level 5 over `app`/`database`/`routes`, baseline of 212 pre-existing findings in `phpstan-baseline.neon`; run with `composer analyse`
- [~] Enforce on new/touched code; reduce baseline gradually — analysis is clean against the baseline, so new findings fail immediately. Baseline reduction is ongoing (220 → 212 so far).

### Phase 12 — Octane

- [~] Benchmark PHP-FPM with realistic workloads — request-level baselines now exist at representative volume (above). A full FPM load benchmark under concurrency is still outstanding.
- [ ] Adopt Octane only if measurements justify it
