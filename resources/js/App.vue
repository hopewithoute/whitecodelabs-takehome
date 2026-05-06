<script setup>
import { ref } from 'vue';
import { RouterView } from 'vue-router';
import ApiErrorBanner from '@/components/app/ApiErrorBanner.vue';
import WorkspaceHeader from '@/components/app/WorkspaceHeader.vue';
import { useCompanies } from '@/composables/useCompanies';

const selectedCompanyId = ref(null);
const { companies, error, isLoading } = useCompanies();
</script>

<template>
    <main class="min-h-screen bg-background text-foreground">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
            <WorkspaceHeader
                v-model:selected-company-id="selectedCompanyId"
                :companies="companies"
                :is-loading-companies="isLoading"
            />

            <ApiErrorBanner :error="error" />

            <RouterView v-slot="{ Component }">
                <component :is="Component" :companies="companies" :selected-company-id="selectedCompanyId" />
            </RouterView>
        </div>
    </main>
</template>
