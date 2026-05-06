<script setup>
import { computed, nextTick, ref } from 'vue';
import { Check, ChevronsUpDown } from 'lucide-vue-next';
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
    active: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: [String, null], default: null },
    isLoading: { type: Boolean, default: false },
    modelValue: { type: [String, null], default: null },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, required: true },
    searchPlaceholder: { type: String, default: 'Filter options...' },
});

const emit = defineEmits(['focus', 'keydown', 'select']);

const open = ref(false);
const trigger = ref(null);

const selectedOption = computed(() => props.options.find((option) => option.id === props.modelValue) ?? null);
const selectedLabel = computed(() => selectedOption.value?.name ?? props.placeholder);

function focus() {
    if (typeof trigger.value?.focus === 'function') {
        trigger.value.focus();
        return;
    }

    trigger.value?.$el?.focus();
}

async function openPicker() {
    if (props.disabled) {
        return;
    }

    open.value = true;
    await nextTick();
}

function selectOption(option) {
    emit('select', option);
    open.value = false;
}

function handleTriggerKeydown(event) {
    if (!['Enter', ' ', 'F2'].includes(event.key)) {
        emit('keydown', event);
        return;
    }

    event.preventDefault();
    openPicker();
}

defineExpose({ focus });
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                ref="trigger"
                type="button"
                variant="cell"
                size="cell"
                data-editor="trigger"
                :data-active="active"
                :disabled="disabled"
                :aria-invalid="Boolean(error)"
                :aria-expanded="open"
                class="justify-between"
                @focus="emit('focus')"
                @keydown="handleTriggerKeydown"
            >
                <span :class="cn('truncate', selectedOption ? 'text-foreground' : 'text-muted-foreground')">
                    {{ selectedLabel }}
                </span>
                <ChevronsUpDown class="text-muted-foreground" />
            </Button>
        </PopoverTrigger>

        <PopoverContent class="w-72 p-1" align="start">
            <Command>
                <CommandInput :placeholder="searchPlaceholder" />
                <CommandList>
                    <CommandEmpty>{{ isLoading ? 'Loading options...' : 'No options found.' }}</CommandEmpty>
                    <CommandGroup>
                        <CommandItem
                            v-for="option in options"
                            :key="option.id"
                            :value="option.name"
                            @select="selectOption(option)"
                        >
                            <Check
                                :class="cn('text-primary', modelValue === option.id ? 'opacity-100' : 'opacity-0')"
                            />
                            <span>{{ option.name }}</span>
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>
