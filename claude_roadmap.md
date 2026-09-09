# Roadmap — zue

**Created:** 2026-09-10 · **Baseline:** [`claude_codebase_audit.md`](claude_codebase_audit.md)
**Status at start:** Laravel 13.31.0 · PHP 8.4.24 · 311 tests passing · PHPStan level 5 clean

> **This application is not in production yet.** That inverts the usual ordering. Structural
> refactoring is cheapest now — there is no traffic to break, no data to migrate, no
> rollback to rehearse. Deployment configuration and observability are therefore the *last*
> phase, done once, immediately before go-live, rather than a standing emergency.

---

## Ordering principle

```
Clear the ground   →  remove what nobody uses, so the map matches the territory
      ↓
Structure          →  fix what makes the code slow to change, while it is free to do so
      ↓
Data model         →  reshape storage before there is production data in it
      ↓
Testing depth      →  raise the safety net before it is load-bearing
      ↓
Frontend           →  remove external dependencies and dead weight
      ↓
Quality gates      →  make the standard automatic rather than remembered
      ↓
Production ready   →  configuration, observability, go-live
```

Each phase leaves the suite green and PHPStan clean. No phase is a big-bang rewrite.

---

## Phase 1 — Clear the ground

Dead code is not harmless: it is read, searched, and mistaken for something live. Every
item below was verified to have **zero references** before being listed.

| # | Task | Evidence |
| --- | --- | --- |
| 1.1 | Delete `CLinicalExamController` | Has no registered routes at all |
| 1.2 | Delete the three root debug scripts | `debug_appraisal.php`, `debug_finalize.php`, `verify_sync.php` |
| 1.3 | Delete `legacy-api/` | 52 files, 212 KB, serving zero registered API routes |
| 1.4 | Delete the 26 `Http/Resources` classes | API-era serializers with no API |
| 1.5 | Remove commented-out Blade blocks | 41 view files carry dead markup |
| 1.6 | Delete `IconTimeSheet` if still unreferenced | Only invoked from commented-out Blade |

**Done when:** the suite is green, `route:list` is unchanged, and nothing references the
removed files.

## Phase 2 — Structure

The audit's weakest score (SOLID 5/10). None of this causes bugs today; all of it makes
the code slow and risky to change.

| # | Task | Evidence |
| --- | --- | --- |
| 2.1 | Extract interfaces for the six core services and bind them in a service provider | Zero interfaces, zero container bindings today |
| 2.2 | Replace `app(Concrete::class)` service location with constructor injection | 32 such calls; only 8 of 27 controllers inject |
| 2.3 | Break up `TimeSheetService::getApprovalData` | 225 lines in one method |
| 2.4 | Break up `TimeSheetAuthorizationService` | 614 lines; `approve` 115, `approvalStages` 103 |
| 2.5 | Collapse the 18 duplicated CRUD controllers onto a shared base | ~2,000 lines of structural repetition |
| 2.6 | Replace inline `$request->validate()` with Form Requests | 30 files still validate inline |

**Done when:** no method exceeds ~60 lines, core services are resolved through interfaces,
and the CRUD controllers share one implementation.

## Phase 3 — Data model

Reshape storage now, while the tables are effectively empty.

| # | Task | Evidence |
| --- | --- | --- |
| 3.1 | Move the HR profile to a 1:1 `employee_details` table | `employees` is 87 columns and Eloquent does `SELECT *` |
| 3.2 | Review `Employee::$appends` | 8 appended accessors, 4 of which traverse relations |
| 3.3 | Split `Employee` (791 lines) once its profile moves | Largest class in the codebase |
| 3.4 | Decide whether `centers` duplicates `departments.code` | Cost centre and department code are identical in every imported row |

**Done when:** employee list queries no longer carry salaries and national IDs, and
`Employee` is under ~300 lines.

## Phase 4 — Testing depth

311 tests is a real safety net, but it has specific holes.

| # | Task | Evidence |
| --- | --- | --- |
| 4.1 | Policy unit tests for all 16 policies | Policies are the security boundary and are only tested through HTTP |
| 4.2 | A smoke test per role | No test asserts what a `timekeeper` or `campboss` can actually reach |
| 4.3 | Enable coverage measurement (PCOV) and record a baseline | Coverage is currently unknown, not low — unmeasured |
| 4.4 | Investigate the flaky `PerformanceBudgetTest` failure | Observed once, unreproducible across three full runs, unexplained |
| 4.5 | Browser tests for flight manifest, timesheet approval, appraisal | The three flows where a UI regression costs most |

**Done when:** every policy has direct tests and coverage is a number, not a guess.

## Phase 5 — Frontend

| # | Task | Evidence |
| --- | --- | --- |
| 5.1 | Bundle flatpickr; delete the 4 CDN references | External runtime dependency in 5 views; breaks under CSP or an outage |
| 5.2 | Move 162 inline `style=` attributes and 12 `<style>` blocks into the stylesheet | Unmaintainable and unthemeable |
| 5.3 | Move the 22 inline `<script>` blocks into modules | Same |
| 5.4 | Code-split or replace the editor bundle | 1,656 KB — six times the size of the main bundle |
| 5.5 | Decide the i18n story | 38 views hard-code Arabic while only an `en` locale exists |

**Done when:** no view references an external host, and the CSS/JS live in the build.

## Phase 6 — Quality gates

Make the standard automatic so it survives handover.

| # | Task | Evidence |
| --- | --- | --- |
| 6.1 | Reduce the PHPStan baseline from 212 | Grandfathered debt |
| 6.2 | Raise PHPStan to level 6, then 7 | Currently level 5 |
| 6.3 | Adopt model observers for audit logging and balance recalculation | Zero observers/events/listeners; every side effect hand-wired |
| 6.4 | Add CI running Pint, PHPStan and the suite on every push | Nothing enforces the gates except habit |

**Done when:** a red gate blocks a merge without anyone remembering to look.

## Phase 7 — Production readiness *(final phase, immediately before go-live)*

Everything here is configuration and operations. It is deliberately last because the
application is not yet deployed — but **none of it is optional at go-live**, and 7.1 is the
single highest-risk item in the whole document.

### 7a. Security configuration

| # | Task | Why |
| --- | --- | --- |
| 7.1 | `APP_ENV=production`, `APP_DEBUG=false`; confirm Ignition routes disappear | Ignition's `execute-solution` endpoint has a remote-code-execution history and is currently reachable |
| 7.2 | Rate-limit login, password reset and import endpoints | 1 of 241 routes is throttled; credential stuffing is unimpeded |
| 7.3 | Add security headers and a Content Security Policy | No headers today |
| 7.4 | Rotate `APP_KEY` and all credentials for production | The development key must not travel |
| 7.5 | Force HTTPS and secure/`SameSite` cookies | |

### 7b. Infrastructure

| # | Task | Why |
| --- | --- | --- |
| 7.6 | `QUEUE_CONNECTION=redis` with a supervised worker | `sync` today: backups and yearly aggregation block a user's HTTP request |
| 7.7 | Sessions and cache to Redis | File-based today — a second web server logs everyone out |
| 7.8 | Uploads to S3-compatible object storage | `local` today — uploads are lost on a second server |
| 7.9 | Verify `config:cache`, `route:cache`, `view:cache` and `npm run build` in the deploy | All four pass locally |

### 7c. Observability

| # | Task | Why |
| --- | --- | --- |
| 7.10 | Error tracking | There is currently no way to learn the application broke |
| 7.11 | Failed-job and queue-depth alerting | Backups fail silently otherwise |
| 7.12 | Log aggregation; set production log level and rotation | `LOG_CHANNEL=stack`, debug level |
| 7.13 | Slow-query and request-duration monitoring | The `home1` aggregate cost needs watching under real load |
| 7.14 | Uptime checks | |

### 7d. Go-live

| # | Task | Why |
| --- | --- | --- |
| 7.15 | Document and rehearse the rollback | Untested rollback is not a rollback |
| 7.16 | Restore drill against production-shaped data | Backups are verified; a full restore under real volume is not |
| 7.17 | Seed production permissions and roles; verify each role's access | Roles come from a SQL dump, not the seeder |
| 7.18 | Load test, then decide on Octane | Only meaningful once queue, cache and sessions are external |

---

## What "done" looks like

| Area | Now | Target |
| --- | :---: | :---: |
| Security | 8 | 9 |
| Authorization | 8 | 9 |
| Database | 7 | 8 |
| Performance | 7 | 8 |
| Testing | 7 | 9 |
| MVC / layering | 7 | 8 |
| Laravel practice | 7 | 8 |
| Code quality | 6 | 8 |
| DRY | 6 | 8 |
| Frontend | 6 | 8 |
| SOLID | 5 | 8 |
| Scalability | 5 | 8 |
| Operations | 5 | 8 |
| **Overall** | **6.5** | **8.3** |

Phases 1–2 alone move SOLID, DRY and code quality from 5–6 to roughly 8. Phase 7 moves
scalability and operations. Nothing here requires a rewrite.
