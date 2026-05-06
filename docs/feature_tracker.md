# Feature Tracker

Use this matrix to track progress against the exercise spec. Status values should stay simple: `Not Started`, `In Progress`, `Done`, or `Deferred`.

## Current Slice

| Status | Slice | Scope | Next Step |
| --- | --- | --- | --- |
| In Progress | AI-Assisted Entry Bonus | Must-have create/view flow is API-backed, tested, and usable with real seeded data. | Remaining: polish AI-assisted draft behavior, reuse-previous-values auto-fill, optional date-range history filtering, and AI conversation export. |

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
| Done | Exact duplicate task guard | Business Rules | Unique index blocks duplicate company/employee/project/task/date rows while allowing different tasks on the same employee/project/date. |
| Done | Hours storage precision | Goal | Uses `decimal(5, 2)` for partial hours; positive value enforcement belongs to backend validation. |

## Must Have: Models And Relationships

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | `Company` model | Required Generation | Uses UUIDs and exposes employees, projects, tasks, and time entries relationships. |
| Done | `Employee` model | Required Generation | Uses UUIDs and exposes companies, projects, and time entries relationships. |
| Done | `Project` model | Required Generation | Uses UUIDs and exposes company, employees, and time entries relationships. |
| Done | `Task` model | Required Generation | Uses UUIDs and exposes company and time entries relationships. |
| Done | `TimeEntry` model | Required Generation | Uses UUIDs, model casts, appended display fields, and exposes company, employee, project, and task relationships. |
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
| Done | Seed actions | Boilerplate Pattern | Seeder creates companies, employees, projects, tasks, assignments, and entries through action classes. |
| Done | Seed DTOs | Boilerplate Pattern | Seeder action payloads use Spatie Data DTOs from `app/Data`. |

## Must Have: API Endpoints

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | Versioned API routes | API Requirements, Boilerplate Pattern | JSON API routes live under `/api/v1` and are registered through `routes/api.php`. |
| Done | `GET /api/v1/companies` | Interface Requirements | Returns company options for top-level and row company selectors, ordered by name. |
| Done | `GET /api/v1/companies/{company}/employees` | New Entries Tab | Returns only employees for the selected company. |
| Done | `GET /api/v1/companies/{company}/projects` | New Entries Tab | Returns only projects for the selected company. |
| Done | Employee-filtered projects query | Required Relationships | Project options can be filtered by selected employee assignment through `filter[employee_id]`. |
| Done | `GET /api/v1/companies/{company}/tasks` | New Entries Tab | Returns only tasks for the selected company. |
| Done | `GET /api/v1/time-entries` | History Tab | Returns read-only time entry history with related company, employee, project, and task labels. |
| Done | Company-filtered history query | Interface Requirements | History supports All and specific company scope through `filter[company_id]`. |
| Done | `POST /api/v1/time-entries` | New Entries Tab | Accepts a batch of entries and persists valid rows through Spatie Data DTO validation and action-based transactional creation. |
| Done | API Resources | Boilerplate Pattern | Responses use consistent JSON resource shapes for companies, employees, projects, tasks, and time entries. |
| In Progress | `POST /api/v1/ai/time-entry-drafts` | Super Bonus: AI-Assisted Entry | Parses plain-English notes into spreadsheet-ready draft rows without persisting; agent provider/model are env-selectable, defaulting to the DeepSeek adapter for local gateway compatibility. |
| Done | Thin API controllers | Boilerplate Pattern | Controllers orchestrate resources, read-side services, and query builders only. |
| Done | Query builders where useful | Boilerplate Pattern | History and filtered project endpoints use Spatie index query builders to keep filter, sort, and eager-loading logic out of controllers. |
| Done | Reference option response caching | Performance Considerations | Company, employee, project, task, and employee-filtered project option reads use versioned cache keys; relevant write actions invalidate the affected company or company list. |

## Must Have: Backend Validation And Invariants

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | DTO validation for batch payload | Boilerplate Pattern | Uses `TimeEntryBatchData` instead of inline controller validation. |
| Done | Required field validation | Goal | Company, date, employee, project, task, and hours are required per row. |
| Done | Date validation | Goal | Entry date must be a valid date. |
| Done | Hours validation | Goal | Hours must be numeric and greater than zero. |
| Done | Employee company membership validation | Goal | API rejects employee outside selected company. |
| Done | Project company validation | Goal | API rejects project outside selected company. |
| Done | Task company validation | Goal | API rejects task outside selected company. |
| Done | Employee project assignment validation | Required Relationships | API rejects project not assigned to selected employee. |
| Done | Company-scoped one project per employee per date validation | Business Rules | API rejects different projects for the same company/employee/date while allowing shared employees to work under different companies on that date. |
| Done | Multiple distinct tasks same project/date allowed | Business Rules | API allows same employee/date/project with different tasks. |
| Done | Duplicate same-task validation | Business Rules | API rejects duplicate company/employee/date/project/task rows within a submitted batch and against existing entries. |
| Done | Batch internal conflict validation | Business Rules | API rejects conflicting projects within the submitted batch. |
| Done | Existing data conflict validation | Business Rules | API rejects conflicts with already-saved entries. |
| Done | Transactional batch creation | Business Rules | A batch saves atomically; invalid batch persists nothing. |
| Done | Row-field JSON validation errors | Bonus / UX Baseline | 422 errors are keyed like `entries.0.project_id`. |

## Must Have: Frontend Application Shell

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | SPA app shell | Interface Requirements | Vue app mounts successfully from Laravel/Vite. |
| Done | Branded CVA component variants | Design Direction, shadcn-vue Foundation | Base UI primitives expose CVA variants aligned to `docs/design.md`: near-black canvas, charcoal surfaces, hairline borders, lavender focus/primary states, compact 8px controls. |
| Done | shadcn-vue base primitives | Frontend Component Inventory | Added required primitives: `button`, `input`, `badge`, `alert`, `table`, `popover`, `command`, `tooltip`, and `separator` before composing domain controls. |
| Done | Domain app components | Frontend Component Inventory | Built `WorkspaceHeader`, `ScopeCompanySelect`, `RouteTabs`, and `ApiErrorBanner` from shadcn-vue primitives. |
| Done | Top-level company selector | Interface Requirements | Selector appears above both views and loads company options from `GET /api/v1/companies`. |
| Done | All default option | Interface Requirements | Default scope is `All companies`. |
| Done | Route-backed tab navigation | Interface Requirements | New Entries and History appear as two tabs. |
| Done | Scope state shared across views | Interface Requirements | Company selection is owned by `App.vue` and passed into New Entries and History route views. |
| Done | Clean usable visual design | UX / Design Expectations | Dark Linear-inspired shell, branded primitives, dense spreadsheet surfaces, keyboard pickers, validation states, and history states are in place. |

## Must Have: New Entries Frontend

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | Spreadsheet component foundation | Frontend Component Inventory, UX / Design Expectations | Built `TimeEntrySpreadsheet`, `SpreadsheetToolbar`, `SpreadsheetCell`, `SpreadsheetSelectCell`, and branded cell CVA variants. |
| Done | Spreadsheet keyboard controller | UX / Design Expectations | `useSpreadsheetNavigation` handles Tab, Shift+Tab, Enter, arrows, active-cell state, DOM focus movement, keyboard-open trigger cells, and last-cell add-row behavior. |
| Done | Editable table | New Entries Tab | Each row represents one new time entry with selectable company/employee/project/task, date input, and hours input. |
| Done | Required field order | New Entries Tab | Columns are Company, Date, Employee, Project, Task, Hours. |
| Done | Add row control | New Entries Tab | User can add multiple rows before submitting. |
| Done | Batch submit control | New Entries Tab | Sends non-empty rows to `POST /api/v1/time-entries`. |
| Done | Company-scoped employee loading | New Entries Tab | Employee dropdown depends on row company. |
| Done | Company-scoped project loading | New Entries Tab | Project dropdown depends on row company. |
| Done | Employee-filtered project loading | Required Relationships | Project dropdown respects selected employee assignment. |
| Done | Company-scoped task loading | New Entries Tab | Task dropdown depends on row company. |
| Done | Dependent field clearing | New Entries Tab | Changing company clears employee/project/task; changing employee clears project. |
| Done | Keyboard-friendly tab flow | UX / Design Expectations | User can fill rows efficiently with Tab and open select/date cells from keyboard. |
| Done | Frontend invalid-combination prevention | Goal | UI only offers company-scoped employees/projects/tasks and employee-assigned project options. |
| Done | Row-level validation display | Bonus / UX Baseline | Backend validation errors are remapped to original row indexes and shown beside affected fields. |
| Done | Successful submit state | New Entries Tab | Saved rows reset after success and the spreadsheet shows a compact saved confirmation. |

## Must Have: History Frontend

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | Read-only history table | History Tab | Lists submitted time entries from `GET /api/v1/time-entries`. |
| Done | History core columns | History Tab | Shows Company, Date, Employee, Project, Task, Hours. |
| Done | All-company history | Interface Requirements | Shows entries across all companies when scope is All. |
| Done | Company-filtered history | Interface Requirements | Shows only selected company when scope is specific. |
| Done | Refresh after submit | New Entries Tab, History Tab | Newly saved entries appear in History after batch submit. |
| Done | Empty state | UX / Design Expectations | Clear display when no entries exist for the current scope. |

## Must Have: Tests

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | Relationship tests | Evaluation Criteria | Proves required model relationships and appended time-entry display fields work. |
| Done | Company options API test | API Requirements | Endpoint returns expected company resource shape. |
| Done | Company employees API test | New Entries Tab | Endpoint returns only employees for selected company. |
| Done | Company projects API test | New Entries Tab | Endpoint returns only projects for selected company. |
| Done | Employee-filtered projects API test | Required Relationships | Endpoint returns only projects assigned to selected employee. |
| Done | Company tasks API test | New Entries Tab | Endpoint returns only tasks for selected company. |
| Done | History API test | History Tab | Endpoint returns saved entries with required related labels. |
| Done | Company-filtered history API test | Interface Requirements | Endpoint respects specific company filter. |
| Done | Valid batch create API test | Business Rules | Valid batch persists and returns created resources. |
| Done | Multiple distinct tasks same project/date test | Business Rules | Same employee/date/project with different tasks is accepted. |
| Done | Duplicate same-task invariant tests | Business Rules | Duplicate employee/date/project/task rows are rejected within a batch and against existing entries. |
| Done | Invalid employee/company test | Goal | API rejects employee outside company. |
| Done | Invalid project/company test | Goal | API rejects project outside company. |
| Done | Invalid task/company test | Goal | API rejects task outside company. |
| Done | Invalid employee/project assignment test | Required Relationships | API rejects unassigned project for employee. |
| Done | One-project-per-day conflict test | Business Rules | API rejects different project for same employee/date. |
| Done | Invalid hours/date tests | Goal | API rejects invalid hours and dates. |
| Done | Spreadsheet keyboard E2E tests | Testing Decisions | Playwright covers keyboard commit flow, popover calendar navigation, and tab exit from an open picker. |
| Done | History E2E tests | Testing Decisions | Playwright covers API-backed history listing, global company scope filtering, and refresh after a new submit. |
| Done | Frontend invariant validation E2E tests | Testing Decisions | Playwright submits duplicate task rows and verifies the backend 422 invariant error renders beside the affected task field in both rows. |

## Must Have: Documentation And Submission

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | README setup commands | Submission Requirements | Documents composer/npm/env/key/migrate/seed/dev, build, and test commands. |
| Done | README seed data notes | Submission Requirements | Explains demo companies, shared employee behavior, projects, tasks, assignments, and seeded history. |
| Done | README business rules | Evaluation Criteria | Documents relationship validation, company-scoped one-project-per-date, multiple distinct tasks, and duplicate-task rejection. |
| Done | Manual invariant testing guide | Evaluation Criteria | `docs/manual_testing_guide.md` provides seeded-data UI/API checks for relationship rules, business invariants, history behavior, keyboard UX, and AI drafts. |
| Done | README performance notes | Performance Considerations | Documents scoped option loading, frontend memoization, query builders, indexes, and pagination tradeoff. |
| Done | API documentation | API Requirements, Submission Quality | Scramble is installed and exposes interactive API documentation at `/docs/api` plus OpenAPI JSON at `/docs/api.json`. |
| Done | Frontend component inventory | Interface Requirements, UX / Design Expectations | `docs/frontend_component_inventory.md` maps the required and bonus frontend components with spreadsheet-style keyboard entry as the main interaction. |
| Done | AI implementation package preparation | Super Bonus: AI-Assisted Entry | `laravel/ai` is installed with config, stubs, and conversation-store migration; `.env.example` includes the OpenAI provider placeholder. |
| Done | Local debug tooling | Development Quality | Laravel Debugbar is installed as a dev dependency with published config and `.env.example` toggle. |
| Done | README AI usage note | Submission Requirements | Points to the expected `docs/ai-conversation.json` export path. |
| In Progress | README AI-assisted entry notes | Super Bonus: AI-Assisted Entry | Documents structured draft-row generation, env-selectable provider/model settings, local-gateway DeepSeek adapter compatibility, and the SSE streaming decision for Laravel AI. |
| Not Started | AI conversation JSON export | Submission Requirements | Include JSON export, preferably `docs/ai-conversation.json`. |
| Not Started | GitHub-ready repository | Submission Requirements | Repo contains backend, frontend, migrations, seeders, endpoints, README, AI export. |

## Bonus

| Status | Feature | Source Requirement | Acceptance Notes |
| --- | --- | --- | --- |
| Done | Edit existing entries | Bonus: Edit Existing Entries | History rows expose an edit action backed by `PATCH /api/v1/time-entries/{timeEntry}`; updates reuse DTO/action flow and backend invariants. |
| Done | Faster data entry helpers | Bonus: Faster Data Entry | Duplicate active row (Ctrl+D), add row (Ctrl+Shift+Enter), clear rows, and keyboard-driven spreadsheet flow are implemented. Auto-fill next row with previous values is the remaining optional improvement. |
| Done | Enhanced validation UX | Bonus: Better Validation UX | Row-level backend errors render beside affected cells with destructive styling and "Needs fix" status badge; E2E test verifies duplicate-task 422 errors display beside the task field in both affected rows. |
| Done | Summary totals | Bonus: Summary Totals | History API returns unpaginated filtered summary totals for total hours plus company, employee, project, task, and date groups; the History page renders expandable grouped sections with icons (Building2, Users, Folder, ClipboardList), top-5 preview, show more/less toggle, and count badges. |
| Done | History search | Bonus: History Table Improvements | Prefix search across company, employee, project, and task labels through the history API, backed by portable B-tree name indexes. |
| Done | History sorting | Bonus: History Table Improvements | Sort visible history rows by date, employee, project, task, or hours. |
| In Progress | History filtering beyond company scope | Bonus: History Table Improvements | Single search field filters across company, employee, project, and task labels via prefix match; date range filtering remains optional. |
| Done | History pagination | Bonus: History Table Improvements | History API uses `TimeEntryIndexQuery::jsonPaginate()` with `page` and `per_page`; the History page renders previous/next controls with pagination metadata. |
| Done | Keyboard shortcuts | Bonus: Keyboard Shortcuts | Header popover legend documents shortcuts; app supports `?` to open shortcut legend, Alt+N/Alt+H tab switching, Alt+E spreadsheet focus, Alt+S history search focus, plus spreadsheet Tab, Shift+Tab, Enter, arrows, picker opening, duplicate row, add row, and submit batch shortcuts. |
| In Progress | AI-assisted entry | Super Bonus: AI-Assisted Entry | Plain-English input uses Laravel AI structured output to draft spreadsheet rows; rows remain editable and final persistence still goes through the normal batch create action. |

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
