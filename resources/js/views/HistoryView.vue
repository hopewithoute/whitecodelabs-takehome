<script setup>
import { ArrowDownAZ, ArrowUpAZ, Building2, ChevronLeft, ChevronRight, ClipboardList, Folder, Pencil, RefreshCw, Search, Users } from 'lucide-vue-next';
import { nextTick, ref, toRef, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import EditEntryDialog from '@/components/history/EditEntryDialog.vue';
import { useEntryEditor } from '@/composables/useEntryEditor';
import { useHistoryEntries } from '@/composables/useHistoryEntries';

const props = defineProps({
    historyRefreshKey: { type: Number, default: 0 },
    historySearchFocusKey: { type: Number, default: 0 },
    selectedCompanyId: { type: [String, null], default: null },
});

const selectedCompanyIdRef = toRef(props, 'selectedCompanyId');
const historyRefreshKeyRef = toRef(props, 'historyRefreshKey');

const {
    error,
    isLoading,
    links,
    meta,
    search,
    sortKey,
    sortDirection,
    sortedEntries,
    scopeLabel,
    totalEntries,
    totalHoursDisplay,
    summarySections,
    pageLabel,
    sortOptions,
    loadEntries,
    setSort,
    nextPage,
    previousPage,
    relatedName,
} = useHistoryEntries(selectedCompanyIdRef, historyRefreshKeyRef);

const {
    isOpen: editOpen,
    form: editForm,
    options: editOptions,
    errors: editErrors,
    isLoadingOptions: isLoadingEditOptions,
    isSaving: isSavingEdit,
    fieldError,
    open: openEdit,
    close: closeEdit,
    changeCompany: changeEditCompany,
    changeEmployee: changeEditEmployee,
    save: saveEdit,
    updateField,
} = useEntryEditor(loadEntries);

const searchInputRef = ref(null);
const expandedSections = ref({});

const SUMMARY_PREVIEW_COUNT = 5;

const sectionIcons = {
    by_company: Building2,
    by_employee: Users,
    by_project: Folder,
    by_task: ClipboardList,
};

function isExpanded(key) {
    return expandedSections.value[key] === true;
}

function toggleSection(key) {
    expandedSections.value = { ...expandedSections.value, [key]: !expandedSections.value[key] };
}

function visibleRows(section) {
    if (isExpanded(section.key)) {
        return section.rows;
    }
    return section.rows.slice(0, SUMMARY_PREVIEW_COUNT);
}

function hiddenCount(section) {
    return Math.max(0, section.rows.length - SUMMARY_PREVIEW_COUNT);
}

watch(() => props.historySearchFocusKey, async () => {
    await nextTick();
    searchInputRef.value?.$el?.focus();
});
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
                <Button
                    variant="ghost"
                    size="icon-sm"
                    :disabled="isLoading"
                    @click="loadEntries"
                >
                    <RefreshCw :class="isLoading ? 'animate-spin' : ''" />
                </Button>
            </div>
        </div>

        <div class="flex flex-col gap-2 rounded-xl border border-border bg-card p-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative sm:max-w-sm sm:flex-1">
                <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    ref="searchInputRef"
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
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-xs font-medium uppercase text-muted-foreground">
                            <component :is="sectionIcons[section.key]" class="size-3.5" />
                            {{ section.label }}
                        </span>
                        <Badge v-if="section.rows.length > SUMMARY_PREVIEW_COUNT" variant="outline" class="text-[10px]">
                            {{ section.rows.length }} total
                        </Badge>
                    </div>
                    <div class="space-y-1">
                        <div
                            v-for="row in visibleRows(section)"
                            :key="row.id ?? row.name"
                            class="flex items-center justify-between gap-3 text-sm"
                        >
                            <span class="truncate">{{ row.name ?? 'Unknown' }}</span>
                            <span class="tabular-nums text-muted-foreground">{{ row.total_hours }}</span>
                        </div>
                        <p v-if="section.rows.length === 0" class="text-sm text-muted-foreground">No data</p>
                        <Button
                            v-if="hiddenCount(section) > 0"
                            variant="ghost"
                            size="sm"
                            class="mt-1 w-full text-xs text-muted-foreground"
                            @click="toggleSection(section.key)"
                        >
                            {{ isExpanded(section.key) ? 'Show less' : `Show ${hiddenCount(section)} more` }}
                        </Button>
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
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                :aria-label="`Edit ${relatedName(entry, 'employee')} entry`"
                                @click="openEdit(entry)"
                            >
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
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="isLoading || !links.prev"
                    @click="previousPage"
                >
                    <ChevronLeft />
                    Previous
                </Button>
                <span class="min-w-24 text-center text-sm text-muted-foreground">{{ pageLabel }}</span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="isLoading || !links.next"
                    @click="nextPage"
                >
                    Next
                    <ChevronRight />
                </Button>
            </div>
        </div>

        <EditEntryDialog
            :form="editForm"
            :options="editOptions"
            :errors="editErrors"
            :is-open="editOpen"
            :is-loading-options="isLoadingEditOptions"
            :is-saving="isSavingEdit"
            :field-error="fieldError"
            :change-company="changeEditCompany"
            :change-employee="changeEditEmployee"
            :save="saveEdit"
            :close="closeEdit"
            :update-field="updateField"
        />
    </div>
</template>
