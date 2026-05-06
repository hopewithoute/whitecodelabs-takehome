<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RefreshCw } from 'lucide-vue-next';
import { getTimeEntries } from '@/api/options';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const props = defineProps({
    selectedCompanyId: { type: [String, null], default: null },
});

const entries = ref([]);
const error = ref(null);
const isLoading = ref(false);
const loadToken = ref(0);

const totalHours = computed(() => entries.value.reduce((total, entry) => total + Number.parseFloat(entry.hours ?? 0), 0));
const scopeLabel = computed(() => props.selectedCompanyId ? 'Company scope' : 'All companies');

async function loadEntries() {
    const token = loadToken.value + 1;
    loadToken.value = token;
    const companyId = props.selectedCompanyId;

    isLoading.value = true;
    error.value = null;

    try {
        const nextEntries = await getTimeEntries(companyId);

        if (token === loadToken.value) {
            entries.value = nextEntries;
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

onMounted(loadEntries);
watch(() => props.selectedCompanyId, loadEntries);
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
                <Badge variant="outline">{{ entries.length }} entries</Badge>
                <Badge variant="outline">{{ totalHours.toFixed(2) }} hours</Badge>
                <Button variant="ghost" size="icon-sm" :disabled="isLoading" @click="loadEntries">
                    <RefreshCw :class="isLoading ? 'animate-spin' : ''" />
                </Button>
            </div>
        </div>

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
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="isLoading">
                        <TableCell colspan="6" class="py-8 text-center text-sm text-muted-foreground">
                            Loading history
                        </TableCell>
                    </TableRow>
                    <TableRow v-else-if="error">
                        <TableCell colspan="6" class="py-8 text-center text-sm text-destructive">
                            {{ error.message }}
                        </TableCell>
                    </TableRow>
                    <TableRow v-else-if="entries.length === 0">
                        <TableCell colspan="6" class="py-8 text-center text-sm text-muted-foreground">
                            No time entries found for this scope.
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="entry in entries" v-else :key="entry.id">
                        <TableCell>{{ relatedName(entry, 'company') }}</TableCell>
                        <TableCell class="whitespace-nowrap">{{ entry.entry_date_display ?? entry.entry_date }}</TableCell>
                        <TableCell>{{ relatedName(entry, 'employee') }}</TableCell>
                        <TableCell>{{ relatedName(entry, 'project') }}</TableCell>
                        <TableCell>{{ relatedName(entry, 'task') }}</TableCell>
                        <TableCell class="text-right tabular-nums">
                            {{ entry.hours_display ?? entry.hours }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </section>
    </div>
</template>
