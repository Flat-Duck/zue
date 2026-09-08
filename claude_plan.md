# Claude Plan — Remediation and Laravel 13 Upgrade

**Companion documents:** [`claude_audit.md`](claude_audit.md) (evidence) · [`claude_check_list.md`](claude_check_list.md) (tracking)

This plan implements the findings in `claude_audit.md`. It follows the global rules of `FIX_PLAN.MD`: no big-bang rewrite, no API layer, preserve MVC + Blade + Livewire, preserve existing business behaviour, measure before optimising, and enforce security server-side.

---

## Guiding constraints

1. **Nothing runs before test isolation.** An unisolated suite is destructive; every later step depends on being able to run tests freely.
2. **Green suite before framework upgrade.** A failing suite cannot detect upgrade regressions. The 8 known failures must be resolved first so that any *new* failure during the upgrade is attributable to the upgrade.
3. **Upgrade in controlled groups**, not one uncontrolled `composer update`.
4. **Preserve the legacy skeleton.** Laravel 11+ supports `app/Http/Kernel.php` and friends. Do not adopt the new application skeleton merely because it exists.
5. **Remove rather than upgrade** anything proven unused.

---

## Stage 1 — Safety foundation

**Goal:** make the test suite safe and honest.

| Step | Action | Rationale |
| --- | --- | --- |
| 1.1 | Add `.env.testing` pointing at a dedicated `zue_testing` MySQL database | Isolates tests from the working database (audit F-1) |
| 1.2 | Keep MySQL for tests rather than switching to SQLite | The app uses MySQL-specific behaviour (backup/restore service, raw SQL); SQLite would change semantics and mask defects |
| 1.3 | Create the `zue_testing` database and verify the suite targets it | Proof, not assumption |

**Definition of done:** running the full suite leaves the `zue` database untouched.

## Stage 2 — Fix the live defect

**Goal:** close audit finding F-2.

`resources/views/app/time_sheets/show.blade.php:96` references the deleted `time-sheets.create` route.

The deleted route took no parameter; its replacement `time-sheets.fill` requires an `{employee}`. **Business-behaviour decision, documented rather than invented:** on the show page the timesheet's own employee is the only sensible subject, so the button targets `route('time-sheets.fill', $timeSheet->employee_id)` — preserving the intent (create a timesheet for this employee) without inventing a new rule. The `@can('create', ...)` guard is retained unchanged.

**Definition of done:** the show page renders for a user holding `fill timesheets`.

## Stage 3 — Restore the suite as a regression gate

**Goal:** close audit finding F-3, so the upgrade has a working safety net.

- Rewrite `TimeSheetControllerTest` against the routes and view variables the controller *actually* uses today (`time-sheets.fill` / `time-sheets.revise`; `employees` not `timeSheets`). Preserve the tests' original intent — index/show/store/update/delete coverage — rather than deleting inconvenient cases.
- Fix `ExampleTest` to assert the real unauthenticated behaviour (redirect to login).

**Definition of done:** full suite green.

## Stage 4 — Dependency cleanup

**Goal:** remove blockers and dead weight before touching the framework.

| Package | Action | Reason |
| --- | --- | --- |
| `barryvdh/laravel-debugbar` | **Remove** | L13 blocker; dev-only; caps at `illuminate/support ^12` |
| `laravel/boost` | **Upgrade 1.8 → 2.8** | Second L13 blocker, surfaced only when the framework bump was attempted |
| `laravel/sanctum` | **Remove** | Zero live usage (audit F-4); the API surface is archived |

Sanctum removal covers the package, `config/sanctum.php`, and the `HasApiTokens` trait on `App\Models\User`. The `personal_access_tokens` migration is **retained** so existing deployments' migration history stays consistent.

**Definition of done:** suite still green with both packages gone.

## Stage 5 — Laravel 13 upgrade

Performed in controlled groups, running the suite after each.

1. **Test tooling:** `phpunit/phpunit` 10 → 11, `nunomaduro/collision` 7 → 8.
2. **Annotations:** convert 98 `/** @test */` docblocks across 14 files to `#[Test]` attributes (deprecated in PHPUnit 11, removed in 12).
3. **Ecosystem:** `laravel/tinker` 2 → 3, `spatie/laravel-permission` 5 → 6 (config + migration changes).
4. **Framework:** `laravel/framework` 10 → 13.31.0, `php` constraint `^8.1` → `^8.3`.
5. **Compatibility sweep:** removed/renamed APIs, validation, Eloquent, queue, middleware, auth, filesystem, exception handling, testing APIs, Blade, Livewire integration.

**Explicitly out of scope:** adopting the Laravel 11+ application skeleton, Laravel 13 AI/API features, or any architectural change. The application stays MVC + Blade + Livewire.

**Definition of done:** framework on 13.31.0, suite green, `route:cache` / `config:cache` / `view:cache` all succeed, production assets build.

## Stage 6 — Resume the original phase plan

With the upgrade stable, return to the outstanding items from `FIX_PLAN.MD` — tracked in `claude_check_list.md`. Highest value first:

1. Deployment smoke tests (login → dashboard → Livewire page → denial → authorised CRUD).
2. Backup: retain the previous verified backup until the new one verifies; add failure-path tests.
3. Appraisal: decide and encode the re-finalization rule and required/text-item scoring rules.
4. Failed-job / retry / idempotency and memory tests for large jobs.
5. Structured audit events; sensitive-data redaction.
6. PHPStan/Larastan baseline.
7. Re-measure performance baselines once representative data exists, then reassess Octane.

---

## Risks

| Risk | Mitigation |
| --- | --- |
| Spatie Permission 6 migration/config drift breaks authorization | Upgrade in its own group; the authorization test suite is the gate |
| Livewire 3 behaviour shifts under L13 | Livewire version is unchanged (3.8.8); existing Livewire tests must stay green |
| Rewritten tests quietly weaken coverage | Rewrite to the original intent; never delete a case to make the suite pass |
| Performance regressions invisible | Baselines cannot be re-measured while the database is empty; deferred and flagged, not faked |

---

## Rollback

The upgrade touches dependencies and four source files. To revert:

1. **Dependencies** — restore the pre-upgrade manifests and reinstall:
   ```bash
   git checkout HEAD -- composer.json composer.lock && composer install
   ```
   Verified copies of the Laravel 10 manifests were also kept outside the repo during the upgrade.
2. **Source** — `git checkout HEAD -- app resources tests database config`.
3. **Database** — no schema change was made. Spatie Permission 6 produces byte-identical pivot tables (`permission_id`, `role_id`, `model_type`, `model_id`), verified against the migrated schema, so no data migration or rollback is required.
4. **Caches** — `php artisan optimize:clear` after either direction.

The only irreversible-looking step, removing `laravel/sanctum`, is safe to reverse with `composer require laravel/sanctum` plus restoring the `HasApiTokens` trait; the `personal_access_tokens` table and its migration were deliberately left in place.

---

## Outcome

Stages 1–5 are complete.

| Signal | Before | After |
| --- | --- | --- |
| Laravel | 10.50.3 | **13.31.0** |
| PHP constraint | `^8.1` | `^8.3` (runtime already 8.4.24) |
| Test suite | 8 failed, 1 risky, 149 passed | **160 passed, 0 failed, 0 risky** |
| `composer audit` | 3 advisories | **0 advisories** |

The application remains MVC + Blade + Livewire on the legacy skeleton. No API layer, no repository layer, no skeleton migration, no new abstractions.
