<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
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
import { createTimeEntries } from '@/api/options';
import { spreadsheetColumns, useSpreadsheetNavigation } from '@/composables/useSpreadsheetNavigation';
import { useSpreadsheetOptions } from '@/composables/useSpreadsheetOptions';
import AiEntryAssistant from './AiEntryAssistant.vue';
import SpreadsheetDateCell from './SpreadsheetDateCell.vue';
import SpreadsheetSelectCell from './SpreadsheetSelectCell.vue';
import SpreadsheetToolbar from './SpreadsheetToolbar.vue';

const props = defineProps({
    companies: { type: Array, default: () => [] },
    focusKey: { type: Number, default: 0 },
    selectedCompanyId: { type: [String, null], default: null },
});

const emit = defineEmits(['submitted']);

const companiesRef = computed(() => props.companies);

const nextDraftRowId = ref(1);
const rows = ref([makeRow(), makeRow()]);
const errors = ref({});
const submitError = ref(null);
const submitState = ref('idle');
const editorCloseSignal = ref(0);

const rowCount = computed(() => rows.value.length);
const { activeCell, handleCellKeydown, setActiveCell, setCellRef } = useSpreadsheetNavigation(rowCount, addRow);
const { loadingOptions, getOptions, handleSelect, ensureCompanyOptions, ensureEmployeeProjects } = useSpreadsheetOptions(
    companiesRef,
    (message) => { submitError.value = message; },
);

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
        _key: nextDraftRowId.value++,
        company: props.selectedCompanyId ?? null,
        date: '',
        employee: null,
        project: null,
        task: null,
        hours: '',
        _warnings: [],
        _fieldWarnings: {},
        ...overrides,
    };
}

function addRow(options = {}) {
    const nextIndex = rows.value.length;

    rows.value.push(makeRow());

    if (options.focus) {
        nextTick(() => setActiveCell(nextIndex, 0, { focus: true }));
    }
}

function registerCellRef(row, column, element) {
    setCellRef(row, column, element);
}

function duplicateActiveRow() {
    closeCellEditors();

    const source = rows.value[activeCell.row] ?? rows.value[0];
    const duplicateRow = activeCell.row + 1;

    rows.value.splice(activeCell.row + 1, 0, { ...source, _key: nextDraftRowId.value++ });
    setActiveCell(duplicateRow, spreadsheetColumns.indexOf('hours'), { focus: true });
    nextTick(closeCellEditors);
}

function deleteActiveRow() {
    closeCellEditors();

    if (rows.value.length <= 1) {
        rows.value = [makeRow()];
        errors.value = {};
        submitError.value = null;
        setActiveCell(0, activeCell.column, { focus: true });
        return;
    }

    const deletedRow = activeCell.row;

    rows.value.splice(deletedRow, 1);
    errors.value = {};
    submitError.value = null;
    setActiveCell(Math.min(deletedRow, rows.value.length - 1), activeCell.column, { focus: true });
    nextTick(closeCellEditors);
}

function closeCellEditors() {
    editorCloseSignal.value += 1;
}

function clearRows() {
    rows.value = [makeRow()];
    errors.value = {};
    submitError.value = null;
    setActiveCell(0, 0, { focus: true });
}

function navigateFromCell(row, column, direction = 1) {
    const nextColumn = column + direction;

    if (nextColumn >= spreadsheetColumns.length) {
        if (row === rowCount.value - 1) {
            addRow();
        }

        setActiveCell(row + 1, 0, { focus: true });
        return;
    }

    if (nextColumn < 0) {
        setActiveCell(row - 1, spreadsheetColumns.length - 1, { focus: true });
        return;
    }

    setActiveCell(row, nextColumn, { focus: true });
}

function handleSpreadsheetShortcut(event) {
    if (event.target?.closest?.('[data-ai-entry-assistant]')) {
        return;
    }

    if (event.repeat || (!event.ctrlKey && !event.metaKey)) {
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        event.stopPropagation();

        if (event.shiftKey) {
            closeCellEditors();
            addRow({ focus: true });
            return;
        }

        submitBatch();
        return;
    }

    if (event.key.toLowerCase() === 'd' && !event.shiftKey && !event.altKey) {
        event.preventDefault();
        event.stopPropagation();
        duplicateActiveRow();
        return;
    }

    if (event.key === 'Backspace' && event.shiftKey && !event.altKey) {
        event.preventDefault();
        event.stopPropagation();
        deleteActiveRow();
    }
}

async function appendAiDraftRows(draftRows) {
    if (draftRows.length === 0) {
        submitError.value = 'No draft rows were found in the AI response.';
        return;
    }

    const nextRows = draftRows.map((entry) => makeRow({
        company: entry.company_id ?? props.selectedCompanyId ?? null,
        date: entry.entry_date ?? '',
        employee: entry.employee_id ?? null,
        project: entry.project_id ?? null,
        task: entry.task_id ?? null,
        hours: entry.hours ?? '',
        _warnings: entry.warnings ?? [],
        _fieldWarnings: entry.field_warnings ?? {},
    }));

    const existingRows = rows.value.filter((row) => rowHasInput(row));
    rows.value = [...existingRows, ...nextRows];
    errors.value = {};
    submitError.value = null;

    await Promise.all(nextRows.map(async (row) => {
        await ensureCompanyOptions(row.company);

        if (row.employee) {
            await ensureEmployeeProjects(row.company, row.employee);
        }
    }));

    setActiveCell(existingRows.length, 0, { focus: true });

    const rowsWithWarnings = nextRows.filter((row) => row._warnings.length > 0).length;
    toast.success('AI draft rows added.', {
        description: rowsWithWarnings
            ? `${rowsWithWarnings} ${rowsWithWarnings === 1 ? 'row needs' : 'rows need'} review before saving.`
            : `${nextRows.length} ${nextRows.length === 1 ? 'row is' : 'rows are'} ready to review.`,
    });
}

function selectDate(row, value) {
    row.date = value;
    clearFieldWarning(row, 'entry_date');
}

function handleCellSelect(column, row, option) {
    handleSelect(column, row, option);
    clearFieldWarning(row, errorFieldByColumn[column]);
}

function clearFieldWarning(row, field) {
    if (!field || !row._fieldWarnings?.[field]) {
        return;
    }

    const { [field]: _removed, ...remainingWarnings } = row._fieldWarnings;
    row._fieldWarnings = remainingWarnings;
}

function isCellDisabled(column, row) {
    if (column === 'company') {
        return Boolean(props.selectedCompanyId);
    }

    if (column === 'employee' || column === 'project' || column === 'task') {
        return !row.company;
    }

    return false;
}

function fieldError(rowIndex, column) {
    return errors.value[`entries.${rowIndex}.${errorFieldByColumn[column]}`]?.[0] ?? null;
}

function fieldWarning(row, column) {
    return row._fieldWarnings?.[errorFieldByColumn[column]]?.[0] ?? null;
}

function rowHasErrors(rowIndex) {
    return Object.keys(errors.value).some((key) => key.startsWith(`entries.${rowIndex}.`));
}

function rowHasWarnings(row) {
    return Object.values(row._fieldWarnings ?? {}).some((messages) => messages.length > 0);
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

    if (rowHasWarnings(row)) {
        return 'Review';
    }

    return rowIsComplete(row) ? 'Ready' : 'Draft';
}

function rowStatusVariant(row, rowIndex) {
    return rowHasErrors(rowIndex) ? 'destructive' : 'secondary';
}

async function submitBatch() {
    if (submitState.value === 'submitting') {
        return;
    }

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
        const createdEntries = await createTimeEntries(submittedRows.map(({ row }) => ({
            company_id: row.company,
            employee_id: row.employee,
            project_id: row.project,
            task_id: row.task,
            entry_date: row.date,
            hours: row.hours,
        })));

        rows.value = [makeRow()];
        submitState.value = 'submitted';
        toast.success('Time entries saved.', {
            description: `${createdEntries.length} ${createdEntries.length === 1 ? 'entry' : 'entries'} added to history.`,
        });
        emit('submitted', createdEntries);
    } catch (exception) {
        errors.value = remapValidationErrors(exception.errors ?? {}, submittedRows);
        submitError.value = exception.message;
        submitState.value = 'idle';
        toast.error('Time entries were not saved.', {
            description: exception.message,
        });
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

watch(
    () => props.focusKey,
    () => setActiveCell(0, 0, { focus: true }),
);
</script>

<template>
    <section
        class="overflow-hidden rounded-xl border border-border bg-card shadow-[inset_0_1px_0_rgb(255_255_255/0.04)]"
        @keydown.capture="handleSpreadsheetShortcut"
    >
        <SpreadsheetToolbar
            :row-count="rows.length"
            :is-submitting="submitState === 'submitting'"
            @add-row="addRow"
            @duplicate-row="duplicateActiveRow"
            @delete-row="deleteActiveRow"
            @clear-rows="clearRows"
            @submit="submitBatch"
        />

        <AiEntryAssistant
            :selected-company-id="selectedCompanyId"
            @drafted="appendAiDraftRows"
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
                <TableRow v-for="(row, rowIndex) in rows" :key="row._key" class="hover:bg-transparent">
                    <TableCell class="align-top pt-4 text-center font-mono text-xs text-muted-foreground">
                        {{ rowIndex + 1 }}
                    </TableCell>

                    <TableCell
                        v-for="(column, columnIndex) in spreadsheetColumns"
                        :key="column"
                        class="p-1 align-top"
                    >
                        <div class="grid min-h-14 grid-rows-[2.25rem_1rem] items-start gap-1">
                            <Input
                                v-if="column === 'hours'"
                                :ref="(element) => registerCellRef(rowIndex, columnIndex, element)"
                                v-model="row.hours"
                                inputmode="decimal"
                                placeholder="0.00"
                                class="h-9 bg-transparent text-right tabular-nums"
                                :data-active="activeCell.row === rowIndex && activeCell.column === columnIndex"
                                :aria-invalid="Boolean(fieldError(rowIndex, column))"
                                @input="clearFieldWarning(row, errorFieldByColumn[column])"
                                @focus="setActiveCell(rowIndex, columnIndex)"
                                @keydown="handleCellKeydown($event, rowIndex, columnIndex)"
                            />
                            <SpreadsheetDateCell
                                v-else-if="column === 'date'"
                                :ref="(element) => registerCellRef(rowIndex, columnIndex, element)"
                                :active="activeCell.row === rowIndex && activeCell.column === columnIndex"
                                :error="fieldError(rowIndex, column)"
                                :model-value="row.date"
                                :placeholder="cellPlaceholder(column)"
                                :close-signal="editorCloseSignal"
                                @commit="navigateFromCell(rowIndex, columnIndex)"
                                @focus="setActiveCell(rowIndex, columnIndex)"
                                @keydown="handleCellKeydown($event, rowIndex, columnIndex)"
                                @navigate="navigateFromCell(rowIndex, columnIndex, $event)"
                                @select="selectDate(row, $event)"
                            />
                            <SpreadsheetSelectCell
                                v-else
                                :ref="(element) => registerCellRef(rowIndex, columnIndex, element)"
                                :active="activeCell.row === rowIndex && activeCell.column === columnIndex"
                                :disabled="isCellDisabled(column, row)"
                                :error="fieldError(rowIndex, column)"
                                :is-loading="Boolean(loadingOptions[row.company])"
                                :model-value="row[column]"
                                :options="getOptions(column, row)"
                                :placeholder="cellPlaceholder(column)"
                                :search-placeholder="`Filter ${columnLabels[column].toLowerCase()}...`"
                                :close-signal="editorCloseSignal"
                                @commit="navigateFromCell(rowIndex, columnIndex)"
                                @focus="setActiveCell(rowIndex, columnIndex)"
                                @keydown="handleCellKeydown($event, rowIndex, columnIndex)"
                                @navigate="navigateFromCell(rowIndex, columnIndex, $event)"
                                @select="handleCellSelect(column, row, $event)"
                            />
                            <p class="min-h-4 truncate px-1 text-xs leading-4" :class="fieldError(rowIndex, column) ? 'text-destructive' : 'text-muted-foreground'">
                                {{ fieldError(rowIndex, column) ?? fieldWarning(row, column) ?? '' }}
                            </p>
                        </div>
                    </TableCell>

                    <TableCell class="align-top pt-3">
                        <Badge :variant="rowStatusVariant(row, rowIndex)">{{ rowStatus(row, rowIndex) }}</Badge>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </section>
</template>
