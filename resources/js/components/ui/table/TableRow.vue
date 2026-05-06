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
    <tr
        data-slot="table-row"
        :class="
            cn(
                'border-b border-border transition-colors hover:bg-muted/45 data-[state=selected]:bg-muted',
                props.class,
            )
        "
    >
        <slot></slot>
    </tr>
</template>
