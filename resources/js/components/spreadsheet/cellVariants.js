import { cva } from 'class-variance-authority';

export const spreadsheetCellVariants = cva(
    'flex h-9 w-full items-center rounded-md border px-2.5 text-sm transition-[border-color,background-color,color,box-shadow] focus-visible:outline-none',
    {
        variants: {
            state: {
                idle: 'border-transparent bg-transparent text-muted-foreground hover:border-border hover:bg-secondary hover:text-foreground',
                active: 'border-ring bg-accent text-foreground ring-2 ring-ring/35',
                invalid: 'border-destructive bg-destructive/10 text-[#ffced2] ring-2 ring-destructive/20',
                disabled: 'border-transparent bg-transparent text-muted-foreground/50',
            },
            align: {
                left: 'justify-start text-left',
                right: 'justify-end text-right tabular-nums',
                center: 'justify-center text-center',
            },
        },
        defaultVariants: {
            state: 'idle',
            align: 'left',
        },
    },
);
