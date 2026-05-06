import { cva } from "class-variance-authority";

export { default as Badge } from "./Badge.vue";

export const badgeVariants = cva(
  "inline-flex w-fit shrink-0 items-center justify-center gap-1 overflow-hidden rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-2 aria-invalid:ring-destructive/30 [&>svg]:size-3 [&>svg]:pointer-events-none",
  {
    variants: {
      variant: {
        default:
          "border-primary/40 bg-primary/15 text-[#c6cbff] [a&]:hover:bg-primary/20",
        secondary:
          "border-border bg-secondary text-secondary-foreground [a&]:hover:bg-accent",
        destructive:
          "border-destructive/40 bg-destructive/15 text-[#ffb8bd] [a&]:hover:bg-destructive/20 focus-visible:ring-destructive/30",
        outline:
          "border-border text-muted-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
        success:
          "border-[#27a644]/40 bg-[#27a644]/12 text-[#79d98d] [a&]:hover:bg-[#27a644]/20",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
);
