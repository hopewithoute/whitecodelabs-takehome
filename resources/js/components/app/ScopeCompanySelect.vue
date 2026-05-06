<script setup>
import { computed, ref } from 'vue';
import { Check, ChevronsUpDown } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const props = defineProps({
    companies: { type: Array, required: true },
    isLoading: { type: Boolean, default: false },
    modelValue: { type: [String, null], default: null },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);

const selectedLabel = computed(() => {
    if (!props.modelValue) {
        return 'All companies';
    }

    return props.companies.find((company) => company.id === props.modelValue)?.name ?? 'Unknown company';
});

function selectCompany(id) {
    emit('update:modelValue', id);
    open.value = false;
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="secondary"
                class="h-10 min-w-56 justify-between border-border bg-card text-foreground"
                :aria-expanded="open"
            >
                <span class="truncate">{{ selectedLabel }}</span>
                <ChevronsUpDown class="text-muted-foreground" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-72 p-1" align="end">
            <Command>
                <CommandInput placeholder="Filter companies..." />
                <CommandList>
                    <CommandEmpty>No companies found.</CommandEmpty>
                    <CommandGroup>
                        <CommandItem value="all-companies" @select="selectCompany(null)">
                            <Check :class="cn('text-primary', modelValue ? 'opacity-0' : 'opacity-100')" />
                            <span>All companies</span>
                            <Badge variant="secondary" class="ml-auto">Default</Badge>
                        </CommandItem>
                        <CommandItem
                            v-for="company in companies"
                            :key="company.id"
                            :value="company.name"
                            @select="selectCompany(company.id)"
                        >
                            <Check
                                :class="cn('text-primary', modelValue === company.id ? 'opacity-100' : 'opacity-0')"
                            />
                            <span>{{ company.name }}</span>
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
            <div v-if="isLoading" class="border-t border-border px-3 py-2 text-xs text-muted-foreground">
                Loading companies
            </div>
        </PopoverContent>
    </Popover>
</template>
