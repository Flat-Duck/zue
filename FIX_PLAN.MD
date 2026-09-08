## Fix implementation plan

### Phase 1 — Contain critical security risks

1. Add authorization policies for maintenance operations:
   - database export
   - SQL import
   - restore
   - backup download/delete
   - backup settings

2. Restrict or disable public registration.
   - Prefer administrator-created users or verified invitations.
   - Add tests proving ordinary authenticated users cannot access maintenance or user-management actions.

3. Secure Livewire actions:
   - `TimeTable`
   - `TimeSheetApprove`
   - `ClinicEmployeeInfo`
   - Validate all public inputs.
   - Re-check employee, appointment, scope, role, and ownership permissions inside every action.

4. Secure appraisal actions:
   - review creation/update/submission
   - version and item management
   - finalization
   - employee, manager, and HR approval
   - employee form assignment

5. Protect auxiliary bulk operations:
   - employee archive/import
   - reports
   - user imports
   - flight approval
   - room imports
   - operations archive/unarchive actions

Validation:

- Add authorization denial tests for ordinary users, unrelated managers, revoked managers, and invalid Livewire parameters.
- Run the affected feature and Livewire tests.

### Phase 2 — Repair test and deployment foundations

1. Fix `UserFactory` so every generated user receives a unique valid number.
2. Fix `EmployeeFactory` defaults:
   - active employee by default
   - valid schedule
   - controlled user linkage
3. Separate test database, cache, route cache, and compiled views from local runtime state.
4. Rebuild route and view caches using the installed Livewire version.
5. Add a deployment smoke test for:
   - login
   - dashboard
   - one Livewire page
   - one maintenance denial
   - one authorized CRUD action

Validation:

- Run the previously failing employee controller tests.
- Run the full isolated test suite after the targeted tests pass.

### Phase 3 — Unify authorization and identity

1. Choose one authoritative management-scope store.
   - Preferred direction: make the active V2 policy store authoritative.
   - Migrate the management-scope UI to write that store.
   - Keep a temporary compatibility path only while parity is verified.

2. Define canonical user/employee identity:
   - stable user primary keys
   - explicit `employees.user_id` relationship
   - consistent signer and approver foreign keys
   - no mutable primary key based on employee number

3. Reconcile existing identity data before changing constraints:
   - users
   - employees
   - signatures
   - timesheet approvers
   - role assignments
   - Sanctum tokens

4. Replace destructive user/employee cascades where historical HR data must be retained.

Validation:

- Add tests for canonical and fallback identity resolution.
- Compare old and new visible employee sets before cutover.
- Produce a data reconciliation report before migration.

### Phase 4 — Make timesheet writes safe and consistent

1. Create a central timesheet mutation service for:
   - create
   - revise
   - delete
   - audit metadata
   - balance recalculation
   - approval-state handling

2. Enforce action-specific permissions:
   - view
   - fill
   - revise
   - approve
   - delete

3. Prevent employee reassignment during update unless explicitly authorized.

4. Validate:
   - uppercase attendance values
   - valid schedule ratios
   - date ranges
   - overtime values
   - immutable audit fields

5. Make approval updates transactional and retry-safe.
6. Change approval mutations to POST routes.
7. Add conditional updates or row locks to prevent double approval.
8. Ensure monthly approval-step creation is idempotent.

9. Add a unique `employee_id + day` constraint after checking production duplicates.

Validation:

- Concurrent approval tests.
- Duplicate timesheet creation tests.
- Post-approval revision tests.
- Balance calculation tests for empty, partial, and complete histories.

### Phase 5 — Remove measured query inefficiencies

1. Eager-load employee list relationships:
   - user
   - location
   - department
   - center
   - administration where needed

2. Replace query-producing accessors:
   - `total_working_days`
   - `total_off_days`
   - `Room::available`

   with explicit `withCount`, grouped aggregates, or endpoint-specific calculations.

3. Batch authorization lookups:
   - actor-to-employee resolution
   - roles
   - policies
   - approval flow steps
   - dependency checks

4. Reuse request-local authorization results instead of resolving them repeatedly.

5. Add query-budget tests for employee lists, approval pages, and room lists.

6. Replace `whereMonth()`/`whereYear()` filters with half-open date ranges where appropriate.

Validation:

- Compare query counts before and after.
- Benchmark representative employee and timesheet datasets.
- Verify generated SQL with `EXPLAIN`.

### Phase 6 — Bound reports and background work

1. Require a date range for timesheet reports.
2. Limit interactive previews with pagination.
3. Queue large exports and imports.
4. Use chunked or streamed processing for:
   - timesheet exports
   - employee exports
   - room imports
   - user imports
   - archived employee imports

5. Move yearly appraisal aggregation to a queued job.
6. Configure queue workers deliberately:
   - dedicated queues
   - timeout
   - retry interval
   - attempts
   - backoff
   - overlap protection

Validation:

- Large dataset memory tests.
- Failed-job and retry tests.
- Export correctness tests.
- Queue timeout tests.

### Phase 7 — Make backups recoverable

1. Restrict backup filenames and table names to validated allowlists.
2. Replace partial-success SQL comments with explicit failed backup status.
3. Use unique temporary files.
4. Check all storage write results.
5. Avoid loading complete SQL dumps into memory.
6. Use a consistent database dump strategy.
7. Add restore verification in a disposable MySQL database.
8. Set retry intervals above the 30-minute backup timeout.
9. Add overlap locks and operation IDs.
10. Keep old backups until a new backup has passed verification.

Validation:

- Successful backup/restore round trip.
- Partial table failure.
- Storage failure.
- Concurrent backup requests.
- Worker retry behavior.

### Phase 8 — Correct appraisal lifecycle

1. Define scoring rules for:
   - unanswered required items
   - text items
   - partial submissions
   - maximum scores
   - percentage calculation

2. Freeze form versions once referenced by a review.
3. Enforce period status during every write.
4. Make score updates transactional.
5. Define whether re-finalization replaces or preserves prior official results.
6. Make yearly aggregation idempotent.
7. Enforce one active version per form under concurrency.
8. Add explicit authorization policies for every appraisal action.

Validation:

- Required-item tests.
- Text-score tests.
- Closed-period tests.
- Re-finalization tests.
- Mixed-version tests.
- Concurrent activation tests.

### Phase 9 — Dependency and frontend maintenance

1. Patch compatible security updates first.
2. Review PhpSpreadsheet, Livewire, Dompdf, Guzzle, TinyMCE, Axios, and Vite advisories against actual usage.
3. Do not run broad major upgrades automatically.
4. Plan a staged Laravel upgrade after test and authorization work.
5. Consolidate duplicate frontend assets:
   - HugeRTE
   - Tabler
   - Tom Select
   - CDN scripts
6. Load editor code only on pages that need it.
7. Reduce the initial JavaScript and CSS payload.

Validation:

- Composer and npm audits.
- Production asset build.
- Browser smoke tests for forms, modals, Livewire, editors, and uploads.

### Phase 10 — Operational hardening

1. Add structured audit events for:
   - role changes
   - impersonation
   - medical edits
   - imports
   - approvals
   - restores
   - backup failures

2. Redact sensitive data from errors and logs.
3. Configure production log rotation and centralized monitoring.
4. Add slow-query, queue-duration, memory, and failed-job monitoring.
5. Add restore drills and authorization review procedures.
6. Reassess Octane only after FPM benchmarks demonstrate a real bootstrap bottleneck.

The safest implementation sequence is:

1. Security containment  
2. Test and cache repair  
3. Identity and authorization unification  
4. Timesheet integrity  
5. Query optimization  
6. Reports and queues  
7. Backups  
8. Appraisals  
9. Dependencies and frontend  
10. Operations and long-term architecture