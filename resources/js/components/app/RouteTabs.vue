<script setup>
import { Clock3, History } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';
import { Button } from '@/components/ui/button';
</script>

<template>
    <nav class="flex h-10 items-center gap-1 rounded-lg border border-border bg-card p-1" aria-label="Primary">
        <Button
            as-child
            variant="tab"
            size="sm"
            class="h-8"
        >
            <RouterLink to="/entries/new" :data-active="$route.name === 'entries.new'">
                <Clock3 />
                New Entries
            </RouterLink>
        </Button>
        <Button
            as-child
            variant="tab"
            size="sm"
            class="h-8"
        >
            <RouterLink to="/entries/history" :data-active="$route.name === 'entries.history'">
                <History />
                History
            </RouterLink>
        </Button>
    </nav>
</template>
