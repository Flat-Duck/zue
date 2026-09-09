# Roadmap checklist — zue

Tracking for [`claude_roadmap.md`](claude_roadmap.md). Evidence in
[`claude_codebase_audit.md`](claude_codebase_audit.md).

> Not to be confused with [`claude_check_list.md`](claude_check_list.md), which tracks the
> earlier remediation and Laravel 13 upgrade programme. That work is complete; this file
> tracks what comes next.

Legend: `[x]` done · `[~]` partial / follow-up needed · `[ ]` not started

**Status:** Phase 1 complete; Phase 2 next
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

- [ ] 2.1 Extract interfaces for the six core services; bind them in a service provider
- [ ] 2.2 Replace the 32 `app(Concrete::class)` calls with constructor injection
- [ ] 2.3 Break up `TimeSheetService::getApprovalData` (225 lines)
- [ ] 2.4 Break up `TimeSheetAuthorizationService` (614 lines; `approve` 115, `approvalStages` 103)
- [ ] 2.5 Collapse the 18 duplicated CRUD controllers onto a shared base
- [ ] 2.6 Replace inline `$request->validate()` in 30 files with Form Requests
- [ ] No method over ~60 lines; core services resolved through interfaces

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
