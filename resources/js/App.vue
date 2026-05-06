<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterView } from 'vue-router';
import { useRouter } from 'vue-router';
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';
import ApiErrorBanner from '@/components/app/ApiErrorBanner.vue';
import WorkspaceHeader from '@/components/app/WorkspaceHeader.vue';
import { useCompanies } from '@/composables/useCompanies';

const selectedCompanyId = ref(null);
const historyRefreshKey = ref(0);
const historySearchFocusKey = ref(0);
const spreadsheetFocusKey = ref(0);
const shortcutLegendOpenKey = ref(0);
const router = useRouter();
const { companies, error, isLoading } = useCompanies();

function markHistoryStale() {
    historyRefreshKey.value += 1;
}

function handleGlobalShortcut(event) {
    if (event.key === '?' && !event.ctrlKey && !event.metaKey && !event.altKey) {
        event.preventDefault();
        shortcutLegendOpenKey.value += 1;
        return;
    }

    if (!event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
        return;
    }

    const key = event.key.toLowerCase();

    if (!['n', 'h', 'e', 's'].includes(key)) {
        return;
    }

    event.preventDefault();

    if (key === 'n') {
        router.push({ name: 'entries.new' });
        return;
    }

    if (key === 'h') {
        router.push({ name: 'entries.history' });
        return;
    }

    if (key === 's') {
        router.push({ name: 'entries.history' }).then(async () => {
            await nextTick();
            historySearchFocusKey.value += 1;
        });
        return;
    }

    router.push({ name: 'entries.new' }).then(async () => {
        await nextTick();
        spreadsheetFocusKey.value += 1;
    });
}

onMounted(() => window.addEventListener('keydown', handleGlobalShortcut));
onBeforeUnmount(() => window.removeEventListener('keydown', handleGlobalShortcut));
</script>

<template>
    <main class="min-h-screen bg-background text-foreground">
        <div class="mx-auto flex w-full max-w-[88rem] flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
            <WorkspaceHeader
                v-model:selected-company-id="selectedCompanyId"
                :companies="companies"
                :is-loading-companies="isLoading"
                :shortcut-legend-open-key="shortcutLegendOpenKey"
            />

            <ApiErrorBanner :error="error" />

            <RouterView v-slot="{ Component }">
                <component
                    :is="Component"
                    :companies="companies"
                    :history-refresh-key="historyRefreshKey"
                    :selected-company-id="selectedCompanyId"
                    :history-search-focus-key="historySearchFocusKey"
                    :spreadsheet-focus-key="spreadsheetFocusKey"
                    @time-entries-created="markHistoryStale"
                />
            </RouterView>
        </div>

        <Toaster
            theme="dark"
            position="bottom-right"
            rich-colors
            close-button
        />
    </main>
</template>
