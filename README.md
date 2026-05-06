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

| Shortcut | Description |
| --- | --- |
| `Alt+N` | Switch to New Entries |
| `Alt+H` | Switch to History |
| `Alt+E` | Switch to Spreadsheet and focus the first cell |
| `Alt+S` | Focus the search field on the history page |
| `?` | Open keyboard shortcut legend |
| `Ctrl/Cmd+Enter` | Submit the current batch of entries |
| `Ctrl/Cmd+D` | Duplicate the active row |
| `Ctrl/Cmd+Shift+Enter` | Add a row and focus the new row |
| Arrow keys `← → ↑ ↓` | Move between spreadsheet cells |
| `Tab` / `Shift+Tab` | Next / previous cell, wraps across rows |
| `Enter` | Confirm and move to the cell below |
| `Esc` | Cancel the current cell edit |
| `Enter`, `Space`, `F2` | Open select and date cells |

The full shortcut list is also available in-app via the keyboard icon next to the company dropdown.

After selecting a date, the cell stores `YYYY-MM-DD` for the API but renders a human-friendly label such as `Jan 15, 2026`.

The History page includes paginated API-backed search across company, employee, project, and task labels. Visible rows can be sorted by date, employee, project, task, or hours, and existing entries can be edited from the row action. Summary totals come from the API for the full filtered result set, not only the visible page.

## AI Preparation

The Laravel AI SDK is installed and configured through `config/ai.php`. Set the API key for the configured provider before using AI-assisted entry.

The New Entries page includes an AI-assisted draft box. It parses plain-English notes into spreadsheet rows and does not save anything by itself. The generated rows are reviewed in the normal spreadsheet and submitted through the existing batch create endpoint, so the same backend invariant validation still applies.

The time-entry draft agent provider and model are env-selectable:

```env
AI_TIME_ENTRY_DRAFT_PROVIDER=deepseek
AI_TIME_ENTRY_DRAFT_MODEL=
AI_TIME_ENTRY_DRAFT_REAL_TEST=false
```

`AI_TIME_ENTRY_DRAFT_PROVIDER` may be any Laravel AI text provider configured in `config/ai.php`. The app defaults this agent to `deepseek` because the local AI gateway is compatible with Laravel AI's DeepSeek adapter, which calls `chat/completions`. `AI_TIME_ENTRY_DRAFT_MODEL` can be any model name accepted by that provider or local gateway. If the model is blank, Laravel AI uses the selected provider's default text model.

The installed Laravel AI OpenAI adapter calls OpenAI's `responses` endpoint. For OpenAI-compatible local gateways that expect the older chat-completion style API, use `AI_TIME_ENTRY_DRAFT_PROVIDER=deepseek` and point `DEEPSEEK_URL` / `DEEPSEEK_API_KEY` at the gateway.

The real provider test is opt-in to avoid accidental external calls:

```bash
AI_TIME_ENTRY_DRAFT_REAL_TEST=true php artisan test --filter=test_ai_time_entry_draft_endpoint_can_call_real_provider
```

Laravel AI supports route-level SSE by returning an agent `stream()` response directly from a route. For this app, the first AI slice intentionally uses structured output instead of token streaming because the useful result is a finite set of spreadsheet rows. SSE can be added later for a conversational assistant, but it is not necessary for draft-row generation.

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
