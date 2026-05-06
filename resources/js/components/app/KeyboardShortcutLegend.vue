<script setup>
const shortcuts = [
    { keys: ['Alt', 'N'], label: 'New entries' },
    { keys: ['Alt', 'H'], label: 'History' },
    { keys: ['Alt', 'E'], label: 'Spreadsheet' },
    { keys: ['Ctrl', 'Enter'], label: 'Submit' },
    { keys: ['Ctrl', 'D'], label: 'Duplicate row' },
];
</script>

<template>
    <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground" aria-label="Keyboard shortcuts">
        <span class="font-medium text-foreground">Shortcuts</span>
        <span
            v-for="shortcut in shortcuts"
            :key="shortcut.label"
            class="inline-flex items-center gap-1 rounded-md border border-border bg-background/70 px-2 py-1"
        >
            <span class="inline-flex items-center gap-0.5">
                <kbd
                    v-for="key in shortcut.keys"
                    :key="key"
                    class="rounded border border-border bg-card px-1.5 py-0.5 font-mono text-[10px] text-foreground"
                >
                    {{ key }}
                </kbd>
            </span>
            <span>{{ shortcut.label }}</span>
        </span>
    </div>
</template>
