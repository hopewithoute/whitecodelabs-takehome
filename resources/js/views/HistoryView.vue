<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { ArrowDownAZ, ArrowUpAZ, ChevronLeft, ChevronRight, Pencil, RefreshCw, Search } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import {
    getCompanies,
    getCompanyEmployees,
    getCompanyProjects,
    getCompanyTasks,
    getTimeEntries,
    updateTimeEntry,
} from '@/api/options';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import SpreadsheetDateCell from '@/components/spreadsheet/SpreadsheetDateCell.vue';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const props = defineProps({
    historyRefreshKey: { type: Number, default: 0 },
    selectedCompanyId: { type: [String, null], default: null },
});

const entries = ref([]);
const error = ref(null);
const isLoading = ref(false);
const loadToken = ref(0);
const links = ref({});
const meta = ref({});
const page = ref(1);
const perPage = 10;
const search = ref('');
const sortKey = ref('entry_date');
const sortDirection = ref('desc');
const editOpen = ref(false);
const editEntry = ref(null);
const editErrors = ref({});
const editForm = ref(blankEditForm());
const editOptions = ref({
    companies: [],
    employees: [],
    projects: [],
    tasks: [],
});
const isLoadingEditOptions = ref(false);
const isSavingEdit = ref(false);
let searchTimeout = null;

const sortOptions = [
    { key: 'entry_date', label: 'Date' },
    { key: 'employee', label: 'Employee' },
    { key: 'project', label: 'Project' },
    { key: 'task', label: 'Task' },
    { key: 'hours', label: 'Hours' },
];

const sortedEntries = computed(() => [...entries.value].sort(compareEntries));
const pageHours = computed(() => sortedEntries.value.reduce((total, entry) => total + Number.parseFloat(entry.hours ?? 0), 0));
const summary = computed(() => meta.value.summary ?? {});
const scopeLabel = computed(() => props.selectedCompanyId ? 'Company scope' : 'All companies');
const totalEntries = computed(() => meta.value.total ?? sortedEntries.value.length);
const totalHoursDisplay = computed(() => summary.value.total_hours ?? pageHours.value.toFixed(2));
const summarySections = computed(() => [
    { key: 'by_company', label: 'Company', rows: summary.value.by_company ?? [] },
    { key: 'by_employee', label: 'Employee', rows: summary.value.by_employee ?? [] },
    { key: 'by_project', label: 'Project', rows: summary.value.by_project ?? [] },
    { key: 'by_task', label: 'Task', rows: summary.value.by_task ?? [] },
]);
const pageLabel = computed(() => {
    const currentPage = meta.value.current_page ?? page.value;
    const lastPage = meta.value.last_page ?? 1;

    return `Page ${currentPage} of ${lastPage}`;
});

async function loadEntries() {
    cancelDebouncedLoad();

    const token = loadToken.value + 1;
    loadToken.value = token;
    const companyId = props.selectedCompanyId;
    const searchTerm = search.value.trim();

    isLoading.value = true;
    error.value = null;

    try {
        const result = await getTimeEntries({
            companyId,
            search: searchTerm,
            page: page.value,
            perPage,
        });

        if (token === loadToken.value) {
            entries.value = result.entries;
            links.value = result.links;
            meta.value = result.meta;
        }
    } catch (exception) {
        if (token === loadToken.value) {
            error.value = exception;
        }
    } finally {
        if (token === loadToken.value) {
            isLoading.value = false;
        }
    }
}

function relatedName(entry, relation) {
    return entry[relation]?.name ?? 'Unknown';
}

function sortValue(entry, key) {
    if (key === 'hours') {
        return Number.parseFloat(entry.hours ?? 0);
    }

    if (key === 'entry_date') {
        return entry.entry_date ?? '';
    }

    return relatedName(entry, key).toLowerCase();
}

function compareEntries(first, second) {
    const firstValue = sortValue(first, sortKey.value);
    const secondValue = sortValue(second, sortKey.value);
    const direction = sortDirection.value === 'asc' ? 1 : -1;

    if (firstValue > secondValue) {
        return direction;
    }

    if (firstValue < secondValue) {
        return -direction;
    }

    return 0;
}

function setSort(key) {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
        return;
    }

    sortKey.value = key;
    sortDirection.value = key === 'entry_date' || key === 'hours' ? 'desc' : 'asc';
}

function resetAndLoadEntries() {
    page.value = 1;
    loadEntries();
}

function debounceLoadEntries() {
    cancelDebouncedLoad();
    searchTimeout = window.setTimeout(resetAndLoadEntries, 250);
}

function cancelDebouncedLoad() {
    window.clearTimeout(searchTimeout);
    searchTimeout = null;
}

function goToPage(nextPage) {
    if (nextPage < 1 || nextPage === page.value || isLoading.value) {
        return;
    }

    page.value = nextPage;
    loadEntries();
}

function nextPage() {
    if (!links.value.next) {
        return;
    }

    goToPage(page.value + 1);
}

function previousPage() {
    if (!links.value.prev) {
        return;
    }

    goToPage(page.value - 1);
}

function blankEditForm() {
    return {
        company_id: '',
        employee_id: '',
        project_id: '',
        task_id: '',
        entry_date: '',
        hours: '',
    };
}

async function openEdit(entry) {
    editEntry.value = entry;
    editErrors.value = {};
    editForm.value = {
        company_id: entry.company_id,
        employee_id: entry.employee_id,
        project_id: entry.project_id,
        task_id: entry.task_id,
        entry_date: entry.entry_date,
        hours: entry.hours,
    };
    editOpen.value = true;

    await loadEditOptions(entry.company_id, entry.employee_id);
}

async function loadEditOptions(companyId, employeeId = null) {
    if (!companyId) {
        editOptions.value = {
            companies: await getCompanies(),
            employees: [],
            projects: [],
            tasks: [],
        };
        return;
    }

    isLoadingEditOptions.value = true;

    try {
        const [companies, employees, projects, tasks] = await Promise.all([
            getCompanies(),
            getCompanyEmployees(companyId),
            getCompanyProjects(companyId, employeeId),
            getCompanyTasks(companyId),
        ]);

        editOptions.value = { companies, employees, projects, tasks };
    } finally {
        isLoadingEditOptions.value = false;
    }
}

async function changeEditCompany() {
    editForm.value.employee_id = '';
    editForm.value.project_id = '';
    editForm.value.task_id = '';
    await loadEditOptions(editForm.value.company_id);
}

async function changeEditEmployee() {
    editForm.value.project_id = '';
    editOptions.value.projects = editForm.value.company_id
        ? await getCompanyProjects(editForm.value.company_id, editForm.value.employee_id)
        : [];
}

function fieldError(field) {
    return editErrors.value[field]?.[0] ?? editErrors.value[`entries.0.${field}`]?.[0] ?? null;
}

async function saveEdit() {
    if (!editEntry.value || isSavingEdit.value) {
        return;
    }

    isSavingEdit.value = true;
    editErrors.value = {};

    try {
        await updateTimeEntry(editEntry.value.id, editForm.value);
        editOpen.value = false;
        toast.success('Time entry updated.', {
            description: 'History and summary totals were refreshed.',
        });
        await loadEntries();
    } catch (exception) {
        editErrors.value = exception.errors ?? {};
        toast.error('Time entry was not updated.', {
            description: exception.message,
        });
    } finally {
        isSavingEdit.value = false;
    }
}

onMounted(loadEntries);
onBeforeUnmount(cancelDebouncedLoad);
watch(() => props.selectedCompanyId, resetAndLoadEntries);
watch(() => props.historyRefreshKey, resetAndLoadEntries);
watch(search, debounceLoadEntries);
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold tracking-[-0.02em]">History</h2>
                <p class="text-sm text-muted-foreground">Read-only time entries from the API.</p>
            </div>
            <div class="flex items-center gap-2">
                <Badge variant="secondary">{{ scopeLabel }}</Badge>
                <Badge variant="outline">{{ totalEntries }} entries</Badge>
                <Badge variant="outline">{{ totalHoursDisplay }} hours</Badge>
                <Button variant="ghost" size="icon-sm" :disabled="isLoading" @click="loadEntries">
                    <RefreshCw :class="isLoading ? 'animate-spin' : ''" />
                </Button>
            </div>
        </div>

        <div class="flex flex-col gap-2 rounded-xl border border-border bg-card p-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative sm:max-w-sm sm:flex-1">
                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Search company, employee, project, or task"
                />
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
                <Button
                    v-for="option in sortOptions"
                    :key="option.key"
                    :variant="sortKey === option.key ? 'secondary' : 'ghost'"
                    size="sm"
                    @click="setSort(option.key)"
                >
                    {{ option.label }}
                    <component
                        :is="sortDirection === 'asc' ? ArrowUpAZ : ArrowDownAZ"
                        v-if="sortKey === option.key"
                    />
                </Button>
            </div>
        </div>

        <section class="rounded-xl border border-border bg-card p-3">
            <div class="grid gap-3 md:grid-cols-4">
                <div
                    v-for="section in summarySections"
                    :key="section.key"
                    class="space-y-2"
                >
                    <div class="text-xs font-medium uppercase text-muted-foreground">{{ section.label }}</div>
                    <div class="space-y-1">
                        <div
                            v-for="row in section.rows.slice(0, 3)"
                            :key="row.id ?? row.name"
                            class="flex items-center justify-between gap-3 text-sm"
                        >
                            <span class="truncate">{{ row.name ?? 'Unknown' }}</span>
                            <span class="tabular-nums text-muted-foreground">{{ row.total_hours }}</span>
                        </div>
                        <p v-if="section.rows.length === 0" class="text-sm text-muted-foreground">No data</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-border bg-card shadow-[inset_0_1px_0_rgb(255_255_255/0.04)]">
            <Table>
                <TableHeader>
                    <TableRow class="bg-background/70 hover:bg-background/70">
                        <TableHead>Company</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Employee</TableHead>
                        <TableHead>Project</TableHead>
                        <TableHead>Task</TableHead>
                        <TableHead class="text-right">Hours</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="isLoading">
                        <TableCell colspan="7" class="py-8 text-center text-sm text-muted-foreground">
                            Loading history
                        </TableCell>
                    </TableRow>
                    <TableRow v-else-if="error">
                        <TableCell colspan="7" class="py-8 text-center text-sm text-destructive">
                            {{ error.message }}
                        </TableCell>
                    </TableRow>
                    <TableRow v-else-if="sortedEntries.length === 0">
                        <TableCell colspan="7" class="py-8 text-center text-sm text-muted-foreground">
                            No time entries found for this scope.
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="entry in sortedEntries" v-else :key="entry.id">
                        <TableCell>{{ relatedName(entry, 'company') }}</TableCell>
                        <TableCell class="whitespace-nowrap">{{ entry.entry_date_display ?? entry.entry_date }}</TableCell>
                        <TableCell>{{ relatedName(entry, 'employee') }}</TableCell>
                        <TableCell>{{ relatedName(entry, 'project') }}</TableCell>
                        <TableCell>{{ relatedName(entry, 'task') }}</TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ entry.hours_display ?? entry.hours }}
                        </TableCell>
                        <TableCell class="text-right">
                            <Button variant="ghost" size="icon-sm" :aria-label="`Edit ${relatedName(entry, 'employee')} entry`" @click="openEdit(entry)">
                                <Pencil />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </section>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-muted-foreground">
                Showing {{ meta.from ?? 0 }}-{{ meta.to ?? 0 }} of {{ totalEntries }} entries
            </p>
            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" :disabled="isLoading || !links.prev" @click="previousPage">
                    <ChevronLeft />
                    Previous
                </Button>
                <span class="min-w-24 text-center text-sm text-muted-foreground">{{ pageLabel }}</span>
                <Button variant="outline" size="sm" :disabled="isLoading || !links.next" @click="nextPage">
                    Next
                    <ChevronRight />
                </Button>
            </div>
        </div>

        <Dialog v-model:open="editOpen">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Edit time entry</DialogTitle>
                </DialogHeader>

                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="saveEdit">
                    <label class="space-y-1.5 text-sm">
                        <span class="text-muted-foreground">Company</span>
                        <select
                            v-model="editForm.company_id"
                            class="h-9 w-full rounded-md border border-input bg-card px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                            :aria-invalid="Boolean(fieldError('company_id'))"
                            :disabled="isLoadingEditOptions || isSavingEdit"
                            @change="changeEditCompany"
                        >
                            <option value="">Choose a company</option>
                            <option v-for="company in editOptions.companies" :key="company.id" :value="company.id">
                                {{ company.name }}
                            </option>
                        </select>
                        <span v-if="fieldError('company_id')" class="text-xs text-destructive">{{ fieldError('company_id') }}</span>
                    </label>

                    <label class="space-y-1.5 text-sm">
                        <span class="text-muted-foreground">Date</span>
                        <SpreadsheetDateCell
                            :model-value="editForm.entry_date"
                            placeholder="Set date"
                            :error="fieldError('entry_date')"
                            trigger-variant="outline"
                            trigger-class="bg-card text-foreground"
                            @select="editForm.entry_date = $event"
                        />
                        <span v-if="fieldError('entry_date')" class="text-xs text-destructive">{{ fieldError('entry_date') }}</span>
                    </label>

                    <label class="space-y-1.5 text-sm">
                        <span class="text-muted-foreground">Employee</span>
                        <select
                            v-model="editForm.employee_id"
                            class="h-9 w-full rounded-md border border-input bg-card px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                            :aria-invalid="Boolean(fieldError('employee_id'))"
                            :disabled="!editForm.company_id || isLoadingEditOptions || isSavingEdit"
                            @change="changeEditEmployee"
                        >
                            <option value="">Choose an employee</option>
                            <option v-for="employee in editOptions.employees" :key="employee.id" :value="employee.id">
                                {{ employee.name }}
                            </option>
                        </select>
                        <span v-if="fieldError('employee_id')" class="text-xs text-destructive">{{ fieldError('employee_id') }}</span>
                    </label>

                    <label class="space-y-1.5 text-sm">
                        <span class="text-muted-foreground">Project</span>
                        <select
                            v-model="editForm.project_id"
                            class="h-9 w-full rounded-md border border-input bg-card px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                            :aria-invalid="Boolean(fieldError('project_id'))"
                            :disabled="!editForm.company_id || isLoadingEditOptions || isSavingEdit"
                        >
                            <option value="">Choose a project</option>
                            <option v-for="project in editOptions.projects" :key="project.id" :value="project.id">
                                {{ project.name }}
                            </option>
                        </select>
                        <span v-if="fieldError('project_id')" class="text-xs text-destructive">{{ fieldError('project_id') }}</span>
                    </label>

                    <label class="space-y-1.5 text-sm">
                        <span class="text-muted-foreground">Task</span>
                        <select
                            v-model="editForm.task_id"
                            class="h-9 w-full rounded-md border border-input bg-card px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                            :aria-invalid="Boolean(fieldError('task_id'))"
                            :disabled="!editForm.company_id || isLoadingEditOptions || isSavingEdit"
                        >
                            <option value="">Choose a task</option>
                            <option v-for="task in editOptions.tasks" :key="task.id" :value="task.id">
                                {{ task.name }}
                            </option>
                        </select>
                        <span v-if="fieldError('task_id')" class="text-xs text-destructive">{{ fieldError('task_id') }}</span>
                    </label>

                    <label class="space-y-1.5 text-sm">
                        <span class="text-muted-foreground">Hours</span>
                        <Input
                            v-model="editForm.hours"
                            inputmode="decimal"
                            placeholder="0.00"
                            :aria-invalid="Boolean(fieldError('hours'))"
                            :disabled="isSavingEdit"
                        />
                        <span v-if="fieldError('hours')" class="text-xs text-destructive">{{ fieldError('hours') }}</span>
                    </label>
                </form>

                <DialogFooter>
                    <Button variant="ghost" :disabled="isSavingEdit" @click="editOpen = false">Cancel</Button>
                    <Button :disabled="isSavingEdit || isLoadingEditOptions" @click="saveEdit">
                        {{ isSavingEdit ? 'Saving' : 'Save changes' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
