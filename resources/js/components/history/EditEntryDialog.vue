<script setup>
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import SpreadsheetDateCell from '@/components/spreadsheet/SpreadsheetDateCell.vue';

const props = defineProps({
    form: { type: Object, required: true },
    options: { type: Object, required: true },
    errors: { type: Object, required: true },
    isOpen: { type: Boolean, required: true },
    isLoadingOptions: { type: Boolean, required: true },
    isSaving: { type: Boolean, required: true },
    fieldError: { type: Function, required: true },
    changeCompany: { type: Function, required: true },
    changeEmployee: { type: Function, required: true },
    save: { type: Function, required: true },
    close: { type: Function, required: true },
    updateField: { type: Function, required: true },
});

function onCompanyChange(event) {
    props.updateField('company_id', event.target.value);
    props.changeCompany();
}

function onEmployeeChange(event) {
    props.updateField('employee_id', event.target.value);
    props.changeEmployee();
}

function onProjectChange(event) {
    props.updateField('project_id', event.target.value);
}

function onTaskChange(event) {
    props.updateField('task_id', event.target.value);
}
</script>

<template>
    <Dialog :open="isOpen" @update:open="close">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Edit time entry</DialogTitle>
            </DialogHeader>

            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
                <label class="space-y-1.5 text-sm">
                    <span class="text-muted-foreground">Company</span>
                    <select
                        :value="form.company_id"
                        class="h-9 w-full rounded-md border border-input bg-card px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                        :aria-invalid="Boolean(fieldError('company_id'))"
                        :disabled="isLoadingOptions || isSaving"
                        @change="onCompanyChange"
                    >
                        <option value="">Choose a company</option>
                        <option v-for="company in options.companies" :key="company.id" :value="company.id">
                            {{ company.name }}
                        </option>
                    </select>
                    <span v-if="fieldError('company_id')" class="text-xs text-destructive">
                        {{ fieldError('company_id') }}
                    </span>
                </label>

                <label class="space-y-1.5 text-sm">
                    <span class="text-muted-foreground">Date</span>
                    <SpreadsheetDateCell
                        :model-value="form.entry_date"
                        placeholder="Set date"
                        :error="fieldError('entry_date')"
                        trigger-variant="outline"
                        trigger-class="bg-card text-foreground"
                        @select="updateField('entry_date', $event)"
                    />
                    <span v-if="fieldError('entry_date')" class="text-xs text-destructive">
                        {{ fieldError('entry_date') }}
                    </span>
                </label>

                <label class="space-y-1.5 text-sm">
                    <span class="text-muted-foreground">Employee</span>
                    <select
                        :value="form.employee_id"
                        class="h-9 w-full rounded-md border border-input bg-card px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                        :aria-invalid="Boolean(fieldError('employee_id'))"
                        :disabled="!form.company_id || isLoadingOptions || isSaving"
                        @change="onEmployeeChange"
                    >
                        <option value="">Choose an employee</option>
                        <option v-for="employee in options.employees" :key="employee.id" :value="employee.id">
                            {{ employee.name }}
                        </option>
                    </select>
                    <span v-if="fieldError('employee_id')" class="text-xs text-destructive">
                        {{ fieldError('employee_id') }}
                    </span>
                </label>

                <label class="space-y-1.5 text-sm">
                    <span class="text-muted-foreground">Project</span>
                    <select
                        :value="form.project_id"
                        class="h-9 w-full rounded-md border border-input bg-card px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                        :aria-invalid="Boolean(fieldError('project_id'))"
                        :disabled="!form.company_id || isLoadingOptions || isSaving"
                        @change="onProjectChange"
                    >
                        <option value="">Choose a project</option>
                        <option v-for="project in options.projects" :key="project.id" :value="project.id">
                            {{ project.name }}
                        </option>
                    </select>
                    <span v-if="fieldError('project_id')" class="text-xs text-destructive">
                        {{ fieldError('project_id') }}
                    </span>
                </label>

                <label class="space-y-1.5 text-sm">
                    <span class="text-muted-foreground">Task</span>
                    <select
                        :value="form.task_id"
                        class="h-9 w-full rounded-md border border-input bg-card px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/50"
                        :aria-invalid="Boolean(fieldError('task_id'))"
                        :disabled="!form.company_id || isLoadingOptions || isSaving"
                        @change="onTaskChange"
                    >
                        <option value="">Choose a task</option>
                        <option v-for="task in options.tasks" :key="task.id" :value="task.id">
                            {{ task.name }}
                        </option>
                    </select>
                    <span v-if="fieldError('task_id')" class="text-xs text-destructive">
                        {{ fieldError('task_id') }}
                    </span>
                </label>

                <label class="space-y-1.5 text-sm">
                    <span class="text-muted-foreground">Hours</span>
                    <Input
                        :model-value="form.hours"
                        inputmode="decimal"
                        placeholder="0.00"
                        :aria-invalid="Boolean(fieldError('hours'))"
                        :disabled="isSaving"
                        @update:model-value="updateField('hours', $event)"
                    />
                    <span v-if="fieldError('hours')" class="text-xs text-destructive">
                        {{ fieldError('hours') }}
                    </span>
                </label>
            </form>

            <DialogFooter>
                <Button variant="ghost" :disabled="isSaving" @click="close">Cancel</Button>
                <Button :disabled="isSaving || isLoadingOptions" @click="save">
                    {{ isSaving ? 'Saving' : 'Save changes' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
