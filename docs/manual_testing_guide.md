# Manual Testing Guide

Use this checklist after a fresh seed to confirm the requirements and invariants from `docs/main_spec.md`.

## Reset And Run

```bash
php artisan migrate:fresh --seed
php artisan serve
npm run dev
```

Open the Laravel app URL, usually `http://127.0.0.1:8000`.

## Seed Reference

| Company | Employees | Projects | Tasks |
| --- | --- | --- | --- |
| Acme Operations | Ava Chen, Ben Carter, Cora Diaz | Website Redesign, Mobile Launch | Planning, Development, Review |
| Globex Services | Cora Diaz, Dev Malik | Data Migration, Client Support | Discovery, Cleanup, Support |

Project assignments:

| Employee | Company | Assigned Projects |
| --- | --- | --- |
| Ava Chen | Acme Operations | Website Redesign, Mobile Launch |
| Ben Carter | Acme Operations | Website Redesign |
| Cora Diaz | Acme Operations | Mobile Launch |
| Cora Diaz | Globex Services | Data Migration |
| Dev Malik | Globex Services | Data Migration, Client Support |

Seeded history:

| Company | Date | Employee | Project | Tasks |
| --- | --- | --- | --- | --- |
| Acme Operations | 2026-01-05 | Ava Chen | Website Redesign | Development, Review |
| Globex Services | 2026-01-06 | Dev Malik | Client Support | Support |
| Globex Services | 2026-01-07 | Cora Diaz | Data Migration | Cleanup |

## UI Relationship Checks

### 1. Global Scope Defaults To All

1. Open the app.
2. Confirm the company selector says `All companies`.
3. Open History.
4. Confirm Acme and Globex rows are visible.
5. Select `Acme Operations`.
6. Confirm History only shows Acme rows.

Expected: global scope affects History and pre-fills new rows where applicable.

### 2. Row Company Controls Dependent Options

1. Go to New Entries.
2. In row 1 choose `Acme Operations`.
3. Open Employee.

Expected: only `Ava Chen`, `Ben Carter`, and `Cora Diaz` appear. `Dev Malik` must not appear.

4. Open Task.

Expected: only `Planning`, `Development`, and `Review` appear. Globex tasks must not appear.

### 3. Employee Controls Project Options

1. In an Acme row, choose employee `Ben Carter`.
2. Open Project.

Expected: only `Website Redesign` appears because Ben is only assigned to that project.

3. Change employee to `Cora Diaz`.
4. Open Project.

Expected: only `Mobile Launch` appears for Cora under Acme.

## Business Invariant Checks

### 4. Valid Multiple Tasks Same Project And Date

1. Add two rows.
2. Use:

| Row | Company | Date | Employee | Project | Task | Hours |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Acme Operations | Any non-seeded date, for example 2026-02-01 | Ava Chen | Website Redesign | Planning | 1.00 |
| 2 | Acme Operations | Same date | Ava Chen | Website Redesign | Development | 2.00 |

3. Submit.

Expected: save succeeds. History shows both rows. This proves multiple tasks are allowed when company, employee, date, and project are the same.

### 5. Reject Different Projects Same Employee And Date

1. Add two rows.
2. Use:

| Row | Company | Date | Employee | Project | Task | Hours |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Acme Operations | 2026-02-02 | Ava Chen | Website Redesign | Planning | 1.00 |
| 2 | Acme Operations | 2026-02-02 | Ava Chen | Mobile Launch | Development | 2.00 |

3. Submit.

Expected: save is rejected. Both affected Project cells show row-level validation saying an employee can only work on one project per date.

### 6. Reject Existing Database Conflict

1. Confirm seeded history has Ava Chen on `Website Redesign` for `2026-01-05`.
2. New Entries: create a row:

| Company | Date | Employee | Project | Task | Hours |
| --- | --- | --- | --- | --- | --- |
| Acme Operations | 2026-01-05 | Ava Chen | Mobile Launch | Planning | 1.00 |

3. Submit.

Expected: save is rejected with a Project field error because Ava already has a different Acme project on that date.

### 7. Allow Same Employee Different Company Same Date

1. Add two rows for shared employee `Cora Diaz`.
2. Use:

| Row | Company | Date | Employee | Project | Task | Hours |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Acme Operations | 2026-02-03 | Cora Diaz | Mobile Launch | Planning | 1.00 |
| 2 | Globex Services | 2026-02-03 | Cora Diaz | Data Migration | Discovery | 2.00 |

3. Submit.

Expected: save succeeds. This confirms the one-project-per-date rule is company-scoped.

### 8. Reject Exact Duplicate Task Row

1. Add two rows.
2. Use identical values:

| Company | Date | Employee | Project | Task | Hours |
| --- | --- | --- | --- | --- | --- |
| Acme Operations | 2026-02-04 | Ava Chen | Website Redesign | Planning | 1.00 |

3. Submit.

Expected: save is rejected. Both Task cells show duplicate-task validation. This is an added data-integrity rule documented in ADR 001.

## Backend-Only Relationship Checks

The frontend prevents most invalid combinations by hiding unavailable options. Use the API to confirm the backend also rejects tampered payloads.

First get IDs:

```bash
php artisan tinker
```

```php
$acme = App\Models\Company::where('name', 'Acme Operations')->first();
$globex = App\Models\Company::where('name', 'Globex Services')->first();
$dev = App\Models\Employee::where('name', 'Dev Malik')->first();
$ben = App\Models\Employee::where('name', 'Ben Carter')->first();
$mobile = App\Models\Project::where('name', 'Mobile Launch')->first();
$support = App\Models\Task::where('name', 'Support')->first();
$planning = App\Models\Task::where('name', 'Planning')->first();
```

### 9. Employee Must Belong To Company

Submit Dev Malik under Acme:

```bash
curl -X POST http://127.0.0.1:8000/api/v1/time-entries \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"entries":[{"company_id":"ACME_ID","employee_id":"DEV_ID","project_id":"MOBILE_ID","task_id":"PLANNING_ID","entry_date":"2026-02-05","hours":"1.00"}]}'
```

Expected: HTTP 422 with `errors.entries.0.employee_id`.

### 10. Project Must Belong To Company

Submit a Globex project under Acme.

Expected: HTTP 422 with `errors.entries.0.project_id`.

### 11. Task Must Belong To Company

Submit Globex `Support` task under Acme.

Expected: HTTP 422 with `errors.entries.0.task_id`.

### 12. Employee Must Be Assigned To Project

Submit Ben Carter under Acme `Mobile Launch`.

Expected: HTTP 422 with `errors.entries.0.project_id`.

## History And Bonus Checks

### 13. History Search, Pagination, And Totals

1. Open History.
2. Search `Website`.
3. Confirm rows and summary totals update.
4. Change page size or use pagination if enough rows exist.

Expected: summary totals describe the full filtered result set, not only the visible page.

### 14. Edit Existing Entry

1. Open History.
2. Edit a row.
3. Change task or hours to a valid value.
4. Save.

Expected: row updates and toast appears.

5. Try editing an entry into a known conflict, such as Ava on a different project for `2026-01-05`.

Expected: backend validation rejects the edit.

### 15. Keyboard-Only Data Entry

Confirm these shortcuts:

| Shortcut | Expected |
| --- | --- |
| `Shift + /` | Opens shortcut legend |
| `Alt + N` | New Entries |
| `Alt + H` | History |
| `Alt + E` | Focus spreadsheet |
| `Tab` / `Shift + Tab` | Move cell focus |
| `Enter`, `Space`, `F2` | Open select/date cell |
| `Ctrl/Cmd + D` | Duplicate active row |
| `Ctrl/Cmd + Shift + Enter` | Add row |
| `Ctrl/Cmd + Shift + Backspace` | Delete active row |
| `Ctrl/Cmd + Enter` | Submit batch |

## AI-Assisted Draft Check

1. New Entries: enter:

```text
Ava worked 1 hour on Planning for Website Redesign on 2026-02-06.
```

2. Click `Draft rows`.

Expected: a draft row appears with matched fields.

3. Try an incomplete prompt:

```text
Ava worked on something yesterday.
```

Expected: unresolved AI warnings appear under relevant fields. Nothing is saved until the normal batch submit succeeds.
