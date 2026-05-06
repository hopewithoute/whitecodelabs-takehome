<script setup>
import { RouterLink, RouterView } from 'vue-router';
</script>

<template>
    <main class="min-h-screen bg-background text-foreground">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-6 py-8">
            <header class="flex flex-col gap-4 border-b border-border pb-5">
                <div>
                    <p class="text-sm font-medium text-muted-foreground">Time entry</p>
                    <h1 class="text-2xl font-semibold">Employee time entries</h1>
                </div>

                <nav class="flex gap-2" aria-label="Primary">
                    <RouterLink
                        to="/entries/new"
                        class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-accent hover:text-accent-foreground"
                        active-class="bg-accent text-accent-foreground"
                    >
                        New Entries
                    </RouterLink>
                    <RouterLink
                        to="/entries/history"
                        class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-accent hover:text-accent-foreground"
                        active-class="bg-accent text-accent-foreground"
                    >
                        History
                    </RouterLink>
                </nav>
            </header>

            <RouterView />
        </div>
    </main>
</template>
