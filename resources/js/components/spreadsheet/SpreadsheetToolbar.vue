<script setup>
import { Copy, Plus, Send, Trash2 } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

defineProps({
    isSubmitting: { type: Boolean, default: false },
    rowCount: { type: Number, required: true },
});

const emit = defineEmits(['add-row', 'duplicate-row', 'clear-rows', 'submit']);
</script>

<template>
    <TooltipProvider>
        <div class="flex flex-col gap-3 border-b border-border bg-card px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <Badge variant="secondary">{{ rowCount }} rows</Badge>
                <span class="text-xs text-muted-foreground">Spreadsheet entry mode</span>
            </div>

            <div class="flex items-center gap-1.5">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button variant="secondary" size="sm" @click="emit('add-row')">
                            <Plus />
                            Add row
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Add a blank row</TooltipContent>
                </Tooltip>

                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button variant="ghost" size="icon-sm" @click="emit('duplicate-row')">
                            <Copy />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Duplicate active row</TooltipContent>
                </Tooltip>

                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button variant="ghost" size="icon-sm" @click="emit('clear-rows')">
                            <Trash2 />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Clear draft rows</TooltipContent>
                </Tooltip>

                <Separator orientation="vertical" class="mx-1 h-6" />

                <Button size="sm" :disabled="isSubmitting" @click="emit('submit')">
                    <Send />
                    {{ isSubmitting ? 'Submitting' : 'Submit batch' }}
                </Button>
            </div>
        </div>
    </TooltipProvider>
</template>
