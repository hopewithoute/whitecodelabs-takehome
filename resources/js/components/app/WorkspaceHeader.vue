<script setup>
import { Badge } from '@/components/ui/badge';
import RouteTabs from './RouteTabs.vue';
import ScopeCompanySelect from './ScopeCompanySelect.vue';

defineProps({
    companies: { type: Array, required: true },
    isLoadingCompanies: { type: Boolean, default: false },
    selectedCompanyId: { type: [String, null], default: null },
});

const emit = defineEmits(['update:selectedCompanyId']);
</script>

<template>
    <header class="rounded-xl border border-border bg-card/95 p-4 shadow-[inset_0_1px_0_rgb(255_255_255/0.04)]">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="mb-2 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-primary shadow-[0_0_20px_rgb(94_106_210/0.75)]" />
                    <p class="text-xs font-medium tracking-[0.04em] text-muted-foreground uppercase">Time entry</p>
                    <Badge variant="secondary">API backed</Badge>
                </div>
                <h1 class="text-2xl leading-tight font-semibold tracking-[-0.03em] text-foreground">
                    Employee time entries
                </h1>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <ScopeCompanySelect
                    :companies="companies"
                    :is-loading="isLoadingCompanies"
                    :model-value="selectedCompanyId"
                    @update:model-value="emit('update:selectedCompanyId', $event)"
                />
                <RouteTabs />
            </div>
        </div>
    </header>
</template>
