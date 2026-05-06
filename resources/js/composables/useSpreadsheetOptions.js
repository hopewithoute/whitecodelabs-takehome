import { reactive } from 'vue';
import {
    getCompanyEmployees,
    getCompanyProjects,
    getCompanyTasks,
} from '@/api/options';

export function useSpreadsheetOptions(companies, onSubmitError) {
    const optionsByCompany = reactive({});
    const loadingOptions = reactive({});
    const projectsByCompanyEmployee = reactive({});

    function companyEmployeeKey(companyId, employeeId) {
        return `${companyId}:${employeeId}`;
    }

    function companyOptionBucket(companyId) {
        if (!companyId) {
            return { employees: [], projects: [], tasks: [] };
        }

        return optionsByCompany[companyId] ?? { employees: [], projects: [], tasks: [] };
    }

    function getOptions(column, row) {
        if (column === 'company') {
            return companies.value;
        }

        if (column === 'employee') {
            return companyOptionBucket(row.company).employees;
        }

        if (column === 'project') {
            if (!row.company) {
                return [];
            }

            if (!row.employee) {
                return companyOptionBucket(row.company).projects;
            }

            return projectsByCompanyEmployee[companyEmployeeKey(row.company, row.employee)] ?? [];
        }

        if (column === 'task') {
            return companyOptionBucket(row.company).tasks;
        }

        return [];
    }

    async function ensureCompanyOptions(companyId) {
        if (!companyId || optionsByCompany[companyId] || loadingOptions[companyId]) {
            return;
        }

        loadingOptions[companyId] = true;

        try {
            const [employees, projects, tasks] = await Promise.all([
                getCompanyEmployees(companyId),
                getCompanyProjects(companyId),
                getCompanyTasks(companyId),
            ]);

            optionsByCompany[companyId] = { employees, projects, tasks };
        } catch (exception) {
            onSubmitError(exception.message);
        } finally {
            loadingOptions[companyId] = false;
        }
    }

    async function ensureEmployeeProjects(companyId, employeeId) {
        if (!companyId || !employeeId) {
            return;
        }

        const key = companyEmployeeKey(companyId, employeeId);

        if (projectsByCompanyEmployee[key]) {
            return;
        }

        try {
            projectsByCompanyEmployee[key] = await getCompanyProjects(companyId, employeeId);
        } catch (exception) {
            onSubmitError(exception.message);
        }
    }

    function handleSelect(column, row, option) {
        if (column === 'company') {
            row.company = option.id;
            row.employee = null;
            row.project = null;
            row.task = null;
            ensureCompanyOptions(row.company);
            return;
        }

        if (column === 'employee') {
            row.employee = option.id;
            row.project = null;
            ensureEmployeeProjects(row.company, row.employee);
            return;
        }

        if (column === 'project') {
            row.project = option.id;
            return;
        }

        if (column === 'task') {
            row.task = option.id;
        }
    }

    return {
        loadingOptions,
        getOptions,
        handleSelect,
        ensureCompanyOptions,
        ensureEmployeeProjects,
    };
}
