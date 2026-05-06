# ADR 001: Time Entry Invariant Scope

## Status

Accepted

## Context

The assignment requires:

- An employee can only work on one project per date.
- An employee can work on multiple tasks for that same project on the same date.
- Backend validation must enforce the rule.

The data model also says:

- An employee can belong to multiple companies.
- Projects and tasks are company-specific.
- A time entry belongs to a company.

That creates two interpretation points:

1. Whether "one project per date" is global for an employee or scoped to a company.
2. Whether repeated rows for the same task on the same employee, project, and date are valid.

## Decision

The one-project-per-date rule is scoped by company.

Within one company, an employee may only have one project on a given date. Across different companies, the same shared employee may work on different projects on that date.

Exact duplicate task rows are rejected as a data-integrity guard.

An employee may have multiple rows for the same company, date, and project only when those rows use distinct tasks. Repeating the same company, employee, date, project, and task is treated as an accidental duplicate rather than a separate time block.

## Rationale

Company-scoped validation matches the rest of the domain model. Projects and tasks are company-specific, and every time entry stores `company_id`, so a project in one company should not block a valid entry in another company for the same shared employee.

Rejecting exact duplicate task rows keeps history and totals unambiguous. The assignment explicitly allows multiple tasks for the same project/date, which implies distinct task rows. It does not explicitly require splitting a single task into multiple rows on the same date.

## Enforcement

Application validation:

- Batch conflicts are keyed by `company_id`, `employee_id`, and normalized `entry_date`.
- Existing-entry conflicts are checked against the same company-scoped key.
- Exact duplicate task rows are keyed by `company_id`, `employee_id`, `project_id`, `task_id`, and normalized `entry_date`.
- Validation errors stay row-level, for example `entries.1.project_id` or `entries.1.task_id`.

Database guard:

- `time_entries` has a unique index on `company_id`, `employee_id`, `project_id`, `task_id`, and `entry_date`.

## Consequences

Allowed:

- Same employee, same company, same date, same project, different tasks.
- Same employee, different companies, same date, different projects.

Rejected:

- Same employee, same company, same date, different projects.
- Same company, employee, date, project, and task repeated in a submitted batch.
- Same company, employee, date, project, and task submitted when that row already exists.

If future product requirements need multiple time blocks for the same task on the same date, the duplicate-task rule and unique index should be revisited. At that point the UI should make time blocks explicit enough that duplicate rows are not ambiguous.
