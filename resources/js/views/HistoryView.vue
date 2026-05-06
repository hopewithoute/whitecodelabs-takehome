<script setup>
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

defineProps({
    selectedCompanyId: { type: [String, null], default: null },
});

const placeholderRows = [
    ['Acme Operations', 'Jan 5, 2026', 'Ava Chen', 'Website Redesign', 'Development', '4.00'],
    ['Acme Operations', 'Jan 5, 2026', 'Ava Chen', 'Website Redesign', 'Review', '2.00'],
    ['Globex Services', 'Jan 6, 2026', 'Dev Malik', 'Client Support', 'Support', '3.50'],
];
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold tracking-[-0.02em]">History</h2>
                <p class="text-sm text-muted-foreground">Read-only time entries, ready for API data binding.</p>
            </div>
            <Badge variant="secondary">{{ selectedCompanyId ? 'Company scope' : 'All companies' }}</Badge>
        </div>

        <section class="overflow-hidden rounded-xl border border-border bg-card shadow-[inset_0_1px_0_rgb(255_255_255/0.04)]">
            <Table>
                <TableHeader>
                    <TableRow class="bg-background/70 hover:bg-background/70">
                        <TableHead>Company</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Employee</TableHead>
                        <TableHead>Project</TableHead>
                        <TableHead>Task</TableHead>
                        <TableHead class="text-right">Hours</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="row in placeholderRows" :key="row.join('-')">
                        <TableCell v-for="(value, index) in row" :key="value" :class="index === 5 ? 'text-right tabular-nums' : ''">
                            {{ value }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </section>
    </div>
</template>
