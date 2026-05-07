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

## Feature Summary

- Spreadsheet-style batch entry with company, date, employee, project, task, and hours fields.
- Company-scoped employee, project, and task dropdowns backed by API endpoints.
- Employee-filtered project options through `filter[employee_id]`.
- Backend validation for relationship rules and one-project-per-employee-per-date invariants.
- History table with company scope, search, sorting, pagination, summary totals, and edit actions.
- Keyboard-first workflow with shortcut legend, row duplication, row deletion, and batch submit shortcuts.
- AI-assisted draft rows from plain-English input, with field-level review warnings before normal submission.
- Interactive API docs through Scramble.

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

## Quick Start

```bash
composer run dev
```

This starts the Laravel server, queue listener, log tail, and Vite dev server together.

If you prefer separate terminals:

```bash
php artisan serve
npm run dev
```

Open the app at the Laravel URL, usually `http://127.0.0.1:8000`.

## Environment

Key local variables from `.env.example`:

| Variable | Purpose |
| --- | --- |
| `APP_URL` | Base URL used by Laravel and Playwright when `PLAYWRIGHT_BASE_URL` is not set. |
| `DB_CONNECTION=sqlite` | Default local database driver. |
| `CACHE_STORE=database` | Stores versioned reference-option cache keys. |
| `DEBUGBAR_ENABLED` | Enables Laravel Debugbar locally when set to `true`. |
| `OPENAI_API_KEY` / `OPENAI_URL` | Optional OpenAI provider credentials. |
| `DEEPSEEK_API_KEY` / `DEEPSEEK_URL` | Optional DeepSeek or local OpenAI-compatible gateway credentials. |
| `AI_TIME_ENTRY_DRAFT_PROVIDER` | Provider used by AI-assisted draft generation; defaults to `deepseek`. |
| `AI_TIME_ENTRY_DRAFT_MODEL` | Optional model override for AI-assisted draft generation. |
| `AI_TIME_ENTRY_DRAFT_REAL_TEST` | Opt-in flag for the real provider AI test. |

## Test Commands

```bash
php artisan test
vendor/bin/pint --dirty
npm run test:e2e
```

Playwright derives its base URL from `PLAYWRIGHT_BASE_URL`, `APP_URL`, or `.env`; it defaults to `http://127.0.0.1:8000`.

## Manual QA Guide

For human QA, use [docs/manual_testing_guide.md](docs/manual_testing_guide.md). It covers:

- Relationship checks for company-scoped employees, projects, and tasks.
- Business invariant checks for one-project-per-employee-per-date and multi-task entries.
- Backend tampering checks for invalid employee/project/task combinations.
- History search, pagination, totals, and edit behavior.
- Keyboard-only entry flow and AI-assisted draft checks.

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

The duplicate-task rule is an added data-integrity decision. The assignment allows multiple tasks for one project/date; this implementation treats those as distinct task rows and rejects exact duplicates because they make history and totals ambiguous.

## Frontend UX

The New Entries page uses a spreadsheet-style grid backed by real API option data.

Keyboard support:

| Shortcut | Description |
| --- | --- |
| `Alt+N` | Switch to New Entries |
| `Alt+H` | Switch to History |
| `Alt+E` | Switch to Spreadsheet and focus the first cell |
| `Alt+S` | Focus the search field on the history page |
| `Shift+/` | Open keyboard shortcut legend |
| `Ctrl/Cmd+Enter` | Submit the current batch of entries |
| `Ctrl/Cmd+D` | Duplicate the active row |
| `Ctrl/Cmd+Shift+Enter` | Add a row and focus the new row |
| `Ctrl/Cmd+Shift+Backspace` | Delete the active row |
| Arrow keys `← → ↑ ↓` | Move between spreadsheet cells |
| `Tab` / `Shift+Tab` | Next / previous cell, wraps across rows |
| `Enter` | Confirm and move to the cell below |
| `Esc` | Cancel the current cell edit |
| `Enter`, `Space`, `F2` | Open select and date cells |

The full shortcut list is also available in-app via the keyboard icon next to the company dropdown.

After selecting a date, the cell stores `YYYY-MM-DD` for the API but renders a human-friendly label such as `Jan 15, 2026`.

The History page includes paginated API-backed search across company, employee, project, and task labels. Visible rows can be sorted by date, employee, project, task, or hours, and existing entries can be edited from the row action. Summary totals come from the API for the full filtered result set, not only the visible page.

## AI Preparation

- The New Entries page includes an AI-assisted draft box that turns plain-English notes into editable spreadsheet rows.
- Drafting does not save data; final persistence still goes through the normal batch submit and backend validation flow.
- Example seeded-data prompts and a cURL check live in [docs/ai_test_prompts.md](docs/ai_test_prompts.md).
- Configure the draft agent with:

```env
AI_TIME_ENTRY_DRAFT_PROVIDER=deepseek
AI_TIME_ENTRY_DRAFT_MODEL=
AI_TIME_ENTRY_DRAFT_REAL_TEST=false
```

- The app defaults to `deepseek` because it works with OpenAI-compatible local gateways that use chat-completions style APIs.
- Real provider tests are opt-in to avoid accidental external calls:

```bash
AI_TIME_ENTRY_DRAFT_REAL_TEST=true php artisan test --filter=test_ai_time_entry_draft_endpoint_can_call_real_provider
```

## Performance Notes

- Which API responses should be cached?
  - Reference option endpoints are cached: companies, company employees, company projects, company tasks, and employee-filtered project options.
  - Versioned cache keys are invalidated by the relevant write actions.
  - History responses are not cached because they change after create/update and depend on company scope, search, sorting, pagination, and summary totals.
- Are dropdown options being loaded efficiently?
  - Dropdown data is company-scoped and loaded lazily.
  - Employee, project, and task options are fetched only after a company is selected.
  - Project options can be filtered by `filter[employee_id]` so users only see projects assigned to the selected employee.
- Are unnecessary API calls avoided?
  - The frontend memoizes loaded company option buckets and employee-filtered project lists for the current page session.
  - History uses debounced search and server pagination to avoid fetching the full table repeatedly.
- How would this behave with many companies, employees, projects, tasks, and time entries?
  - History uses Spatie Query Builder filters/sorts and indexes for common lookup paths.
  - Search uses prefix matching (`term%`) so normal B-tree indexes remain useful across SQLite and MySQL.
  - For much larger datasets, summary totals would be candidates for caching, async reporting tables, or a dedicated aggregate endpoint.

## AI Usage Export

The AI chat logs used during development are included at:

```text
docs/ai-conversation.json
docs/ai_chat_log/
```

`docs/ai-conversation.json` is the combined JSON export for submission. The `docs/ai_chat_log/` folder also keeps the source JSONL session exports alongside the human-readable planning and decision documents under `docs/`.

## Submission Checklist

Extracted from [docs/main_spec.md](docs/main_spec.md):

- [x] Laravel backend code.
- [x] Vue 3 frontend code using the Composition API.
- [x] Migrations for companies, employees, projects, tasks, time entries, and required pivots.
- [x] Models, relationships, factories, and seeders.
- [x] API endpoints for company-scoped options, history, create/update time entries, and AI draft rows.
- [x] New Entries tab with API-backed dependent dropdowns.
- [x] History tab with clear submitted entry details.
- [x] Backend validation prevents invalid employee, company, project, task, and assignment combinations.
- [x] Backend validation enforces one project per employee per company/date.
- [x] Multiple tasks for the same employee/project/date are supported.
- [x] Keyboard-friendly data entry and faster-entry shortcuts.
- [x] README setup and testing instructions.
- [x] AI chat log export at `docs/ai-conversation.json`, with source JSONL logs under `docs/ai_chat_log/`.
