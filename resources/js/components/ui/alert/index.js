import { cva } from "class-variance-authority";

export { default as Alert } from "./Alert.vue";
export { default as AlertDescription } from "./AlertDescription.vue";
export { default as AlertTitle } from "./AlertTitle.vue";

export const alertVariants = cva(
  "relative grid w-full grid-cols-[0_1fr] items-start gap-y-0.5 rounded-lg border px-4 py-3 text-sm has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] has-[>svg]:gap-x-3 [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current",
  {
    variants: {
      variant: {
        default: "border-border bg-card text-card-foreground",
        destructive:
          "border-destructive/40 bg-destructive/10 text-[#ffb8bd] [&>svg]:text-current *:data-[slot=alert-description]:text-[#ffced2]",
        info: "border-primary/35 bg-primary/10 text-[#d7dbff] [&>svg]:text-current",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
);
