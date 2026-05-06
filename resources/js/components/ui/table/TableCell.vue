<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
  class: {
    type: [Boolean, null, String, Object, Array],
    required: false,
    skipCheck: true,
  },
});
</script>

<template>
    <td
        data-slot="table-cell"
        :class="
            cn(
                'p-2 align-middle whitespace-nowrap text-foreground [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]',
                props.class,
            )
        "
    >
        <slot></slot>
    </td>
</template>
