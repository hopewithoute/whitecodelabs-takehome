# Database Diagram

```text
companies
---------
id (uuid, pk)
name (unique)
created_at
updated_at

    1 ────────────────< projects
    1 ────────────────< tasks
    1 ────────────────< time_entries
    1 ────────────────< company_employee >─────────────── 1 employees
    1 ────────────────< employee_project >─────────────── 1 employees


employees
---------
id (uuid, pk)
name
email (nullable, unique)
created_at
updated_at

    1 ────────────────< time_entries
    1 ────────────────< company_employee >─────────────── 1 companies
    1 ────────────────< employee_project >─────────────── 1 projects


projects
--------
id (uuid, pk)
company_id (uuid, fk -> companies.id)
name
created_at
updated_at

unique(company_id, name)

    1 ────────────────< time_entries
    1 ────────────────< employee_project >─────────────── 1 employees


tasks
-----
id (uuid, pk)
company_id (uuid, fk -> companies.id)
name
created_at
updated_at

unique(company_id, name)

    1 ────────────────< time_entries


company_employee
----------------
id (uuid, pk)
company_id (uuid, fk -> companies.id)
employee_id (uuid, fk -> employees.id)
created_at
updated_at

unique(company_id, employee_id)


employee_project
----------------
id (uuid, pk)
company_id (uuid, fk -> companies.id)
employee_id (uuid, fk -> employees.id)
project_id (uuid, fk -> projects.id)
created_at
updated_at

unique(company_id, employee_id, project_id)


time_entries
------------
id (uuid, pk)
company_id (uuid, fk -> companies.id)
employee_id (uuid, fk -> employees.id)
project_id (uuid, fk -> projects.id)
task_id (uuid, fk -> tasks.id)
entry_date (date)
hours (decimal 5,2)
created_at
updated_at
```

## Relationship Summary

```text
companies          1 ──< projects
companies          1 ──< tasks
companies          1 ──< time_entries
employees          1 ──< time_entries
projects           1 ──< time_entries
tasks              1 ──< time_entries

companies          >──< employees   via company_employee
employees          >──< projects    via employee_project
```

## Business Rule Note

```text
Allowed:

employee A + 2026-01-01 + project X + task 1
employee A + 2026-01-01 + project X + task 2

Rejected by backend validation:

employee A + 2026-01-01 + project X + task 1
employee A + 2026-01-01 + project Y + task 2
```

The one-project-per-employee-per-date rule is intentionally enforced in backend validation instead of a simple database unique constraint, because multiple task rows for the same employee/date/project must remain valid.
