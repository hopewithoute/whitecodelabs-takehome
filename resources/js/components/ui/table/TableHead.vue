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
    <th
        data-slot="table-head"
        :class="
            cn(
                'h-9 px-2 text-left align-middle text-xs font-medium tracking-normal whitespace-nowrap text-muted-foreground [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]',
                props.class,
            )
        "
    >
        <slot></slot>
    </th>
</template>
