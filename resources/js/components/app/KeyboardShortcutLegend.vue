<script setup>
import { Keyboard } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

const props = defineProps({
    openKey: { type: Number, default: 0 },
});

const isOpen = ref(false);

watch(() => props.openKey, () => {
    isOpen.value = true;
});

const shortcutGroups = [
    {
        title: 'General',
        shortcuts: [
            { keys: ['?'], label: 'Shortcuts', description: 'Open this keyboard shortcut legend' },
            { keys: ['Alt', 'N'], label: 'New entries', description: 'Switch to the new time entries form' },
            { keys: ['Alt', 'H'], label: 'History', description: 'Open the history view with past entries' },
            { keys: ['Alt', 'E'], label: 'Spreadsheet', description: 'Jump to the spreadsheet for quick data entry' },
            { keys: ['Alt', 'S'], label: 'Search', description: 'Focus the search field on the history page' },
            { keys: ['Ctrl', 'Enter'], label: 'Submit', description: 'Submit the current batch of entries' },
            { keys: ['Ctrl', 'D'], label: 'Duplicate row', description: 'Duplicate the selected row below' },
        ],
    },
    {
        title: 'Spreadsheet navigation',
        shortcuts: [
            { keys: ['←', '→', '↑', '↓'], label: 'Arrow keys', description: 'Move between spreadsheet cells' },
            { keys: ['Tab'], label: 'Next cell', description: 'Move to the next cell, wraps to the next row' },
            { keys: ['Shift', 'Tab'], label: 'Previous cell', description: 'Move to the previous cell, wraps up' },
            { keys: ['Enter'], label: 'Cell below', description: 'Confirm and move to the cell directly below' },
            { keys: ['Esc'], label: 'Cancel edit', description: 'Cancel the current cell edit and close the editor' },
        ],
    },
];
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <Button
                variant="ghost"
                size="icon-sm"
                aria-label="Keyboard shortcuts"
            >
                <Keyboard />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-80" align="end">
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-medium text-foreground">Keyboard shortcuts</p>
                    <p class="mt-0.5 text-[11px] text-muted-foreground">
                        Use these shortcuts to navigate and work faster without a mouse.
                    </p>
                </div>
                <div v-for="group in shortcutGroups" :key="group.title" class="space-y-2">
                    <p class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
                        {{ group.title }}
                    </p>
                    <div
                        v-for="shortcut in group.shortcuts"
                        :key="shortcut.label"
                        class="flex items-start justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <p class="text-xs text-foreground">{{ shortcut.label }}</p>
                            <p class="text-[11px] text-muted-foreground">{{ shortcut.description }}</p>
                        </div>
                        <span class="mt-0.5 inline-flex shrink-0 items-center gap-0.5">
                            <kbd
                                v-for="key in shortcut.keys"
                                :key="key"
                                class="rounded border border-border bg-card px-1.5 py-0.5 font-mono text-[10px] text-foreground"
                            >
                                {{ key }}
                            </kbd>
                        </span>
                    </div>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
