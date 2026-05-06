# AI Time Entry Draft — Test Prompts

Use these prompts against the `/api/v1/ai/time-entry-drafts` endpoint to verify
multi-line parsing against seeded data.

## Seed data reference

### Companies
- Acme Operations
- Globex Services

### Employees
| Employee      | Companies                        |
|---------------|----------------------------------|
| Ava Chen      | Acme Operations                  |
| Ben Carter    | Acme Operations                  |
| Cora Diaz     | Acme Operations, Globex Services |
| Dev Malik     | Globex Services                  |

### Projects
| Project           | Company          | Assigned employees        |
|-------------------|------------------|---------------------------|
| Website Redesign  | Acme Operations  | Ava Chen, Ben Carter      |
| Mobile Launch     | Acme Operations  | Ava Chen, Cora Diaz       |
| Data Migration    | Globex Services  | Cora Diaz, Dev Malik      |
| Client Support    | Globex Services  | Dev Malik                 |

### Tasks
| Task         | Company          |
|--------------|------------------|
| Planning     | Acme Operations  |
| Development  | Acme Operations  |
| Review       | Acme Operations  |
| Discovery    | Globex Services  |
| Cleanup      | Globex Services  |
| Support      | Globex Services  |

---

## Prompt 1 — Multi-line, single company (Acme)

```
Log the following for Acme Operations:

Ava Chen worked 3 hours on Development for Website Redesign on Jan 20 2026.
Ben Carter worked 5.5 hours on Review for Website Redesign on Jan 20 2026.
Cora Diaz worked 2 hours on Development for Mobile Launch on Jan 21 2026.
```

Expected: 3 rows, all under Acme. All employees/projects/tasks resolve.

---

## Prompt 2 — Multi-line, mixed companies

```
Need to log these:

- Ava Chen, Acme Operations, Website Redesign, Planning, 1.5h, Jan 22 2026
- Dev Malik, Globex Services, Client Support, Support, 4h, Jan 22 2026
- Cora Diaz, Globex Services, Data Migration, Cleanup, 6h, Jan 23 2026
- Ben Carter, Acme Operations, Mobile Launch, Review, 2.25h, Jan 23 2026
```

Expected: 4 rows across 2 companies. Ben Carter project/task resolve or warn (Ben not assigned to Mobile Launch).

---

## Prompt 3 — Ambiguous / missing data (warning-heavy)

```
Ava Chen did 3 hours of work on Jan 24.
Someone worked 4 hours on Development at Acme on Jan 24.
Dev Malik worked on Data Migration for 2 hours yesterday.
```

Expected warnings:
- Row 1: missing project, missing task
- Row 2: missing employee (ambiguous "someone")
- Row 3: missing task (Development is Acme, but Dev is Globex)

---

## Prompt 4 — With selected_company_id override

POST body:
```json
{
  "company_id": 1,
  "prompt": "Ava Chen worked 2 hours on Development for Website Redesign on Jan 25 2026.\nBen Carter worked 4 hours on Review for Website Redesign on Jan 25 2026."
}
```

Expected: company_id forced to Acme (1) on both rows regardless of text.

---

## Prompt 5 — Single entry, all fields present

```
Cora Diaz worked 7.5 hours on Cleanup for Data Migration at Globex Services on January 26, 2026.
```

Expected: 1 row, Globex, all fields resolved, no warnings.

---

## cURL quick test

```bash
curl -s http://localhost:8000/api/v1/ai/time-entry-drafts \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "prompt": "Log the following for Acme Operations:\n\nAva Chen worked 3 hours on Development for Website Redesign on Jan 20 2026.\nBen Carter worked 5.5 hours on Review for Website Redesign on Jan 20 2026.\nCora Diaz worked 2 hours on Development for Mobile Launch on Jan 21 2026."
  }' | jq .
```
