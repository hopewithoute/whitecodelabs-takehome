<script setup>
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { spreadsheetCellVariants } from './cellVariants';

const props = defineProps({
    active: { type: Boolean, default: false },
    align: { type: String, default: 'left' },
    disabled: { type: Boolean, default: false },
    error: { type: [String, null], default: null },
    class: {
        type: [Boolean, null, String, Object, Array],
        default: null,
    },
});

const state = computed(() => {
    if (props.error) {
        return 'invalid';
    }

    if (props.disabled) {
        return 'disabled';
    }

    return props.active ? 'active' : 'idle';
});
</script>

<template>
    <button
        type="button"
        :disabled="disabled"
        :aria-invalid="Boolean(error)"
        :class="cn(spreadsheetCellVariants({ state, align }), props.class)"
    >
        <slot></slot>
    </button>
</template>
