<script setup>
import TimeEntrySpreadsheet from '@/components/spreadsheet/TimeEntrySpreadsheet.vue';

defineProps({
    companies: { type: Array, default: () => [] },
    selectedCompanyId: { type: [String, null], default: null },
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold tracking-[-0.02em]">New Entries</h2>
                <p class="text-sm text-muted-foreground">Batch time entry workspace with spreadsheet-style controls.</p>
            </div>
        </div>

        <TimeEntrySpreadsheet :companies="companies" :selected-company-id="selectedCompanyId" />
    </div>
</template>
