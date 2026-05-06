<script setup>
import { useVModel } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = defineProps({
    defaultValue: { type: String, required: false },
    modelValue: { type: String, required: false },
    class: {
        type: [Boolean, null, String, Object, Array],
        required: false,
        skipCheck: true,
    },
});

const emits = defineEmits(['update:modelValue']);

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});
</script>

<template>
    <textarea
        v-model="modelValue"
        data-slot="textarea"
        :class="cn(
            'min-h-20 w-full resize-none rounded-md border border-input bg-card px-3 py-2 text-sm text-foreground shadow-[inset_0_1px_0_rgb(255_255_255/0.03)] outline-none transition-[color,box-shadow,border-color] placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-40',
            'focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50',
            'aria-invalid:border-destructive aria-invalid:ring-2 aria-invalid:ring-destructive/30',
            props.class,
        )"
    />
</template>
