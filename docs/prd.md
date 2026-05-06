# PRD: Time Entry Interface

## Slice 1: Valid Time Entry Creation and Read-Only History

## Problem Statement

Users need a simple Laravel + Vue application for recording employee time entries. Each entry must be tied to a valid company, employee, project, task, date, and hour amount.

The core risk is data integrity. The user must not be able to create a time entry where the employee, project, or task does not belong to the selected company, where the employee is not assigned to the selected project, or where the employee is recorded against more than one project on the same date.

Slice 1 must prove the full vertical path: seeded relational data, API-backed dependent option loading, keyboard-friendly table entry, backend validation, persistence, and read-only history.

## Solution

Build a Laravel JSON API backend and a Vue 3 Composition API single-page app using Vue Router. This is not an Inertia application.

The SPA presents a top-level company scope selector with `All` as the default, plus two route-backed tab views:

- `New Entries`: a table for entering one or more new time-entry rows.
- `History`: a read-only table listing previously submitted entries.

The frontend uses API data to guide valid choices, but the backend is the source of truth. All relationship and business-rule invariants are enforced through Laravel validation and transactional persistence.

## User Stories

1. As a time-entry user, I want to choose All or a specific company at the top of the page, so that I can control which entries and options I am working with.
2. As a time-entry user, I want the company selector to default to All, so that I can start without making an initial filter choice.
3. As a time-entry user, I want New Entries and History to behave like tabs, so that the two workflows are easy to switch between.
4. As a time-entry user, I want tab state to be route-backed, so that refreshes and URLs preserve which workflow I am viewing.
5. As a time-entry user, I want the New Entries table to show Company, Date, Employee, Project, Task, and Hours in that order, so that it matches the required data-entry structure.
6. As a time-entry user, I want to add multiple rows before submitting, so that I can record a batch in one request.
7. As a time-entry user, I want row company choices to respect the top-level company scope when a specific company is selected, so that focused entry is faster and less error-prone.
8. As a time-entry user, I want each row to allow any company when the top-level scope is All, so that I can enter cross-company batches.
9. As a time-entry user, I want employee options to depend on the selected row company, so that employees outside that company are not offered.
10. As a time-entry user, I want project options to depend on the selected row company, so that projects outside that company are not offered.
11. As a time-entry user, I want task options to depend on the selected row company, so that tasks outside that company are not offered.
12. As a time-entry user, I want project options to be limited to projects assigned to the selected employee, so that the selected employee can validly work on the project.
13. As a time-entry user, I want dependent fields to clear or revalidate after changing company or employee, so that stale invalid values are not silently submitted.
14. As a time-entry user, I want to enter hours as a positive numeric value, so that totals and reports can be computed correctly.
15. As a keyboard-heavy user, I want normal Tab navigation through each row and into the next row, so that I can enter time efficiently without using a mouse.
16. As a time-entry user, I want to submit all rows to one API endpoint, so that a batch is saved together.
17. As a time-entry user, I want invalid rows to show field-level messages, so that I know exactly what to fix.
18. As a time-entry user, I want backend validation to reject invalid employee, project, and task combinations, so that direct API calls cannot bypass UI rules.
19. As a time-entry user, I want the backend to reject entries where an employee would work on two different projects on the same date, so that the business rule is always preserved.
20. As a time-entry user, I want an employee to be allowed to work on multiple tasks for the same project on the same date, so that detailed task breakdowns are supported.
21. As a time-entry user, I want saved entries to appear in History, so that I can confirm what was recorded.
22. As a time-entry user, I want History to include Company, Date, Employee, Project, Task, and Hours, so that each row is clear.
23. As a time-entry user, I want the top-level company scope to filter History, so that I can review all companies or one company.
24. As a reviewer, I want seed data with multiple companies, shared employees, company-specific tasks, company-specific projects, and employee project assignments, so that I can test valid and invalid combinations quickly.
25. As a reviewer, I want a README with setup, seed data, validation, performance notes, and AI conversation export instructions, so that the project is easy to evaluate.

## Domain Model

### Company

Represents an organization for which employees, projects, tasks, and time entries are scoped.

### Employee

Represents a person who can belong to one or more companies and can be assigned to projects.

### Project

Represents company-specific work. A project belongs to exactly one company. Employees must be assigned to projects before time can be entered against those projects.

### Task

Represents company-specific activity categories. Tasks belong to exactly one company and are not project-specific.

### Time Entry

Represents recorded hours for one employee on one company, project, task, and date.

## Suggested Database Design

Use UUID primary keys for first-class models, matching the boilerplate model pattern.

### `companies`

- `id` UUID primary key
- `name` string, required
- timestamps

Suggested constraints:

- unique index on `name`

### `employees`

- `id` UUID primary key
- `name` string, required
- `email` string, nullable or required by implementation preference
- timestamps

Suggested constraints:

- unique index on `email` when email is present

### `company_employee`

Pivot table for company membership.

- `id` UUID primary key, or composite key by implementation preference
- `company_id` UUID foreign key to companies
- `employee_id` UUID foreign key to employees
- timestamps

Suggested constraints:

- unique index on `company_id`, `employee_id`
- foreign keys cascade on delete

### `projects`

- `id` UUID primary key
- `company_id` UUID foreign key to companies
- `name` string, required
- timestamps

Suggested constraints:

- unique index on `company_id`, `name`
- index on `company_id`
- foreign key cascade on delete

### `tasks`

- `id` UUID primary key
- `company_id` UUID foreign key to companies
- `name` string, required
- timestamps

Suggested constraints:

- unique index on `company_id`, `name`
- index on `company_id`
- foreign key cascade on delete

### `employee_project`

Pivot table for project assignments.

- `id` UUID primary key, or composite key by implementation preference
- `company_id` UUID foreign key to companies
- `employee_id` UUID foreign key to employees
- `project_id` UUID foreign key to projects
- timestamps

Suggested constraints:

- unique index on `company_id`, `employee_id`, `project_id`
- index on `employee_id`, `company_id`
- index on `project_id`
- foreign keys cascade on delete

Rationale:

- `company_id` is intentionally stored on the pivot even though project implies company. It makes validation and querying explicit and prevents ambiguous assignments when employees belong to multiple companies.

### `time_entries`

- `id` UUID primary key
- `company_id` UUID foreign key to companies
- `employee_id` UUID foreign key to employees
- `project_id` UUID foreign key to projects
- `task_id` UUID foreign key to tasks
- `entry_date` date, required
- `hours` decimal, required
- timestamps

Suggested constraints:

- index on `company_id`, `entry_date`
- index on `employee_id`, `entry_date`
- index on `project_id`, `entry_date`
- index on `task_id`
- foreign keys restrict or cascade by implementation preference; avoid orphaned entries
- `hours` should use decimal precision suitable for partial hours, for example `decimal(5, 2)`
- check constraint for `hours > 0` if supported by the database

Important business-rule note:

- Do not add a simple unique constraint on `employee_id`, `entry_date`, because that would incorrectly block multiple tasks on the same project/date.
- A database-level unique constraint for "one project per employee per date" is not straightforward while also allowing multiple task rows. Enforce this invariant in backend validation inside a transaction. If using a database that supports advanced constraints, this can be strengthened later with a generated key or separate daily assignment table.

## System Invariants

These invariants must hold after every successful API write.

1. Every company referenced by a time entry exists.
2. Every employee referenced by a time entry exists.
3. Every project referenced by a time entry exists.
4. Every task referenced by a time entry exists.
5. A project belongs to exactly one company.
6. A task belongs to exactly one company.
7. A company can have many employees.
8. An employee can belong to multiple companies.
9. A company can have many projects.
10. A company can have many tasks.
11. A time entry belongs to exactly one company.
12. A time entry belongs to exactly one employee.
13. A time entry belongs to exactly one project.
14. A time entry belongs to exactly one task.
15. A time entry has exactly one date.
16. A time entry's employee must belong to the time entry's company.
17. A time entry's project must belong to the time entry's company.
18. A time entry's task must belong to the time entry's company.
19. A time entry's employee must be assigned to the time entry's project.
20. The employee-project assignment must be for the same company as the time entry.
21. An employee may have multiple time entries on the same date only when all those entries use the same project.
22. An employee may have multiple time entries on the same date and same project across different tasks.
23. A submitted batch must not contain two rows where the same employee and date use different projects.
24. A submitted batch must not create a conflict with existing database entries where the same employee and date already use a different project.
25. Hours must be greater than zero.
26. Entry date must be a valid date.
27. Top-level company scope affects frontend option filtering and history display, but it does not replace backend row-level validation.
28. Frontend filtering is advisory; backend validation is authoritative.

## API Contract

Use versioned JSON API routes under `/api/v1`.

### `GET /api/v1/companies`

Returns company options for the top-level selector and row company fields.

Response shape:

- `data[]`
- `id`
- `name`

### `GET /api/v1/companies/{company}/employees`

Returns employees that belong to the selected company.

Response shape:

- `data[]`
- `id`
- `name`
- `email` when available

### `GET /api/v1/companies/{company}/projects`

Returns projects for the selected company. When `employee_id` is provided, returns only projects assigned to that employee within the company.

Supported query:

- `filter[employee_id]`

Response shape:

- `data[]`
- `id`
- `company_id`
- `name`

### `GET /api/v1/companies/{company}/tasks`

Returns tasks for the selected company.

Response shape:

- `data[]`
- `id`
- `company_id`
- `name`

### `GET /api/v1/time-entries`

Returns read-only history entries.

Supported query:

- `filter[company_id]` for a specific company
- no company filter for All

Slice 1 may return all seeded/saved entries without pagination if the dataset is small, but the endpoint should be structured so pagination can be added without changing the frontend contract substantially.

Response shape:

- `data[]`
- `id`
- `entry_date`
- `hours`
- `company`
- `employee`
- `project`
- `task`
- `created_at`

### `POST /api/v1/time-entries`

Creates a batch of time entries.

Request shape:

- `entries[]`
- `entries[].company_id`
- `entries[].entry_date`
- `entries[].employee_id`
- `entries[].project_id`
- `entries[].task_id`
- `entries[].hours`

Success response:

- HTTP 201
- created `time_entries` as API resources

Validation error response:

- HTTP 422
- errors keyed by row and field, for example `entries.0.employee_id`
- message text must make the invalid relationship understandable

## Frontend Spec

### Application Shell

- Vue 3 SPA mounted from the Laravel/Vite frontend entry.
- Vue Router owns navigation.
- A top-level company selector is visible above both route-backed tabs.
- The selector options are `All` plus all companies from the API.
- Default selected scope is `All`.
- New Entries and History appear visually as tabs, even if implemented as routes.

### New Entries View

- Render an editable table.
- Each row has fields in this exact order: Company, Date, Employee, Project, Task, Hours.
- The user can add rows before submitting.
- The user can submit all rows as a batch.
- When top-level scope is a specific company, new rows default to that company.
- When top-level scope is a specific company, row company selection should be locked or limited to that company.
- When top-level scope is All, each row can choose any company.
- Employee, project, and task fields are disabled until a row company is selected.
- Project field is disabled until an employee is selected if the frontend filters projects by employee.
- Changing company clears employee, project, and task if they are no longer valid.
- Changing employee clears project if it is no longer valid.
- Validation messages are displayed at the affected row and field.
- Successful submit clears saved rows or replaces them with one blank row, and History reflects the saved entries.
- Tab order follows the visual field order and continues predictably through added rows.

### History View

- Render a read-only table with Company, Date, Employee, Project, Task, and Hours.
- When top-level scope is All, show all entries.
- When top-level scope is a company, show only that company's entries.
- Data should reload or refetch when company scope changes.
- Slice 1 does not require editing, search, sorting, summaries, or pagination.

## Backend Implementation Decisions

- Use Laravel JSON API endpoints, not Inertia responses.
- Organize API routes under `/api/v1` following the boilerplate route organization pattern.
- Keep controllers thin: route/request orchestration only.
- Use Spatie Laravel Data DTOs for request validation and transformation.
- Use single-purpose action classes for business operations, especially batch creation.
- Wrap batch creation in a database transaction.
- Use Eloquent API Resources for JSON response formatting.
- Use query builder classes for history and option endpoints when filters are involved.
- Model companies, employees, projects, tasks, and time entries as first-class Eloquent models.
- Model company membership through `company_employee`.
- Model employee project assignments through `employee_project`.
- Keep tasks company-specific and not project-specific.
- Use eager loading for history to avoid N+1 queries.
- Select only fields needed for dropdown endpoints.
- Avoid authentication and authorization in Slice 1 because the exercise explicitly says authentication is not required.

## Frontend Implementation Decisions

- Use Vue 3 Composition API.
- Use Vue Router for New Entries and History.
- Use a small API client module around Axios or Fetch for `/api/v1` calls.
- Memoize option responses by company during the page session.
- Memoize employee-filtered project options by company and employee where practical.
- Avoid unnecessary API calls when row company/employee selections have not changed.
- Keep table controls native and keyboard-accessible.
- Use clear loading, disabled, empty, success, and validation states.

## Testing Decisions

- Backend tests should focus on externally visible behavior: JSON API responses, persisted records, validation failures, and business-rule enforcement.
- Test model relationships enough to prove company membership, project ownership, task ownership, project assignment, and time-entry ownership.
- Test company option endpoint.
- Test company-scoped employee endpoint.
- Test company-scoped project endpoint.
- Test employee-filtered project endpoint.
- Test company-scoped task endpoint.
- Test history endpoint with All companies and a specific company filter.
- Test successful batch creation.
- Test that multiple tasks for the same employee, project, and date are allowed.
- Test rejection when an employee does not belong to the selected company.
- Test rejection when a project does not belong to the selected company.
- Test rejection when a task does not belong to the selected company.
- Test rejection when an employee is not assigned to the selected project.
- Test rejection when a batch contains the same employee/date with two different projects.
- Test rejection when an existing entry for the same employee/date uses a different project.
- Test rejection when hours are zero, negative, missing, or non-numeric.
- Test rejection when date is missing or invalid.
- Test API response shape through resources without asserting controller internals.
- Frontend tests are optional for Slice 1 unless a Vue test setup is added; if added, cover route-backed tab navigation, dependent field clearing, option loading, batch submit, and backend row-level validation rendering.

## Acceptance Criteria

1. A reviewer can run migrations and seeders to get usable sample data.
2. The application opens to the SPA with company scope set to All.
3. The New Entries route shows a keyboard-friendly editable table.
4. The History route shows a read-only table.
5. A valid time-entry batch can be submitted and persisted.
6. Saved entries appear in History.
7. Company scope filters History.
8. Company scope influences New Entries row company behavior.
9. Employees, projects, and tasks are loaded from API endpoints based on selected company.
10. Projects can be filtered to those assigned to the selected employee.
11. The backend rejects invalid company, employee, project, and task relationships.
12. The backend rejects one employee working on different projects on the same date.
13. The backend allows one employee working on multiple tasks for the same project on the same date.
14. Backend validation errors are returned as JSON 422 responses keyed to row fields.
15. The frontend displays row-level validation errors clearly.
16. The implementation does not use Inertia.
17. The README explains setup, seed data, validation rules, performance considerations, and the AI conversation export requirement.

## Performance and Scalability Notes

- Dropdown option data should be loaded lazily by company instead of loading all employees, projects, and tasks for all companies upfront.
- Company options are small and can be loaded once on app startup.
- Company-scoped employees, projects, and tasks can be memoized on the frontend for the lifetime of the page.
- History should eager-load related company, employee, project, and task records.
- History should be designed so pagination can be added later without replacing the endpoint.
- Indexes on company/date and employee/date support the most important history and invariant checks.
- Validation for the one-project-per-employee-per-date rule should query existing entries by employee/date and compare project IDs efficiently.

## Out of Scope

- Authentication and authorization.
- Editing existing entries from History.
- Search, sorting, filtering beyond company scope, and pagination in History.
- Summary totals.
- AI-assisted natural-language entry.
- Advanced keyboard shortcuts beyond normal Tab navigation.
- Production-grade caching, background jobs, or reporting optimizations.
- Publishing the PRD to an external issue tracker; this repository currently has no issue tracker configuration.
- Inertia pages, Inertia shared props, and Inertia form handling.

## Further Notes

- Slice 1 is intentionally the smallest useful end-to-end product, but the invariants are complete for the required exercise.
- Later slices can add faster entry affordances, validation polish, history improvements, summary totals, editing, keyboard shortcuts, and optional AI-assisted entry.
- The repository must include a JSON export of the AI conversation used during development.
