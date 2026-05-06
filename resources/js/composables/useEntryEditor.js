import { ref } from 'vue';
import { toast } from 'vue-sonner';
import {
    getCompanies,
    getCompanyEmployees,
    getCompanyProjects,
    getCompanyTasks,
    updateTimeEntry,
} from '@/api/options';

function blankForm() {
    return {
        company_id: '',
        employee_id: '',
        project_id: '',
        task_id: '',
        entry_date: '',
        hours: '',
    };
}

export function useEntryEditor(onSaved) {
    const isOpen = ref(false);
    const entry = ref(null);
    const errors = ref({});
    const form = ref(blankForm());
    const options = ref({
        companies: [],
        employees: [],
        projects: [],
        tasks: [],
    });
    const isLoadingOptions = ref(false);
    const isSaving = ref(false);

    function fieldError(field) {
        return errors.value[field]?.[0] ?? errors.value[`entries.0.${field}`]?.[0] ?? null;
    }

    async function loadOptions(companyId, employeeId = null) {
        if (!companyId) {
            options.value = {
                companies: await getCompanies(),
                employees: [],
                projects: [],
                tasks: [],
            };
            return;
        }

        isLoadingOptions.value = true;

        try {
            const [companies, employees, projects, tasks] = await Promise.all([
                getCompanies(),
                getCompanyEmployees(companyId),
                getCompanyProjects(companyId, employeeId),
                getCompanyTasks(companyId),
            ]);

            options.value = { companies, employees, projects, tasks };
        } finally {
            isLoadingOptions.value = false;
        }
    }

    async function open(targetEntry) {
        entry.value = targetEntry;
        errors.value = {};
        form.value = {
            company_id: targetEntry.company_id,
            employee_id: targetEntry.employee_id,
            project_id: targetEntry.project_id,
            task_id: targetEntry.task_id,
            entry_date: targetEntry.entry_date,
            hours: targetEntry.hours,
        };
        isOpen.value = true;

        await loadOptions(targetEntry.company_id, targetEntry.employee_id);
    }

    function close() {
        isOpen.value = false;
    }

    async function changeCompany() {
        form.value.employee_id = '';
        form.value.project_id = '';
        form.value.task_id = '';
        await loadOptions(form.value.company_id);
    }

    async function changeEmployee() {
        form.value.project_id = '';
        options.value.projects = form.value.company_id
            ? await getCompanyProjects(form.value.company_id, form.value.employee_id)
            : [];
    }

    function updateField(field, value) {
        form.value[field] = value;
    }

    async function save() {
        if (!entry.value || isSaving.value) {
            return;
        }

        isSaving.value = true;
        errors.value = {};

        try {
            await updateTimeEntry(entry.value.id, form.value);
            isOpen.value = false;
            toast.success('Time entry updated.', {
                description: 'History and summary totals were refreshed.',
            });
            await onSaved();
        } catch (exception) {
            errors.value = exception.errors ?? {};
            toast.error('Time entry was not updated.', {
                description: exception.message,
            });
        } finally {
            isSaving.value = false;
        }
    }

    return {
        // State
        isOpen,
        errors,
        form,
        options,
        isLoadingOptions,
        isSaving,

        // Methods
        fieldError,
        open,
        close,
        changeCompany,
        changeEmployee,
        save,
        updateField,
    };
}
