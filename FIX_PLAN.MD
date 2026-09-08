# Laravel Application — Unified Fix Implementation Plan

## Purpose

This plan converts the application audit into a controlled remediation program.

The objectives are:

1. Eliminate critical security vulnerabilities.
2. Preserve existing HR/business behavior unless explicitly changed.
3. Establish reliable tests and deployment foundations.
4. Unify authorization and identity rules.
5. Make timesheet operations correct, atomic, and concurrency-safe.
6. Remove measured N+1 and database inefficiencies.
7. Bound large reports and move expensive operations to queues.
8. Make backups verifiably recoverable.
9. Correct the appraisal lifecycle.
10. Resolve dependency and frontend security/maintenance issues.
11. Improve production observability and operational safety.
12. Reassess Laravel Octane only after measurable evidence justifies it.

---

# Global Implementation Rules

These rules apply to every phase.

### 1. No big-bang rewrite

Do not introduce:

* repository layers without evidence
* unnecessary architectural rewrites
* new frameworks
* unnecessary packages
* broad model/controller restructuring

Prefer targeted changes that solve an identified problem.

### 2. Preserve business behavior

Before changing business logic:

* inspect the existing implementation
* inspect related tests
* inspect routes, policies, services, models, migrations, and views
* identify the current behavior

If the correct business rule is not established by the existing application/specification:

> Do not invent a new business rule.

Document the ambiguity and preserve existing behavior until the rule is explicitly decided.

### 3. Measure before optimizing

For performance work:

```text
Baseline
    ↓
Identify bottleneck
    ↓
Implement targeted change
    ↓
Run tests
    ↓
Benchmark again
    ↓
Compare before/after
```

Do not optimize based only on assumptions.

### 4. Security must be enforced server-side

Never rely on:

* hidden buttons
* disabled UI controls
* Livewire component visibility
* frontend validation

Every sensitive action must enforce authorization on the server.

### 5. Small implementation steps

Each phase should be implemented independently.

For each phase:

1. Inspect.
2. Plan the exact changes.
3. Implement.
4. Run targeted tests.
5. Run relevant regression tests.
6. Review changed files.
7. Report results.
8. Stop before moving to the next phase unless explicitly instructed.

---

# Phase 0 — Baseline and Safety Foundation

## Objective

Create a reliable baseline before modifying business-critical code.

### Tasks

1. Create a dedicated remediation branch.
2. Back up the database.
3. Verify the application boots.
4. Repair stale route/view cache problems.
5. Verify the installed Livewire version is used consistently.
6. Fix the `UserFactory` primary-key collision.
7. Fix `EmployeeFactory` defaults:

   * active employee by default
   * valid schedule
   * controlled user linkage
8. Ensure tests use isolated:

   * database
   * cache
   * route cache
   * compiled views
9. Run the existing targeted test suites.
10. Run the full isolated test suite where practical.
11. Add characterization tests around critical existing behavior before invasive changes.

## Establish performance baseline

Measure representative workloads such as:

### Employee lists

* 10 employees
* 20 employees
* 50 employees
* 100 employees
* 500 employees

Record:

* query count
* SQL execution time
* request duration
* peak memory
* returned rows

### Timesheet workloads

Measure:

* normal timesheet list
* approval page
* employee attendance page
* representative report
* search/filter operation

### Reports

Measure at least:

* one-month report
* larger multi-month/year report where safe

Store the baseline so Phase 5/6 can compare against it.

## Validation

* Previously failing employee controller tests pass.
* Application boots normally.
* Targeted tests pass.
* Baseline measurements are recorded.
* No production data is modified.

---

# Phase 1 — Contain Critical Security Risks

## Objective

Eliminate the highest-risk authorization vulnerabilities before performance or architectural work.

### 1. Maintenance authorization

Add explicit authorization policies/gates for:

* database export
* SQL import
* restore
* backup download
* backup deletion
* backup settings
* maintenance operations

Verify both routes and underlying actions.

### 2. Registration

Restrict or disable public registration.

Preferred model:

```text
Administrator
    ↓
Create user / invite user
    ↓
Assign appropriate role
    ↓
User activates account
```

If public registration must remain temporarily:

* assign a restricted default role
* prevent privileged access
* prevent maintenance access
* prevent user-management access

### 3. Secure Livewire actions

Audit and secure:

* `TimeTable`
* `TimeSheetApprove`
* `ClinicEmployeeInfo`

For every public Livewire action:

1. Validate all inputs.
2. Resolve the target record.
3. Verify employee scope.
4. Verify appointment/record ownership where applicable.
5. Verify role.
6. Verify manager/approval scope.
7. Verify current state.
8. Perform authorization.
9. Only then mutate data.

Authorization must happen inside the action/service, not only in the Blade view.

### 4. Appraisal authorization

Secure:

* review creation
* review update
* submission
* version management
* item management
* finalization
* employee approval
* manager approval
* HR approval
* employee form assignment

### 5. Auxiliary operations

Audit authorization for:

* employee archive/import
* reports
* user imports
* flight approval
* room imports
* archive/unarchive
* other administrative bulk actions

## Validation

Add denial tests for:

* unauthenticated users
* ordinary authenticated users
* unrelated managers
* revoked managers
* users outside the employee's scope
* manipulated IDs
* invalid Livewire parameters
* users without required permissions

Also test authorized users to ensure legitimate behavior still works.

---

# Phase 2 — Repair Test and Deployment Foundations

## Objective

Make the test and deployment environment trustworthy.

### Tasks

1. Fix factories and deterministic test data.
2. Separate test runtime state from local development state.
3. Ensure test cache cannot affect application behavior.
4. Repair route caching.
5. Repair compiled Blade/Livewire views.
6. Verify production cache commands.
7. Create deployment smoke tests covering:

```text
Login
 ↓
Dashboard
 ↓
Livewire page
 ↓
Maintenance denial
 ↓
Authorized CRUD action
```

### Validation

Run:

* targeted tests
* Livewire tests
* employee controller tests
* full isolated suite

No database refresh/migration should be performed against real production data during testing.

---

# Phase 3 — Unify Authorization and Identity

## Objective

Remove conflicting identity and management-scope sources.

## 3.1 Authoritative management scope

Determine and document one authoritative management-scope store.

Preferred direction:

> Make the active V2 policy store authoritative.

Then:

1. Update management-scope UI to write to that store.
2. Identify old scope storage.
3. Compare old/new results.
4. Introduce temporary compatibility only where required.
5. Remove compatibility after parity is demonstrated.

### Validation

For representative managers:

```text
Old visible employee set
        vs
New visible employee set
```

They should match unless an intentional business correction is documented.

## 3.2 Canonical identity

Establish:

* stable user primary keys
* explicit `employees.user_id`
* consistent signer foreign keys
* consistent approver foreign keys
* stable employee identity

Do not use mutable employee numbers as primary identity.

## 3.3 Data reconciliation

Before changing constraints:

reconcile:

* users
* employees
* signatures
* timesheet approvers
* roles
* Sanctum tokens
* management scopes

Produce a reconciliation report.

Only after data is clean should constraints/migrations be introduced.

## 3.4 Historical data

Review destructive cascades.

Historical HR information should not disappear simply because an employee/user relationship is removed.

Replace destructive cascades where business rules require historical retention.

---

# Phase 4 — Timesheet Integrity and Safe Writes

## Objective

Create a reliable and concurrency-safe timesheet mutation path.

### 4.1 Central mutation service

Create a focused timesheet mutation service for:

* creation
* revision
* deletion
* audit metadata
* balance recalculation
* approval state
* related mutations

Controllers and Livewire components should coordinate requests rather than duplicate business rules.

### 4.2 Action-specific permissions

Define permissions for:

* view
* fill
* revise
* approve
* delete

### 4.3 Input validation

Validate:

* attendance values
* uppercase normalization where required
* schedule ratios
* date ranges
* overtime
* immutable audit fields
* employee ownership/scope

### 4.4 Prevent unauthorized reassignment

Updating a timesheet must not silently change its employee.

Require explicit authorization for reassignment if that operation is actually supported.

### 4.5 Transactional approval

Approval operations must be transactional.

Use appropriate:

* transactions
* conditional updates
* row locks where justified

Prevent double approval or conflicting concurrent updates.

### 4.6 Idempotency

Ensure:

* monthly approval-step creation is idempotent
* retries do not duplicate records
* queue retries do not duplicate mutations

### 4.7 HTTP semantics

Change state-changing approval operations from GET to POST with CSRF protection.

Preserve the existing business behavior.

### 4.8 Database uniqueness

First check existing production data for duplicate:

```text
employee_id + day
```

Resolve/reconcile duplicates.

Then introduce the database uniqueness constraint if the business rule confirms one timesheet per employee/day.

## Validation

Add tests for:

* duplicate creation
* concurrent creation
* concurrent approval
* double approval
* unauthorized revision
* post-approval revision
* employee reassignment
* empty history
* partial history
* complete history
* balance recalculation

---

# Phase 5 — Remove Measured Query Inefficiencies

## Objective

Eliminate N+1 queries and repeated database work without hiding problems behind caching.

## 5.1 Employee relationships

Audit and optimize relationships such as:

* user
* location
* department
* center
* administration
* signatures

Use targeted eager loading based on what each endpoint actually displays.

Do not blindly eager-load every relationship.

## 5.2 Query-producing accessors

Review:

* `total_working_days`
* `total_off_days`
* `Room::available`
* similar accessors

Replace repeated per-record queries with appropriate:

* `withCount`
* grouped aggregates
* subqueries
* endpoint-specific aggregate queries
* explicit eager loading

## 5.3 Authorization queries

Batch repeated lookups for:

* actor → employee resolution
* roles
* policies
* approval steps
* dependency checks

Reuse request-local results where safe.

Do not introduce global mutable state merely to avoid queries.

## 5.4 Duplicate queries

Find repeated loading of:

* departments
* employees
* roles
* managers
* locations
* dropdown data

Reuse request-local data where appropriate.

## 5.5 Date predicates

Replace function-wrapped date filtering such as:

```text
whereMonth()
whereYear()
```

with half-open date ranges where appropriate so indexes can be used.

## 5.6 Query budgets

Add regression tests for important screens.

The budget should be based on measured healthy behavior rather than arbitrary numbers.

## Validation

For every optimization:

```text
Before
    ↓
query count
SQL time
request time
memory
    ↓
After
```

Verify important queries using `EXPLAIN`.

The optimization is successful only if:

1. behavior is unchanged
2. query work is reduced
3. performance is improved or resource usage is meaningfully reduced

---

# Phase 6 — Bound Reports and Background Work

## Objective

Prevent large operations from exhausting PHP memory or blocking HTTP requests.

### 6.1 Reports

Require appropriate date ranges for large timesheet reports.

Interactive previews should be:

* filtered
* paginated
* bounded

Never hydrate the entire timesheet table into PHP unnecessarily.

### 6.2 Large exports

Queue large:

* timesheet exports
* employee exports
* reports

### 6.3 Imports

Use chunked/streamed processing for:

* room imports
* user imports
* employee imports
* archived employee imports

### 6.4 Yearly aggregation

Move expensive yearly appraisal aggregation to a queue where appropriate.

### 6.5 Queue architecture

Configure deliberately:

* dedicated queues
* timeout
* attempts
* backoff
* retry interval
* overlap protection
* idempotency

Use dispatch-after-commit when jobs depend on committed database state.

## Validation

Test:

* large dataset memory usage
* export correctness
* import correctness
* failed jobs
* retries
* timeout behavior
* duplicate retry prevention

---

# Phase 7 — Make Backups Recoverable

## Objective

Treat backup/restore as a production-critical subsystem.

### Tasks

1. Validate backup filenames using strict rules/allowlists.
2. Validate table names against known database metadata/allowlists.
3. Use unique temporary files.
4. Check every storage write.
5. Avoid loading complete SQL dumps into PHP memory.
6. Use a consistent dump strategy.
7. Record explicit backup status:

   * started
   * running
   * successful
   * failed
   * verified
8. Give operations unique IDs.
9. Prevent concurrent backup conflicts with locks.
10. Configure retry intervals that respect backup timeout.
11. Keep the previous verified backup until the new backup passes verification.
12. Verify restores using a disposable MySQL database.

## Validation

Test:

* successful backup
* successful restore
* partial table failure
* storage failure
* concurrent requests
* worker timeout
* worker retry
* corrupted backup
* failed restore

A backup is considered successful only when its verification requirements pass.

---

# Phase 8 — Correct Appraisal Lifecycle

## Objective

Make appraisal behavior consistent, authorized, versioned, and concurrency-safe.

### Define scoring rules

Explicitly determine existing business behavior for:

* unanswered required items
* text items
* partial submissions
* maximum scores
* percentage calculations

Do not invent new scoring rules without evidence.

### Versioning

Once an appraisal form version is referenced by a review:

* freeze the version
* prevent unintended structural changes

### Period status

Every write must enforce the current appraisal period state.

### Transactions

Make score updates and finalization transactional.

### Finalization

Define whether re-finalization:

* replaces previous results
* preserves historical results
* is prohibited

Preserve existing intended behavior unless the business rule says otherwise.

### Version activation

Ensure only one active version exists where required, including concurrent activation attempts.

### Authorization

Create explicit policies for every appraisal action.

### Yearly aggregation

Make aggregation idempotent.

## Validation

Test:

* required items
* text items
* scoring
* percentages
* closed periods
* finalized reviews
* re-finalization
* mixed versions
* concurrent activation
* unauthorized access
* yearly aggregation

---

# Phase 9 — Dependency and Frontend Maintenance

## Objective

Reduce known security exposure without destabilizing the application.

### Composer

Patch compatible security updates first.

Review advisories for:

* PhpSpreadsheet
* Livewire
* Dompdf
* Guzzle
* other production dependencies

Determine actual applicability before prioritizing each issue.

### NPM

Review:

* Axios
* Vite
* frontend dependencies
* development-only dependencies

Do not blindly apply:

```text
npm audit fix --force
```

Do not perform broad major upgrades automatically.

### Dependency cleanup

Confirm whether potentially unused dependencies are actually used before removing:

* Intervention
* PHPWord
* Dompdf
* TinyMCE-related packages
* old tooling

### Laravel upgrade

Plan a staged Laravel upgrade only after:

* security containment
* test stability
* authorization fixes
* business-critical regression coverage

### Frontend

Consolidate duplicate assets where safe:

* HugeRTE
* Tabler
* Tom Select
* CDN scripts

Lazy-load editors and page-specific assets.

Reduce initial CSS/JavaScript payload.

## Validation

Run:

* Composer audit
* NPM audit
* production asset build
* browser smoke tests
* Livewire interaction tests
* upload tests
* editor tests

---

# Phase 10 — Production and Operational Hardening

## Configuration

Verify production configuration:

```text
APP_ENV=production
APP_DEBUG=false
```

Verify:

* config cache
* route cache
* view cache
* production asset build

### Storage

Before horizontal scaling, ensure shared files are stored in durable shared storage/object storage where required.

### Logging

Configure:

* production log level
* rotation
* centralized collection where appropriate
* sensitive-data redaction

### Monitoring

Monitor:

* slow queries
* request duration
* queue duration
* failed jobs
* memory usage
* backup failures
* authorization failures
* application errors

### Audit events

Add structured audit events for sensitive operations such as:

* role changes
* impersonation
* medical record edits
* imports
* approvals
* restores
* backup failures

Do not log sensitive medical/personnel data unnecessarily.

### Restore drills

Perform periodic restore tests.

### Authorization reviews

Periodically review privileged roles and management scopes.

---

# Phase 11 — Static Analysis and Code Quality

After functional/security changes stabilize:

Introduce/strengthen:

* Laravel Pint
* PHPStan/Larastan

Start from a realistic baseline.

Recommended approach:

```text
Existing code
    ↓
Establish baseline
    ↓
New code must pass
    ↓
Touched files improve
    ↓
Gradually reduce baseline
```

Use Pint on touched PHP files.

Avoid unrelated mass-formatting changes.

---

# Phase 12 — Reassess Laravel Octane

Octane is intentionally last.

Do not introduce it simply because the application has performance problems.

First fix:

```text
Security
 ↓
Correctness
 ↓
N+1 queries
 ↓
Database
 ↓
Reports
 ↓
Queues
 ↓
Dependencies
 ↓
Production configuration
 ↓
FPM benchmark
```

Then compare realistic workloads using:

* PHP-FPM
* Octane

Investigate:

* static mutable state
* service lifetimes
* authenticated-user state
* backup services
* mutable collections
* global scopes
* memory growth
* sequential requests from different users
* package compatibility

Only adopt Octane if measurements demonstrate a meaningful improvement.

---

# Final Execution Order

The recommended order is:

```text
M0  Baseline + test/deployment foundation
 ↓
M1  Critical security containment
 ↓
M2  Authorization + identity unification
 ↓
M3  Timesheet integrity + concurrency
 ↓
M4  Measured N+1/query optimization
 ↓
M5  Reports + imports + queues
 ↓
M6  Backup/restore reliability
 ↓
M7  Appraisal lifecycle
 ↓
M8  Dependency + frontend maintenance
 ↓
M9  Production operations + observability
 ↓
M10 Static analysis/code quality
 ↓
M11 FPM benchmark → Octane decision
```

# Definition of Done

The application should not be considered remediated merely because the code compiles.

For each phase, verify:

### Security

* Unauthorized requests are denied.
* Authorized requests still work.
* Direct HTTP/Livewire calls cannot bypass UI restrictions.

### Correctness

* Existing business behavior remains intact unless intentionally changed.
* Critical workflows have regression tests.
* Concurrent operations cannot create inconsistent state.

### Performance

* Query count is measured.
* SQL time is measured.
* Request time is measured.
* Memory is measured where relevant.
* Before/after results are recorded.

### Database

* Constraints match actual business rules.
* Existing data is reconciled before restrictive migrations.
* Important queries have appropriate indexes.
* `EXPLAIN` has been checked for major query changes.

### Operations

* Queued jobs retry safely.
* Backups can actually be restored.
* Logs are useful without exposing sensitive data.
* Production caches/builds work.
* Monitoring detects important failures.

# Most Important Principle

Do not optimize or modernize the application for its own sake.

The target state is:

```text
Secure
   +
Correct
   +
Measured
   +
Maintainable
   +
Scalable where actually needed
```

The application does not need a rewrite.

It needs **controlled remediation of the problems identified by the audit**, followed by measurement to determine which additional improvements are justified.
