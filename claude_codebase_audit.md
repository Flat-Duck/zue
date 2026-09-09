# Full codebase audit — zue

**Date:** 2026-09-10 · **Laravel** 13.31.0 · **PHP** 8.4.24
**Suite:** 311 tests / 1,436 assertions passing · **PHPStan** level 5 clean (212-item baseline)
**Method:** every figure below was measured against the working tree, not estimated.

---

## Scores at a glance

| # | Area | Score | One-line verdict |
| --- | --- | :---: | --- |
| 1 | Security | **8**/10 | Genuinely good now; residual risk is deployment config, not code |
| 2 | Authorization | **8**/10 | 16 policies, no permissive stubs, Livewire actions gated server-side |
| 3 | Database | **7**/10 | 66 FKs, 148 indexes, real constraints; one very wide table |
| 4 | Performance | **7**/10 | Measured flat at 11× data volume — the N+1 class is gone |
| 5 | Testing | **7**/10 | 311 tests, strong on services; no policy or browser tests |
| 6 | MVC / layering | **7**/10 | Clean layers, services extracted; some logic still in controllers |
| 7 | Laravel practice | **7**/10 | Idiomatic; no events/observers/notifications at all |
| 8 | Code quality | **6**/10 | Pint + PHPStan enforced, but god objects and dead code remain |
| 9 | DRY | **6**/10 | 18 near-identical CRUD controllers; good component reuse elsewhere |
| 10 | Frontend | **6**/10 | Tabler now correct; 1.65 MB editor bundle, 4 live CDN links |
| 11 | SOLID | **5**/10 | Zero interfaces, zero container bindings, concrete dependencies |
| 12 | Scalability | **5**/10 | Correct and bounded, but single-server by configuration |
| 13 | Operations | **5**/10 | Backups verified; no monitoring, sync queue, file sessions |

**Weighted overall: 6.5 / 10** — a sound, well-tested Laravel application whose remaining
weaknesses are structural (SOLID, duplication) and operational (single-server, no
observability) rather than correctness or security.

---

## Codebase shape

| Layer | Files | Lines |
| --- | ---: | ---: |
| Controllers | 41 | 4,814 |
| Models | 45 | 3,130 |
| Services | 16 | 3,426 |
| Form Requests | 40 | 1,403 |
| Policies | 16 | 1,110 |
| Livewire | 10 | 1,061 |
| Jobs / Imports / Exports | 11 | 1,084 |
| **app total** | **233** | **17,917** |
| Blade views | 187 | 15,616 |
| Tests | 59 | 7,289 |
| Migrations | 78 | 3,031 |

Test-to-app ratio is 0.41:1 — healthy for a line-of-business application.

---

## 1. Security — 8/10

**Evidence of strength**

- 225 of 241 routes sit behind `Authenticate`. The 16 that do not are Livewire's own
  endpoints, password reset, logout, and Ignition's debug routes.
- **No policy contains a permissive `return true` stub.** All 16 check a named permission.
- 49 routes carry gate middleware (`manage-appraisals` 25, `manage-clinic` 18,
  `manage-operations` 5, `maintenance` 1).
- `composer audit` and `npm audit --omit=dev`: **zero advisories**.
- Audit trail records restores, backup deletion and download, role changes, timesheet
  approvals, clinic writes and impersonation — with credential, medical and identity
  fields redacted at any nesting depth, and impersonated actions attributed to the
  real operator.
- No `env()` calls outside config. No mass-assignment holes: 39 models declare
  `$fillable`, none use `$guarded = []`.
- 22 raw-SQL sites exist but are confined to the backup service, a sync command and the
  maintenance controller, and use identifier allow-lists and quoting.

**Residual risk**

1. **`APP_DEBUG=true` and Ignition routes.** In production these must be off; Ignition's
   `execute-solution` endpoint has a history of remote code execution. Deployment concern,
   not code, but it is the single highest-impact item.
2. **Rate limiting is effectively absent** — one route out of 241 is throttled. Login is
   not, so credential stuffing is unimpeded.
3. **No CSP or security headers.**
4. Three debug scripts sit in the project root (`debug_appraisal.php`, `debug_finalize.php`,
   `verify_sync.php`). Not web-reachable — the document root is `public/` — but they are
   cruft that reads as risk.

## 2. Authorization — 8/10

Every policy is permission-backed, Livewire mutations authorize on the server rather than
by hiding buttons, and cross-record access is scoped (a booking id from another flight is
refused, not acted on). The management-scope model was unified onto the V2 policy store.

**Gaps:** `HomeController` has no authorization beyond authentication, which is probably
right for a dashboard but is unstated. There are no policy unit tests — policies are only
exercised indirectly through HTTP tests.

## 3. Database — 7/10

54 tables, **66 foreign keys**, **148 indexes**, 78 migrations. Business rules are enforced
in the schema (unique `employee_id + day` on timesheets, unique traveller per flight leg),
not just in code.

**Concerns**

- `employees` is **87 columns**. Eloquent issues `SELECT *`, so every employee query pulls
  salaries, national IDs and bank details into memory even on pages that need a name. A 1:1
  `employee_details` table would keep the hot path lean and sensitive data out of casual
  queries.
- `Employee` appends 8 accessors, four of which traverse relations
  (`administration_name` → `department → administration`). Any serialization to array or
  JSON — which is what Livewire does with public model properties — can trigger relation
  loads per row.

## 4. Performance — 7/10

The one measurement that matters was taken at two data volumes:

| page | 101 employees / 9k timesheets | 1,101 / 99k | query growth |
| --- | --- | --- | ---: |
| `home1` | 21 q / 29 ms | 19 q / 119 ms | **−2** |
| `employees.index` | 18 q / 29 ms | 18 q / 37 ms | **0** |
| `time-sheets.index` | 30 q / 18 ms | 30 q / 33 ms | **0** |
| `reports.index` | 28 q / 6 ms | 28 q / 9 ms | **0** |

Eleven times the rows, no additional queries — the N+1 class is genuinely eliminated, and
query-budget tests keep it that way. Reports are capped, imports chunked.

The outlier is `home1`: wall time grew 4× on *fewer* queries. That is aggregate work across
the timesheet table — a query-tuning question, and the first place to look if the dashboard
feels slow.

## 5. Testing — 7/10

311 tests, 1,436 assertions, ~40 s. Reference coverage: Imports 100%, Models 76%,
Services 75%, Livewire 60%, Jobs 40%.

**Strengths:** authorization denial paths are tested as first-class cases; concurrency
(double-booking, double-approval) is covered; several tests were mutation-checked to prove
they fail when the guard is removed.

**Gaps:** no policy unit tests, no browser/E2E tests, no accessibility tests, and coverage
percentage is unmeasured (no Xdebug/PCOV). One flaky failure was observed in
`PerformanceBudgetTest` and could not be reproduced across three subsequent full runs — it
remains unexplained.

## 6. MVC and layering — 7/10

Layering is clean: controllers → form requests → services → Eloquent → Blade. Business
logic lives in 16 services (3,426 lines), not controllers. Timesheet mutation, appraisal
finalization, flight dispatch and backups are all centralised behind services with
transactions and row locks.

**Gaps:** 30 files still call `$request->validate()` inline instead of a Form Request, and
the largest methods are very large — `TimeSheetService::getApprovalData` is **225 lines**.

## 7. Laravel practice — 7/10

Named routes, policies, Form Requests, factories, queued jobs with retry/backoff/uniqueness,
scheduled commands, chunked imports, idempotent seeders. Configuration is clean.

**Notable absence:** **zero observers, zero events, zero listeners, zero notifications.**
Every side effect is called inline. That is workable at this size but means audit logging,
balance recalculation and status changes are wired by hand at each call site rather than
reacting to model lifecycle events.

## 8. Code quality — 6/10

Pint runs on every change; PHPStan level 5 passes. The **212-item baseline is acknowledged
debt** — new code is clean, legacy code is grandfathered.

**Problems**

- God objects: `Employee` **791 lines** with 12 accessors, `TimeSheetAuthorizationService`
  **614 lines**.
- Long methods: 225, 174, 153, 147, 146 lines at the top of the list.
- Dead code: `CLinicalExamController` **has no routes at all** — same pattern as the
  `RunController` already removed. 26 `Http/Resources` classes and a 52-file `legacy-api`
  directory serve zero registered API routes.
- 41 view files contain commented-out Blade blocks.

## 9. DRY — 6/10

**Good:** `x-inputs` components used 141 times; `@lang`/`__()` used 477 times; the employee
profile is defined once and drives the show page, both forms and the validation rules.

**Bad:** **18 controllers implement a near-identical seven-method CRUD shape.** That is
roughly 2,000 lines of structural repetition that a base controller or a generator-backed
trait would collapse.

## 10. Frontend — 6/10

Tabler is now correctly wired: icons render, Bootstrap 5 comes from Tabler's own bundle,
jQuery and Bootstrap 4 are gone, and all 58 icons used resolve against the font.

| Asset | Size |
| --- | ---: |
| `app.css` | 892 KB (120 KB gzip) |
| `app.js` | 285 KB (98 KB gzip) |
| `editor.js` | **1,656 KB** (540 KB gzip) |

**Problems**

- The editor bundle is 1.65 MB. It is already lazy-loaded to editor pages only, but it
  dwarfs everything else.
- **4 live CDN references** (flatpickr CSS and JS) in five views. These are a runtime
  dependency on an external host — an outage, a firewall or a CSP breaks date pickers.
  Everything else is bundled; these should be too.
- 162 inline `style=` attributes, 12 `<style>` blocks, 22 inline `<script>` tags.
- Only 6 `x-data` usages — Alpine is barely adopted despite shipping with Livewire.
- 38 view files contain hard-coded Arabic while only the `en` locale exists, so the UI is
  bilingual by accident rather than by translation.

## 11. SOLID — 5/10

The weakest dimension, and the most structural.

- **Zero interfaces. Zero container bindings.** Every service is a concrete class resolved
  directly, so nothing can be substituted, decorated or faked without touching call sites.
- **Dependency inversion is absent** — high-level code depends on concretions throughout.
  32 `app(Concrete::class)` service-location calls sit inside methods rather than being
  injected; only 8 of 27 controllers use constructor injection.
- **Single responsibility** is violated by the two god objects above.
- **Open/closed:** behaviour is selected with `match`/`if` on strings. Adding an approval
  level or appraisal rule means editing existing methods.

This is not urgent — it does not cause bugs today — but it is what makes the code hard to
change, and it will get worse as features accumulate.

## 12. Scalability — 5/10

Cost is flat with respect to data size (section 4), which is the hard part and it is done.
What blocks scaling is configuration:

- `QUEUE_CONNECTION=sync` — jobs run inside the web request. Backups, yearly appraisal
  aggregation and large exports all block a user's HTTP request.
- `SESSION_DRIVER=file`, `CACHE_DRIVER=file`, `FILESYSTEM_DISK=local` — **a second web
  server would log users out and lose uploads.** Horizontal scaling is impossible until
  these move to Redis and object storage.

## 13. Operations — 5/10

Backups are hardened, verified against a disposable database, retention protects the last
verified copy, and failures are audited. Restores have been drilled.

**Missing:** no monitoring or alerting of any kind, no error tracking, no queue worker
supervision, no log aggregation, no uptime checks, no documented rollback beyond the
upgrade note. `APP_DEBUG=true` and `LOG_CHANNEL=stack` are local values that must not reach
production.

---

# Roadmap

Ordered by *risk removed per hour spent*, not by how interesting the work is.

## Now — before the next production deploy (hours)

| # | Action | Why |
| --- | --- | --- |
| 1 | `APP_ENV=production`, `APP_DEBUG=false`, verify Ignition routes are gone | Debug mode exposes an RCE-capable endpoint and full stack traces |
| 2 | Rate-limit login and password reset | One throttled route out of 241; credential stuffing is currently free |
| 3 | `QUEUE_CONNECTION=redis` (or database) + a supervised worker | Backups and yearly aggregation currently block a web request |
| 4 | Delete `CLinicalExamController`, the three root debug scripts, `legacy-api/`, and the 26 unused `Http/Resources` | Dead code that reads as attack surface and confuses newcomers |

## Next — 2 to 4 weeks

| # | Action | Why |
| --- | --- | --- |
| 5 | Move sessions and cache to Redis; uploads to S3-compatible storage | The only thing preventing a second web server |
| 6 | Add error tracking and queue/failed-job alerting | There is currently no way to know the application is broken |
| 7 | Bundle flatpickr; delete the 4 CDN references | Removes an external runtime dependency and a CSP blocker |
| 8 | Add security headers and a CSP | Cheap, broad hardening |
| 9 | Policy unit tests + a smoke test per role | Policies are the security boundary and are only tested indirectly |

## Then — 1 to 2 months

| # | Action | Why |
| --- | --- | --- |
| 10 | Split `Employee` (791 lines): move the HR profile to a 1:1 `employee_details` table | Fixes the 87-column `SELECT *` and keeps salaries and IDs out of casual queries |
| 11 | Break up `TimeSheetService::getApprovalData` (225 lines) and `TimeSheetAuthorizationService` (614 lines) | The two hardest places in the codebase to change safely |
| 12 | Introduce interfaces + container bindings for the six core services | Raises SOLID from 5 to ~8 and makes the services testable in isolation |
| 13 | Collapse the 18 duplicated CRUD controllers onto a shared base | ~2,000 lines of repetition removed |
| 14 | Replace inline `$request->validate()` in 30 files with Form Requests | Consistency; validation becomes reviewable in one place |

## Later — opportunistic

| # | Action | Why |
| --- | --- | --- |
| 15 | Adopt model observers for audit logging and balance recalculation | Removes hand-wiring at each call site |
| 16 | Reduce the PHPStan baseline from 212, raise to level 6 | Converts grandfathered debt into real coverage |
| 17 | Decide the i18n story: translate the 38 Arabic view files, or commit to Arabic and add `lang/ar` | Currently bilingual by accident |
| 18 | Code-split or replace the 1.65 MB editor bundle | Largest single asset by 6× |
| 19 | Browser tests for the flight manifest, timesheet approval and appraisal flows | The three flows where a UI regression is most expensive |
| 20 | Re-measure, then decide on Octane | Only after queue, cache and sessions are externalised — Octane cannot fix I/O design |

---

## Conclusion

**This is a healthy codebase.** The dangerous problems — unauthorised access, silent data
loss, unbounded reports, N+1 growth, unrecoverable backups — have been found and fixed, and
there are 311 tests holding them fixed. Security and authorization are the *strongest*
dimensions, which is unusual and worth protecting.

What remains splits cleanly in two:

- **Operational readiness (fix now).** The application is correct but configured as a
  single-server, no-observability deployment with debug mode on. Items 1–6 are mostly
  configuration and would take days, not weeks — and they are what stands between "works"
  and "safe to run".
- **Structural debt (fix deliberately).** Zero interfaces, two god objects and eighteen
  duplicated controllers do not cause bugs today; they cause *slowness* tomorrow. Address
  them as you touch the code, not in a big-bang refactor.

The single highest-value item is **#1** — `APP_DEBUG=false` in production. Everything else
is improvement; that one is exposure.
