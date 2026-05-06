<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    createTimeEntries,
    getCompanyEmployees,
    getCompanyProjects,
    getCompanyTasks,
} from '@/api/options';
import { spreadsheetColumns, useSpreadsheetNavigation } from '@/composables/useSpreadsheetNavigation';
import SpreadsheetSelectCell from './SpreadsheetSelectCell.vue';
import SpreadsheetToolbar from './SpreadsheetToolbar.vue';

const props = defineProps({
    companies: { type: Array, default: () => [] },
    selectedCompanyId: { type: [String, null], default: null },
});

const rows = ref([makeRow(), makeRow()]);
const errors = ref({});
const submitError = ref(null);
const submitState = ref('idle');
const optionsByCompany = reactive({});
const loadingOptions = reactive({});
const projectsByCompanyEmployee = reactive({});

const rowCount = computed(() => rows.value.length);
const { activeCell, handleCellKeydown, setActiveCell, setCellRef } = useSpreadsheetNavigation(rowCount, addRow);

const columnLabels = {
    company: 'Company',
    date: 'Date',
    employee: 'Employee',
    project: 'Project',
    task: 'Task',
    hours: 'Hours',
};

const errorFieldByColumn = {
    company: 'company_id',
    date: 'entry_date',
    employee: 'employee_id',
    project: 'project_id',
    task: 'task_id',
    hours: 'hours',
};

function makeRow(overrides = {}) {
    return {
        company: props.selectedCompanyId ?? null,
        date: '',
        employee: null,
        project: null,
        task: null,
        hours: '',
        ...overrides,
    };
}

function addRow() {
    rows.value.push(makeRow());
}

function registerCellRef(row, column, element) {
    setCellRef(row, column, element?.$el ?? element);
}

function duplicateActiveRow() {
    const source = rows.value[activeCell.row] ?? rows.value[0];
    rows.value.splice(activeCell.row + 1, 0, { ...source });
    setActiveCell(activeCell.row + 1, 0, { focus: true });
}

function clearRows() {
    rows.value = [makeRow()];
    errors.value = {};
    submitError.value = null;
    setActiveCell(0, 0, { focus: true });
}

function companyOptions() {
    return props.companies;
}

function employeeOptions(row) {
    return companyOptionBucket(row.company).employees;
}

function projectOptions(row) {
    if (!row.company) {
        return [];
    }

    if (!row.employee) {
        return companyOptionBucket(row.company).projects;
    }

    return projectsByCompanyEmployee[companyEmployeeKey(row.company, row.employee)] ?? [];
}

function taskOptions(row) {
    return companyOptionBucket(row.company).tasks;
}

function companyOptionBucket(companyId) {
    if (!companyId) {
        return { employees: [], projects: [], tasks: [] };
    }

    return optionsByCompany[companyId] ?? { employees: [], projects: [], tasks: [] };
}

function companyEmployeeKey(companyId, employeeId) {
    return `${companyId}:${employeeId}`;
}

function handleDateKeydown(event, row, column) {
    if (event.key === 'F2' || (event.altKey && event.key === 'ArrowDown')) {
        event.preventDefault();
        setActiveCell(row, column);
        event.currentTarget.showPicker?.();
        return;
    }

    if (event.key === ' ') {
        event.preventDefault();
        return;
    }

    handleCellKeydown(event, row, column);
}

function selectCompany(row, option) {
    row.company = option.id;
    row.employee = null;
    row.project = null;
    row.task = null;
    ensureCompanyOptions(row.company);
}

function selectEmployee(row, option) {
    row.employee = option.id;
    row.project = null;
    ensureEmployeeProjects(row.company, row.employee);
}

function selectProject(row, option) {
    row.project = option.id;
}

function selectTask(row, option) {
    row.task = option.id;
}

async function ensureCompanyOptions(companyId) {
    if (!companyId || optionsByCompany[companyId] || loadingOptions[companyId]) {
        return;
    }

    loadingOptions[companyId] = true;

    try {
        const [employees, projects, tasks] = await Promise.all([
            getCompanyEmployees(companyId),
            getCompanyProjects(companyId),
            getCompanyTasks(companyId),
        ]);

        optionsByCompany[companyId] = { employees, projects, tasks };
    } catch (exception) {
        submitError.value = exception.message;
    } finally {
        loadingOptions[companyId] = false;
    }
}

async function ensureEmployeeProjects(companyId, employeeId) {
    if (!companyId || !employeeId) {
        return;
    }

    const key = companyEmployeeKey(companyId, employeeId);

    if (projectsByCompanyEmployee[key]) {
        return;
    }

    try {
        projectsByCompanyEmployee[key] = await getCompanyProjects(companyId, employeeId);
    } catch (exception) {
        submitError.value = exception.message;
    }
}

function fieldError(rowIndex, column) {
    return errors.value[`entries.${rowIndex}.${errorFieldByColumn[column]}`]?.[0] ?? null;
}

function rowHasErrors(rowIndex) {
    return Object.keys(errors.value).some((key) => key.startsWith(`entries.${rowIndex}.`));
}

function rowIsComplete(row) {
    return Boolean(row.company && row.date && row.employee && row.project && row.task && row.hours);
}

function rowHasInput(row) {
    return Boolean(row.date || row.employee || row.project || row.task || row.hours || (!props.selectedCompanyId && row.company));
}

function rowStatus(row, rowIndex) {
    if (rowHasErrors(rowIndex)) {
        return 'Needs fix';
    }

    return rowIsComplete(row) ? 'Ready' : 'Draft';
}

function rowStatusVariant(row, rowIndex) {
    return rowHasErrors(rowIndex) ? 'destructive' : 'secondary';
}

async function submitBatch() {
    const submittedRows = rows.value
        .map((row, index) => ({ index, row }))
        .filter(({ row }) => rowHasInput(row));

    errors.value = {};
    submitError.value = null;

    if (submittedRows.length === 0) {
        submitError.value = 'Add at least one time entry before submitting.';
        return;
    }

    submitState.value = 'submitting';

    try {
        await createTimeEntries(submittedRows.map(({ row }) => ({
            company_id: row.company,
            employee_id: row.employee,
            project_id: row.project,
            task_id: row.task,
            entry_date: row.date,
            hours: row.hours,
        })));

        rows.value = [makeRow()];
        submitState.value = 'submitted';
    } catch (exception) {
        errors.value = remapValidationErrors(exception.errors ?? {}, submittedRows);
        submitError.value = exception.message;
        submitState.value = 'idle';
    }
}

function remapValidationErrors(validationErrors, submittedRows) {
    return Object.fromEntries(Object.entries(validationErrors).map(([key, messages]) => {
        const match = key.match(/^entries\.([0-9]+)\.(.+)$/);

        if (!match) {
            return [key, messages];
        }

        const originalIndex = submittedRows[match[1]]?.index ?? match[1];

        return [`entries.${originalIndex}.${match[2]}`, messages];
    }));
}

function cellPlaceholder(column) {
    return {
        company: 'Select company',
        date: 'Set date',
        employee: 'Select employee',
        project: 'Select project',
        task: 'Select task',
        hours: '0.00',
    }[column];
}

watch(
    () => props.selectedCompanyId,
    (companyId) => {
        rows.value = rows.value.map((row) => ({
            ...row,
            company: companyId ?? row.company,
            employee: companyId && row.company !== companyId ? null : row.employee,
            project: companyId && row.company !== companyId ? null : row.project,
            task: companyId && row.company !== companyId ? null : row.task,
        }));

        errors.value = {};
        submitError.value = null;
        ensureCompanyOptions(companyId);
    },
    { immediate: true },
);
</script>

<template>
    <section class="overflow-hidden rounded-xl border border-border bg-card shadow-[inset_0_1px_0_rgb(255_255_255/0.04)]">
        <SpreadsheetToolbar
            :row-count="rows.length"
            :is-submitting="submitState === 'submitting'"
            @add-row="addRow"
            @duplicate-row="duplicateActiveRow"
            @clear-rows="clearRows"
            @submit="submitBatch"
        />

        <div v-if="submitError" class="border-b border-border px-3 py-2 text-sm text-destructive">
            {{ submitError }}
        </div>
        <div v-else-if="submitState === 'submitted'" class="border-b border-border px-3 py-2 text-sm text-[#79d98d]">
            Time entries saved.
        </div>

        <Table class="[&_table]:table-fixed">
            <TableHeader>
                <TableRow class="bg-background/70 hover:bg-background/70">
                    <TableHead class="w-12 text-center">#</TableHead>
                    <TableHead
                        v-for="column in spreadsheetColumns"
                        :key="column"
                        :class="column === 'hours' ? 'w-28 text-right' : 'w-44'"
                    >
                        {{ columnLabels[column] }}
                    </TableHead>
                    <TableHead class="w-28">Status</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="(row, rowIndex) in rows" :key="rowIndex" class="hover:bg-transparent">
                    <TableCell class="text-center font-mono text-xs text-muted-foreground">
                        {{ rowIndex + 1 }}
                    </TableCell>

                    <TableCell
                        v-for="(column, columnIndex) in spreadsheetColumns"
                        :key="column"
                        class="p-1"
                    >
                        <Input
                            v-if="column === 'date'"
                            :ref="(element) => registerCellRef(rowIndex, columnIndex, element)"
                            v-model="row.date"
                            type="date"
                            data-editor="trigger"
                            class="h-9 bg-transparent"
                            :data-active="activeCell.row === rowIndex && activeCell.column === columnIndex"
                            :aria-invalid="Boolean(fieldError(rowIndex, column))"
                            @focus="setActiveCell(rowIndex, columnIndex)"
                            @keydown="handleDateKeydown($event, rowIndex, columnIndex)"
                        />
                        <Input
                            v-else-if="column === 'hours'"
                            :ref="(element) => registerCellRef(rowIndex, columnIndex, element)"
                            v-model="row.hours"
                            inputmode="decimal"
                            placeholder="0.00"
                            class="h-9 bg-transparent text-right tabular-nums"
                            :data-active="activeCell.row === rowIndex && activeCell.column === columnIndex"
                            :aria-invalid="Boolean(fieldError(rowIndex, column))"
                            @focus="setActiveCell(rowIndex, columnIndex)"
                            @keydown="handleCellKeydown($event, rowIndex, columnIndex)"
                        />
                        <SpreadsheetSelectCell
                            v-else
                            :ref="(element) => registerCellRef(rowIndex, columnIndex, element)"
                            :active="activeCell.row === rowIndex && activeCell.column === columnIndex"
                            :disabled="(column !== 'company' && !row.company) || (column === 'project' && !row.employee)"
                            :error="fieldError(rowIndex, column)"
                            :is-loading="Boolean(loadingOptions[row.company])"
                            :model-value="row[column]"
                            :options="{
                                company: companyOptions(),
                                employee: employeeOptions(row),
                                project: projectOptions(row),
                                task: taskOptions(row),
                            }[column]"
                            :placeholder="cellPlaceholder(column)"
                            :search-placeholder="`Filter ${columnLabels[column].toLowerCase()}...`"
                            @focus="setActiveCell(rowIndex, columnIndex)"
                            @keydown="handleCellKeydown($event, rowIndex, columnIndex)"
                            @select="{
                                company: selectCompany,
                                employee: selectEmployee,
                                project: selectProject,
                                task: selectTask,
                            }[column](row, $event)"
                        />
                        <p v-if="fieldError(rowIndex, column)" class="mt-1 px-1 text-xs text-destructive">
                            {{ fieldError(rowIndex, column) }}
                        </p>
                    </TableCell>

                    <TableCell>
                        <Badge :variant="rowStatusVariant(row, rowIndex)">{{ rowStatus(row, rowIndex) }}</Badge>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </section>
</template>
