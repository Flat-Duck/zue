# Claude Checklist

Tracking for [`claude_plan.md`](claude_plan.md). Evidence in [`claude_audit.md`](claude_audit.md).

Legend: `[x]` done · `[~]` partial / follow-up needed · `[ ]` not started

**Status:** Stages 1–5 complete — application is on Laravel 13.31.0, suite green
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
| Test suite | 8 failed, 1 risky, 149 passed | **160 passed, 0 failed, 0 risky** |
| `composer audit` | 3 advisories | **0 advisories** |
| Direct dependencies | 17 | 15 |

Also fixed in passing:

- [x] `resources/views/app/rooms/create.blade.php` — unclosed `@section('content')` (its only `@endsection` was inside a Blade comment), which left an output buffer open

Noted, not changed:

- [ ] `layouts/sidebar.blade.php` uses bare `@can('list employees')`; Spatie throws `PermissionDoesNotExist` if the permission row is missing, so an unseeded deployment 500s on every page. The policies use `hasAnyExistingPermission` to avoid exactly this. Worth aligning.

---

## Carried over from `FIX_PLAN.MD` (still outstanding)

### Phase 0 / 2 — Test and deployment foundations

- [ ] Deployment smoke tests: login → dashboard → Livewire page → maintenance denial → authorised CRUD
- [ ] Record formal performance baselines (blocked: database is empty)

### Phase 6 — Reports and background work

- [ ] Failed-job / retry / idempotency tests for large jobs
- [ ] Memory tests for large imports/exports/reports
- [~] Large exports remain synchronous, bounded to 5,000 rows — reassess with real data volume

### Phase 7 — Backups

- [ ] Keep the previous verified backup until the new backup passes verification
- [ ] Tests for partial table failure, storage failure, corrupted backup, restore failure, concurrent requests

### Phase 8 — Appraisal lifecycle

- [ ] Confirm and encode required/text/partial scoring rules (currently undecided — do not invent)
- [ ] Decide re-finalization behaviour: replace, preserve, or prohibit

### Phase 10 — Production operations

- [ ] Sensitive-data redaction in logs
- [ ] Structured audit events (role changes, impersonation, medical edits, imports, approvals, restores, backup failures)
- [~] Production env, storage, logging, monitoring remain deployment-specific

### Phase 11 — Static analysis

- [ ] Install PHPStan/Larastan and establish a realistic baseline
- [ ] Enforce on new/touched code; reduce baseline gradually

### Phase 12 — Octane

- [ ] Benchmark PHP-FPM with realistic workloads (blocked: needs representative data)
- [ ] Adopt Octane only if measurements justify it
