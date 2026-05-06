# Feature Tracker

Use this matrix to track progress against the exercise spec. Status values should stay simple: `Not Started`, `In Progress`, `Done`, or `Deferred`.

## Must Have: Project Foundation

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | Laravel application scaffold | Tech Stack, Submission Requirements | Repository includes runnable Laravel 13 backend code; reviewed and verified with Composer validation and PHPUnit smoke tests. |
| Done | Spatie Laravel Data installed | Boilerplate Pattern | `spatie/laravel-data` is installed and package discovery succeeds. |
| Done | Spatie Query Builder installed | Boilerplate Pattern | `spatie/laravel-query-builder` is installed and package discovery succeeds. |
| Done | Laravel Boost installed | User request, Tooling | `laravel/boost` is installed as a dev dependency and Boost guidelines/skills are installed. |
| Done | Vue 3 frontend scaffold | Tech Stack, Submission Requirements | Frontend uses Vue 3 and Composition API; reviewed and verified with production Vite build. |
| Done | Vue Router installed and configured | Project clarification | New Entries and History are route-backed views with Laravel SPA fallback. |
| Done | shadcn-vue initialized | User request, UI Foundation | `components.json`, shadcn aliases, Tailwind CSS variables, CVA, Reka UI, Lucide icons, and `cn` helper are configured. |
| Done | Vite frontend build configured | Submission Requirements | Laravel scaffold includes Vite configuration and npm scripts. |
| Done | No authentication flow | Tech Stack | App currently has no login, registration, roles, or user account flow. |
| Done | No Inertia dependency in app flow | Project clarification | UI is a Vue Router SPA backed by Laravel JSON API. |

## Must Have: Database And Migrations

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | `companies` migration | Required Database Structure | Includes UUID primary key, unique name, and timestamps. |
| Done | `employees` migration | Required Database Structure | Includes UUID primary key, name, nullable unique email, and timestamps. |
| Done | `company_employee` migration | Required Relationships | Supports many-to-many company membership with unique company/employee pairs. |
| Done | `projects` migration | Required Database Structure | Includes UUID primary key, company foreign key, name, timestamps, and company/name uniqueness. |
| Done | `tasks` migration | Required Database Structure | Includes UUID primary key, company foreign key, name, timestamps, and company/name uniqueness. |
| Done | `employee_project` migration | Required Relationships | Supports employee assignments to company projects with unique company/employee/project triples. |
| Done | `time_entries` migration | Required Database Structure | Stores company, employee, project, task, entry date, hours, and timestamps. |
| Done | Foreign key constraints | Required Relationships | All required references use foreign UUID constraints; cross-table company consistency remains a validation invariant. |
| Done | Useful indexes | Performance Considerations | Includes indexes for company/date, employee/date, project/date, task, and option lookups. |
| Done | Hours storage precision | Goal | Uses `decimal(5, 2)` for partial hours; positive value enforcement belongs to backend validation. |

## Must Have: Models And Relationships

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | `Company` model | Required Generation | Uses UUIDs and exposes employees, projects, tasks, and time entries relationships. |
| Done | `Employee` model | Required Generation | Uses UUIDs and exposes companies, projects, and time entries relationships. |
| Done | `Project` model | Required Generation | Uses UUIDs and exposes company, employees, and time entries relationships. |
| Done | `Task` model | Required Generation | Uses UUIDs and exposes company and time entries relationships. |
| Done | `TimeEntry` model | Required Generation | Uses UUIDs, casts entry date/hours, appends display fields, and exposes company, employee, project, and task relationships. |
| Done | Company has many employees | Required Relationships | Implemented through `company_employee` with `Company::addEmployee()`. |
| Done | Employee belongs to multiple companies | Required Relationships | Employee can be attached to more than one company. |
| Done | Company has many projects | Required Relationships | Project belongs to one company. |
| Done | Company has many tasks | Required Relationships | Task belongs to one company and is not project-specific. |
| Done | Employees assigned to projects | Required Relationships | Implemented through `employee_project` with `Employee::assignToProject()`. |
| Done | Time entry belongs to required records | Required Relationships | Time entry belongs to company, employee, project, task, and date. |

## Must Have: Seed Data And Factories

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | Company seed data | Required Generation | Seeds Acme Operations and Globex Services for scope behavior. |
| Done | Employee seed data | Required Generation | Seeds employees belonging to one company and Cora Diaz as a shared employee across companies. |
| Done | Project seed data | Required Generation | Each seeded company has its own projects. |
| Done | Task seed data | Required Generation | Each seeded company has its own tasks. |
| Done | Employee project assignment seed data | Required Relationships | Seeded employees are assigned to one or more company projects. |
| Done | Optional time entry seed data | History Tab | Seeds history rows, including multiple tasks for the same employee/project/date. |
| Done | Model factories | Testing Decisions | Factories support API and relationship tests for all domain models. |

## Must Have: API Endpoints

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Not Started | Versioned API routes | API Requirements, Boilerplate Pattern | JSON API routes live under `/api/v1`. |
| Not Started | `GET /api/v1/companies` | Interface Requirements | Returns company options for top-level and row company selectors. |
| Not Started | `GET /api/v1/companies/{company}/employees` | New Entries Tab | Returns employees for selected company. |
| Not Started | `GET /api/v1/companies/{company}/projects` | New Entries Tab | Returns projects for selected company. |
| Not Started | Employee-filtered projects query | Required Relationships | Project options can be filtered by selected employee assignment. |
| Not Started | `GET /api/v1/companies/{company}/tasks` | New Entries Tab | Returns tasks for selected company. |
| Not Started | `GET /api/v1/time-entries` | History Tab | Returns read-only time entry history. |
| Not Started | Company-filtered history query | Interface Requirements | History supports All and specific company scope. |
| Not Started | `POST /api/v1/time-entries` | New Entries Tab | Accepts a batch of entries and persists valid rows. |
| Not Started | API Resources | Boilerplate Pattern | Responses use consistent JSON resource shapes. |
| Not Started | Thin API controllers | Boilerplate Pattern | Controllers orchestrate DTOs/actions/query builders and responses only. |
| Not Started | Query builders where useful | Boilerplate Pattern | History and filtered option endpoints keep query logic out of controllers. |

## Must Have: Backend Validation And Invariants

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Not Started | DTO validation for batch payload | Boilerplate Pattern | Uses typed request data instead of raw controller validation. |
| Not Started | Required field validation | Goal | Company, date, employee, project, task, and hours are required. |
| Not Started | Date validation | Goal | Entry date must be a valid date. |
| Not Started | Hours validation | Goal | Hours must be numeric and greater than zero. |
| Not Started | Employee company membership validation | Goal | API rejects employee outside selected company. |
| Not Started | Project company validation | Goal | API rejects project outside selected company. |
| Not Started | Task company validation | Goal | API rejects task outside selected company. |
| Not Started | Employee project assignment validation | Required Relationships | API rejects project not assigned to selected employee. |
| Not Started | One project per employee per date validation | Business Rules | API rejects different projects for same employee/date. |
| Not Started | Multiple tasks same project/date allowed | Business Rules | API allows same employee/date/project with multiple tasks. |
| Not Started | Batch internal conflict validation | Business Rules | API rejects conflicting projects within the submitted batch. |
| Not Started | Existing data conflict validation | Business Rules | API rejects conflicts with already-saved entries. |
| Not Started | Transactional batch creation | Business Rules | A batch saves atomically; invalid batch persists nothing. |
| Not Started | Row-field JSON validation errors | Bonus / UX Baseline | 422 errors are keyed like `entries.0.project_id`. |

## Must Have: Frontend Application Shell

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | SPA app shell | Interface Requirements | Vue app mounts successfully from Laravel/Vite. |
| Not Started | Top-level company selector | Interface Requirements | Selector appears above both views. |
| Not Started | All default option | Interface Requirements | Default scope is `All`. |
| Done | Route-backed tab navigation | Interface Requirements | New Entries and History appear as two tabs. |
| Not Started | Scope state shared across views | Interface Requirements | Company selection affects both New Entries and History. |
| Not Started | Clean usable visual design | UX / Design Expectations | Interface is reasonably polished and practical. |

## Must Have: New Entries Frontend

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Not Started | Editable table | New Entries Tab | Each row represents one new time entry. |
| Not Started | Required field order | New Entries Tab | Columns are Company, Date, Employee, Project, Task, Hours. |
| Not Started | Add row control | New Entries Tab | User can add multiple rows before submitting. |
| Not Started | Batch submit control | New Entries Tab | Sends all rows to API endpoint. |
| Not Started | Company-scoped employee loading | New Entries Tab | Employee dropdown depends on row company. |
| Not Started | Company-scoped project loading | New Entries Tab | Project dropdown depends on row company. |
| Not Started | Employee-filtered project loading | Required Relationships | Project dropdown respects selected employee assignment. |
| Not Started | Company-scoped task loading | New Entries Tab | Task dropdown depends on row company. |
| Not Started | Dependent field clearing | New Entries Tab | Changing company/employee clears invalid dependent fields. |
| Not Started | Keyboard-friendly tab flow | UX / Design Expectations | User can fill rows efficiently with Tab. |
| Not Started | Frontend invalid-combination prevention | Goal | UI does not offer invalid employee/project/task combinations when API data is available. |
| Not Started | Row-level validation display | Bonus / UX Baseline | Backend validation errors are visible beside affected fields. |
| Not Started | Successful submit state | New Entries Tab | Saved rows clear or reset, and user gets confirmation. |

## Must Have: History Frontend

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Not Started | Read-only history table | History Tab | Lists previously submitted time entries. |
| Not Started | History core columns | History Tab | Shows Company, Date, Employee, Project, Task, Hours. |
| Not Started | All-company history | Interface Requirements | Shows entries across all companies when scope is All. |
| Not Started | Company-filtered history | Interface Requirements | Shows only selected company when scope is specific. |
| Not Started | Refresh after submit | New Entries Tab, History Tab | Newly saved entries appear in History. |
| Not Started | Empty state | UX / Design Expectations | Clear display when no entries exist. |

## Must Have: Tests

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | Relationship tests | Evaluation Criteria | Proves required model relationships and appended time-entry display fields work. |
| Not Started | Company options API test | API Requirements | Endpoint returns expected company resource shape. |
| Not Started | Company employees API test | New Entries Tab | Endpoint returns only employees for selected company. |
| Not Started | Company projects API test | New Entries Tab | Endpoint returns only projects for selected company. |
| Not Started | Employee-filtered projects API test | Required Relationships | Endpoint returns only projects assigned to selected employee. |
| Not Started | Company tasks API test | New Entries Tab | Endpoint returns only tasks for selected company. |
| Not Started | History API test | History Tab | Endpoint returns saved entries with required related labels. |
| Not Started | Company-filtered history API test | Interface Requirements | Endpoint respects specific company filter. |
| Not Started | Valid batch create API test | Business Rules | Valid batch persists and returns created resources. |
| Not Started | Multiple tasks same project/date test | Business Rules | Same employee/date/project with different tasks is accepted. |
| Not Started | Invalid employee/company test | Goal | API rejects employee outside company. |
| Not Started | Invalid project/company test | Goal | API rejects project outside company. |
| Not Started | Invalid task/company test | Goal | API rejects task outside company. |
| Not Started | Invalid employee/project assignment test | Required Relationships | API rejects unassigned project for employee. |
| Not Started | One-project-per-day conflict test | Business Rules | API rejects different project for same employee/date. |
| Not Started | Invalid hours/date tests | Goal | API rejects invalid hours and dates. |
| Not Started | Optional frontend tests | Testing Decisions | Only required if a frontend test setup is added. |

## Must Have: Documentation And Submission

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Not Started | README setup commands | Submission Requirements | Documents composer/npm/env/key/migrate/seed/dev or build commands. |
| Not Started | README seed data notes | Submission Requirements | Explains demo companies, employees, projects, tasks, assignments. |
| Not Started | README business rules | Evaluation Criteria | Documents validation and one-project-per-day rule. |
| Not Started | README performance notes | Performance Considerations | Documents caching/loading/index decisions. |
| Not Started | README AI usage note | Submission Requirements | Points to AI conversation export. |
| Not Started | AI conversation JSON export | Submission Requirements | Include JSON export, preferably `docs/ai-conversation.json`. |
| Not Started | GitHub-ready repository | Submission Requirements | Repo contains backend, frontend, migrations, seeders, endpoints, README, AI export. |

## Bonus

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Deferred | Edit existing entries | Bonus: Edit Existing Entries | Allow editing entries from History. |
| Deferred | Faster data entry helpers | Bonus: Faster Data Entry | Duplicate row, reuse previous values, or similar speed improvements. |
| Deferred | Enhanced validation UX | Bonus: Better Validation UX | More polished row-level and field-level backend validation display. |
| Deferred | Summary totals | Bonus: Summary Totals | Totals by employee, project, task, date, company, or useful combination. |
| Deferred | History search | Bonus: History Table Improvements | Search across history entries. |
| Deferred | History sorting | Bonus: History Table Improvements | Sort history by date, employee, company, project, task, or hours. |
| Deferred | History filtering beyond company scope | Bonus: History Table Improvements | Add filters such as employee, project, task, date range. |
| Deferred | History pagination | Bonus: History Table Improvements | Paginate history for larger datasets. |
| Deferred | Keyboard shortcuts | Bonus: Keyboard Shortcuts | Add thoughtful shortcuts beyond standard Tab navigation. |
| Deferred | AI-assisted entry | Super Bonus: AI-Assisted Entry | Plain-English input can parse and fill New Entries table. Requires API key if implemented with external AI. |

## Do Not Implement In Slice 1

| Status | Item | Reason |
| --- | --- | --- |
| Deferred | Authentication | Spec explicitly says authentication is not required. |
| Deferred | Authorization / roles / permissions | Not required without authentication. |
| Deferred | Inertia pages or Inertia form handling | Project direction is Laravel API with Vue 3 Router. |
| Deferred | Production-grade reporting | Spec asks for simple create/view interface, not reporting product. |
| Deferred | Background jobs | No async workflow is required for Slice 1. |
| Deferred | External AI integration | Super bonus only; should not block must-have app. |
| Deferred | Complex caching infrastructure | Performance should be considered, but over-engineering is unnecessary. |
| Deferred | Multi-tenant workspace/auth boilerplate | Companies are domain data for time entries, not authenticated tenant context in this exercise. |
