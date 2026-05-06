<script setup>
import { Badge } from '@/components/ui/badge';
import KeyboardShortcutLegend from './KeyboardShortcutLegend.vue';
import RouteTabs from './RouteTabs.vue';
import ScopeCompanySelect from './ScopeCompanySelect.vue';

defineProps({
    companies: { type: Array, required: true },
    isLoadingCompanies: { type: Boolean, default: false },
    selectedCompanyId: { type: [String, null], default: null },
    shortcutLegendOpenKey: { type: Number, default: 0 },
});

const emit = defineEmits(['update:selectedCompanyId']);
</script>

<template>
    <header class="rounded-xl border border-border bg-card/95 p-4 shadow-[inset_0_1px_0_rgb(255_255_255/0.04)]">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex h-5 items-center gap-1.5 rounded-md border border-primary/30 bg-primary/10 px-2 text-[11px] font-medium tracking-[0.08em] text-[#c6cbff] uppercase">
                        <span class="size-1.5 rounded-full bg-primary shadow-[0_0_16px_rgb(94_106_210/0.75)]"></span>
                        Time entry
                    </span>
                    <Badge variant="secondary">API backed</Badge>
                </div>
                <div>
                    <h1 class="text-2xl leading-tight font-semibold text-foreground">
                        Employee time entries
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Take-home implementation by <span class="font-medium text-foreground">Anggi Wibiyanto</span>
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex items-center gap-1">
                    <KeyboardShortcutLegend :open-key="shortcutLegendOpenKey" />
                    <ScopeCompanySelect
                        :companies="companies"
                        :is-loading="isLoadingCompanies"
                        :model-value="selectedCompanyId"
                        @update:model-value="emit('update:selectedCompanyId', $event)"
                    />
                </div>
                <RouteTabs />
            </div>
        </div>
    </header>
</template>
