import { cva } from "class-variance-authority";

export { default as Button } from "./Button.vue";

export const buttonVariants = cva(
  "inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium tracking-normal outline-none transition-all disabled:pointer-events-none disabled:opacity-40 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-2 aria-invalid:ring-destructive/30",
  {
    variants: {
      variant: {
        default:
          "border border-primary/70 bg-primary text-primary-foreground shadow-[inset_0_1px_0_rgb(255_255_255/0.16)] hover:bg-[#828fff]",
        destructive:
          "border border-destructive/70 bg-destructive text-white hover:bg-destructive/90 focus-visible:ring-destructive/30",
        outline:
          "border border-border bg-background text-foreground hover:border-input hover:bg-accent hover:text-accent-foreground",
        secondary:
          "border border-border bg-secondary text-secondary-foreground hover:border-input hover:bg-accent hover:text-foreground",
        ghost:
          "text-muted-foreground hover:bg-accent hover:text-accent-foreground",
        tab: "text-muted-foreground hover:bg-accent hover:text-accent-foreground data-[active=true]:bg-secondary data-[active=true]:text-foreground",
        cell: "justify-start border border-transparent bg-transparent text-muted-foreground hover:border-border hover:bg-secondary hover:text-foreground data-[active=true]:border-ring data-[active=true]:bg-accent data-[active=true]:text-foreground",
        link: "text-primary underline-offset-4 hover:text-[#828fff] hover:underline",
      },
      size: {
        default: "h-9 px-3.5 py-2 has-[>svg]:px-3",
        sm: "h-8 gap-1.5 px-3 text-xs has-[>svg]:px-2.5",
        lg: "h-10 px-5 has-[>svg]:px-4",
        cell: "h-9 w-full px-2.5 py-1.5 text-left",
        icon: "size-9",
        "icon-sm": "size-8",
        "icon-lg": "size-10",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
);
