# White Code Labs Time Entry

Laravel 13 API with a Vue 3 Router SPA for creating and viewing employee time entries. The app is intentionally small, but the create path enforces the relationship and business-rule invariants on the backend.

## Stack

- Laravel 13
- Vue 3 Composition API with Vue Router
- SQLite by default
- Spatie Laravel Data
- Spatie Query Builder
- Scramble API documentation
- Laravel AI SDK
- Laravel Debugbar for local query/debug inspection
- shadcn-vue primitives with CVA styling
- Playwright for frontend regression coverage

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build
```

Run the Laravel app:

```bash
php artisan serve
```

For frontend development in a second terminal:

```bash
npm run dev
```

Open the app at the Laravel URL, usually `http://127.0.0.1:8000`.

## Test Commands

```bash
php artisan test
vendor/bin/pint --dirty
npm run test:e2e
```

Playwright derives its base URL from `PLAYWRIGHT_BASE_URL`, `APP_URL`, or `.env`; it defaults to `http://127.0.0.1:8000`.

## API Documentation

Scramble exposes interactive API docs at:

```text
/docs/api
```

The OpenAPI JSON is available at:

```text
/docs/api.json
```

## Seed Data

The database seeder creates two demo companies:

- Acme Operations
- Globex Services

It also creates employees, projects, tasks, project assignments, and sample history entries. Cora Diaz is a shared employee across both companies so the company-scoped behavior can be tested.

All seed writes go through action classes and Spatie Data DTOs.

## Business Rules

Every time entry has:

- Company
- Date
- Employee
- Project
- Task
- Hours

The backend enforces:

- The employee must belong to the selected company.
- The project must belong to the selected company.
- The task must belong to the selected company.
- The employee must be assigned to the selected project.
- Within one company, an employee can only work on one project per date.
- Across different companies, a shared employee may work on different projects on the same date.
- The employee may work on multiple distinct tasks for the same company, date, and project.
- Exact duplicate task rows are rejected for the same company, employee, date, project, and task.

The duplicate-task rule is an added data-integrity decision. The assignment allows multiple tasks for one project/date; this implementation treats those as distinct task rows and rejects exact duplicates because they make history and totals ambiguous. See [ADR 001](docs/adr/001-time-entry-invariants.md).

## Frontend UX

The New Entries page uses a spreadsheet-style grid backed by real API option data.

Keyboard support:

- `Alt+N`: switch to New Entries.
- `Alt+H`: switch to History.
- `Alt+E`: switch to New Entries and focus the first spreadsheet cell.
- `Tab` / `Shift+Tab`: move across cells.
- `Enter`: move down in normal cells.
- `Enter`, `Space`, or `F2`: open select and date cells.
- Arrow keys: move through spreadsheet cells and calendar dates.
- `Ctrl/Cmd+Enter`: submit the batch.
- `Ctrl/Cmd+Shift+Enter`: add a row and focus the new row.
- `Ctrl/Cmd+D`: duplicate the active row and focus the copied row's hours cell.

After selecting a date, the cell stores `YYYY-MM-DD` for the API but renders a human-friendly label such as `Jan 15, 2026`.

The History page includes paginated API-backed search across company, employee, project, and task labels. Visible rows can be sorted by date, employee, project, task, or hours, and existing entries can be edited from the row action. Summary totals come from the API for the full filtered result set, not only the visible page.

## AI Preparation

The Laravel AI SDK is installed and configured through `config/ai.php`. The default provider is OpenAI; set `OPENAI_API_KEY` in `.env` before building the AI-assisted entry bonus.

Laravel Debugbar is installed as a dev dependency for local query and request inspection. Set `DEBUGBAR_ENABLED=true` in `.env` when local debugging is needed.

## Performance Notes

- Option endpoints are company-scoped so employees, projects, and tasks are not loaded globally into each row.
- Project options can be filtered by `filter[employee_id]` to reflect employee assignment.
- The frontend memoizes loaded company option buckets and employee-filtered project lists during the page session.
- History uses Spatie Query Builder for filtering by `filter[company_id]` and prefix search through `filter[search]`.
- Database indexes support the main lookup paths: company/date, employee/date, project/date, task, exact duplicate task guarding, and searchable label names.
- Search is prefix-based (`term%`) so normal B-tree indexes remain useful across SQLite and MySQL. SQLite FTS5 would be the better option for true full-text or contains search, but it would require virtual tables and sync triggers that are beyond the useful scope of this take-home slice.
- The history endpoint uses resource pagination through `TimeEntryIndexQuery::jsonPaginate()` and accepts `page` plus `per_page`.
- History summary totals are calculated from the same filtered query with pagination removed, so total hours and grouped totals stay correct across pages.

## AI Usage Export

The assignment asks for a JSON export of the AI conversation used during development. Add it to:

```text
docs/ai-conversation.json
```

This repository already includes the human-readable planning and decision documents under `docs/`.
