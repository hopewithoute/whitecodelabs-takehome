# Frontend Component Inventory

This inventory maps the required Laravel + Vue time entry interface to a focused component set. The UI should feel like a compact product tool: dark Linear-inspired surfaces from `docs/design.md`, dense tables, clear focus rings, and spreadsheet-style keyboard flow as the main interaction.

## Design Direction

| Area | Decision |
| --- | --- |
| Visual system | Use the dark canvas and charcoal surface ladder from `docs/design.md`: canvas `#010102`, lifted panels around `#0f1011`, hairline borders, muted gray text. |
| Accent use | Use lavender `#5e6ad2` sparingly for primary actions, active tab state, and keyboard focus rings. |
| Layout | Avoid landing-page composition. The first screen should be the actual time entry workspace. |
| Density | Prioritize scan-friendly operational UI: compact header, sticky table headers, tight rows, stable column widths. |
| Main interaction | Spreadsheet-like row entry with Tab, Shift+Tab, Enter, arrow keys, and add-row behavior. |

## Current Frontend State

| Status | Area | Existing File |
| --- | --- | --- |
| Done | Vue 3 app mount | `resources/js/app.js` |
| Done | Vue Router routes | `resources/js/app.js` |
| Partial | App shell and tabs | `resources/js/App.vue` |
| Placeholder | New Entries view | `resources/js/views/NewEntriesView.vue` |
| Placeholder | History view | `resources/js/views/HistoryView.vue` |
| Partial | shadcn-vue foundation | `components.json`, `resources/js/lib/utils.js`, Tailwind CSS variables |

## Required Component Inventory

### Application Shell

| Priority | Component | Responsibility | Requirement Coverage |
| --- | --- | --- | --- |
| Must | `AppShell` | Owns page frame, dark canvas, max-width workspace, and view outlet. | Interface shell |
| Must | `WorkspaceHeader` | Shows title, compact status/meta, and global actions. | Clean usable design |
| Must | `ScopeCompanySelect` | Top-level company selector with `All` default. Shared across New Entries and History. | Company / All selector |
| Must | `RouteTabs` | Route-backed New Entries and History tabs. | Two tabs |
| Must | `ApiErrorBanner` | Displays non-field API failures without replacing row-level errors. | Validation UX baseline |

### API And State

| Priority | Module | Responsibility | Notes |
| --- | --- | --- | --- |
| Must | `apiClient` | Small wrapper around `fetch` for JSON, 422 parsing, and CSRF-safe defaults if needed. | Keep local, no heavy client library. |
| Must | `useCompanies` | Load company options once. | Used by global scope and row company cells. |
| Must | `useCompanyOptions` | Cache employees, projects, and tasks per company. | Avoid repeated dropdown calls. |
| Must | `useTimeEntries` | Load history, optionally filtered by company. | Uses `filter[company_id]`. |
| Must | `useBatchSubmit` | Submit `entries[]`, map 422 errors back to row/cell keys. | Depends on row-level keys like `entries.1.project_id`. |
| Bonus | `useHistoryFilters` | Search, sort, date range, or extra filters. | Bonus history improvements. |

### Spreadsheet Entry Surface

| Priority | Component | Responsibility | Requirement Coverage |
| --- | --- | --- | --- |
| Must | `TimeEntrySpreadsheet` | Owns rows, active cell, keyboard navigation, paste-ready structure, and submit state. | New Entries tab |
| Must | `SpreadsheetToolbar` | Add row, submit batch, duplicate selected row, clear saved rows. | Add row, submit, faster entry bonus |
| Must | `SpreadsheetHeaderRow` | Stable column headers in required order: Company, Date, Employee, Project, Task, Hours. | Required field order |
| Must | `TimeEntryRow` | Renders one editable row and row-level status. | Each row is one entry |
| Must | `SpreadsheetCell` | Generic focusable cell wrapper with roving tabindex and error state. | Keyboard-friendly grid |
| Must | `CompanyCellEditor` | Company combobox/select. Changing company clears employee, project, task. | Relationship enforcement |
| Must | `DateCellEditor` | Date input with keyboard-friendly native date fallback. | Date entry |
| Must | `EmployeeCellEditor` | Company-scoped employee picker. Disabled until company selected. | Employee depends on company |
| Must | `ProjectCellEditor` | Company-scoped and optionally employee-filtered project picker. Disabled until company and employee selected. | Project depends on company and assignment |
| Must | `TaskCellEditor` | Company-scoped task picker. Disabled until company selected. | Task depends on company |
| Must | `HoursCellEditor` | Numeric hours input, accepts decimals, right-aligned. | Hours entry |
| Must | `CellErrorText` | Compact row/cell error display from backend and frontend checks. | Better validation UX baseline |
| Must | `RowStatusCell` | Unsaved, saving, saved, or invalid indicator. | Submit feedback |
| Bonus | `RowActionMenu` | Duplicate row, insert below, clear row, remove row. | Faster data entry |
| Bonus | `PasteHandler` | Future hook for pasting rows from spreadsheet text. | Faster data entry |

### Keyboard Navigation Controller

| Priority | Behavior | Expected Result |
| --- | --- | --- |
| Must | `Tab` | Move to next editable cell; from last cell of last row, create or focus next row. |
| Must | `Shift+Tab` | Move to previous editable cell. |
| Must | `Enter` | Commit open editor and move down in same column when possible. |
| Must | `ArrowLeft` / `ArrowRight` | Move between cells when editor is closed. |
| Must | `ArrowUp` / `ArrowDown` | Move between rows in same column when editor is closed. |
| Must | `Escape` | Close open combobox/editor and keep focus on the cell. |
| Must | Dependent clearing | Changing company clears employee/project/task; changing employee clears invalid project. |
| Bonus | Duplicate shortcut | Duplicate active row without mouse. Keep this discoverable via button tooltip rather than visible instructional text. |

Implementation note: the controller can be a composable such as `useSpreadsheetNavigation(rows, columns)`. Keep it local to the grid. Do not introduce a global shortcut framework.

### History Surface

| Priority | Component | Responsibility | Requirement Coverage |
| --- | --- | --- | --- |
| Must | `HistoryTable` | Read-only table of time entries. | History tab |
| Must | `HistoryTableHeader` | Columns: Company, Date, Employee, Project, Task, Hours. | Required history columns |
| Must | `HistoryRow` | Displays related labels and formatted hours/date. | Understand each entry clearly |
| Must | `HistoryEmptyState` | Clear empty state for no entries or filtered no results. | UX baseline |
| Must | `HistoryRefreshControl` | Refresh after successful submit or manual reload. | New entries appear in History |
| Bonus | `HistorySummaryBar` | Totals by current scope, employee, or project. | Summary totals |
| Bonus | `HistoryFilterBar` | Search, sort, date range, employee/project/task filters. | History improvements |
| Bonus | `HistoryPagination` | Paginated display if API pagination is added. | Scalability bonus |
| Deferred Bonus | `HistoryInlineEditRow` | Edit existing entries from History. | Edit existing entries |

## shadcn-vue / Base UI Components To Add

Domain components should be built from shadcn-vue primitives instead of hand-rolling base controls. Keep `resources/js/components/ui/*` as generated or lightly adapted primitives, then compose application-specific behavior in `components/app`, `components/spreadsheet`, and `components/history`.

| Priority | Component | Use |
| --- | --- | --- |
| Must | `button` | Add row, submit, duplicate, refresh. |
| Must | `table` | History table and possible base styling for spreadsheet shell. |
| Must | `select` or `combobox` pattern | Company, employee, project, task pickers. Prefer combobox for keyboard search. |
| Must | `popover` + `command` | Searchable option picker if shadcn-vue combobox pattern is used. |
| Must | `input` | Date and hours cells. |
| Must | `badge` | Row status and scope labels. |
| Must | `alert` | Non-field API failures. |
| Must | `tooltip` | Icon-only row actions and shortcut hints. |
| Bonus | `dropdown-menu` | Row action menu. |
| Bonus | `separator` | Compact toolbar grouping. |

Existing dependencies already include `reka-ui`, `class-variance-authority`, `lucide-vue-next`, and `tailwind-merge`, so these components should fit the current setup.

## Primitive-To-Domain Component Mapping

| Domain Component | shadcn-vue Primitive Base | Notes |
| --- | --- | --- |
| `ScopeCompanySelect` | `popover`, `command`, `button`, `badge` | Use a combobox-style picker so `All` and company names are searchable from keyboard. |
| `RouteTabs` | `tabs` or existing `RouterLink` styled like tabs | Router state should remain the source of truth. Use shadcn tabs styling only if it does not fight Vue Router. |
| `ApiErrorBanner` | `alert` | Reserved for non-field errors; field errors stay in cells. |
| `SpreadsheetToolbar` | `button`, `tooltip`, `separator` | Use lucide icons for add, duplicate, clear, submit, refresh. |
| `TimeEntrySpreadsheet` | `table` plus custom grid/focus behavior | Use table primitives for structure, but keep keyboard navigation in `useSpreadsheetNavigation`. |
| `SpreadsheetCell` | custom wrapper around `button`/focusable div | Needs roving tabindex, active-cell styling, and `aria-invalid`; shadcn does not provide this behavior directly. |
| `CompanyCellEditor` | `popover`, `command`, `button` | Combobox over companies, includes current row company selection. |
| `EmployeeCellEditor` | `popover`, `command`, `button` | Combobox over company employees. Disabled until company exists. |
| `ProjectCellEditor` | `popover`, `command`, `button` | Combobox over employee-filtered projects when employee exists; otherwise company projects if needed. |
| `TaskCellEditor` | `popover`, `command`, `button` | Combobox over company tasks. |
| `DateCellEditor` | `input` | Native `type="date"` first for keyboard reliability. |
| `HoursCellEditor` | `input` | Numeric input, right-aligned, decimal-friendly. |
| `CellErrorText` | text utility styles, optionally `tooltip` for clipped text | Keep visible enough for row-level validation; avoid only global errors. |
| `RowStatusCell` | `badge` | Unsaved/saved/invalid/saving states. |
| `RowActionMenu` | `dropdown-menu`, `button`, `tooltip` | Bonus row actions; not required for first functional slice. |
| `HistoryTable` | `table`, `badge`, `button` | Read-only history, refresh control, optional row metadata. |
| `HistorySummaryBar` | `badge`, `separator` | Bonus totals for current scope. |
| `HistoryFilterBar` | `input`, `popover`, `command`, `button` | Bonus search/filter controls. |

## shadcn-vue Add Order

Install only the primitives needed by the next frontend slice, not every possible component up front.

| Step | Add | Enables |
| --- | --- | --- |
| 1 | `button`, `input`, `badge`, `alert`, `table` | App shell polish, static spreadsheet, history table, visible validation states. |
| 2 | `popover`, `command` | Keyboard-searchable company, employee, project, and task pickers. |
| 3 | `tooltip`, `separator` | Icon-only toolbar and compact spreadsheet controls. |
| 4 | `dropdown-menu` | Bonus row action menu. |
| 5 | `tabs` | Optional replacement for current RouterLink tab styling if it improves consistency. |

## Data Contracts Used By Components

| Frontend Need | API Endpoint |
| --- | --- |
| Company scope and row company picker | `GET /api/v1/companies` |
| Row employees | `GET /api/v1/companies/{company}/employees` |
| Row projects | `GET /api/v1/companies/{company}/projects` |
| Employee-filtered projects | `GET /api/v1/companies/{company}/projects?filter[employee_id]={employee}` |
| Row tasks | `GET /api/v1/companies/{company}/tasks` |
| History | `GET /api/v1/time-entries` and `GET /api/v1/time-entries?filter[company_id]={company}` |
| Batch submit | `POST /api/v1/time-entries` with `{ entries: [...] }` |

## Suggested File Layout

```text
resources/js/
├── api/
│   ├── client.js
│   ├── options.js
│   └── timeEntries.js
├── components/
│   ├── app/
│   │   ├── ScopeCompanySelect.vue
│   │   └── WorkspaceHeader.vue
│   ├── history/
│   │   ├── HistoryEmptyState.vue
│   │   ├── HistoryTable.vue
│   │   └── HistorySummaryBar.vue
│   ├── spreadsheet/
│   │   ├── CellErrorText.vue
│   │   ├── SpreadsheetCell.vue
│   │   ├── SpreadsheetToolbar.vue
│   │   ├── TimeEntryRow.vue
│   │   └── TimeEntrySpreadsheet.vue
│   └── ui/
│       └── generated shadcn-vue components
├── composables/
│   ├── useBatchSubmit.js
│   ├── useCompanyOptions.js
│   ├── useSpreadsheetNavigation.js
│   └── useTimeEntries.js
└── views/
    ├── HistoryView.vue
    └── NewEntriesView.vue
```

## Implementation Order

| Step | Deliverable | Why First |
| --- | --- | --- |
| 1 | API client and option-loading composables | The grid depends on scoped dropdown data. |
| 2 | App shell scope selector | Both views need shared company scope. |
| 3 | Spreadsheet static layout | Locks column sizing and keyboard focus model before API complexity. |
| 4 | Cell editors and dependent clearing | Enforces valid combinations before submit. |
| 5 | Batch submit and row-level errors | Connects to backend validation contract. |
| 6 | History table and refresh after submit | Completes create/view loop. |
| 7 | Faster-entry bonuses | Duplicate row, reuse previous values, summary totals, and richer history filters. |

## Acceptance Checklist

| Requirement | Component Coverage |
| --- | --- |
| Two tabs | `RouteTabs`, existing Vue Router |
| Company / All selector | `ScopeCompanySelect` |
| Editable New Entries table | `TimeEntrySpreadsheet` |
| Fields in correct order | `SpreadsheetHeaderRow`, `TimeEntryRow` |
| Add multiple rows | `SpreadsheetToolbar` |
| Submit batch | `useBatchSubmit`, `CreateTimeEntryBatchAction` API |
| Prevent invalid combinations | scoped option composables and dependent clearing |
| Backend row errors visible | `CellErrorText` keyed by `entries.{index}.{field}` |
| Read-only history | `HistoryTable` |
| Company-filtered history | `useTimeEntries` with current scope |
| Keyboard-friendly entry | `useSpreadsheetNavigation` |
| Bonus faster entry | `RowActionMenu`, duplicate row, reuse previous values |
| Bonus validation UX | `CellErrorText`, row status, API error banner |
| Bonus summary totals | `HistorySummaryBar` |
