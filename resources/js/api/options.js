import { apiGet, apiPost } from './client';

export async function getCompanies() {
    const payload = await apiGet('/api/v1/companies');

    return payload.data ?? [];
}

export async function getCompanyEmployees(companyId) {
    const payload = await apiGet(`/api/v1/companies/${companyId}/employees`);

    return payload.data ?? [];
}

export async function getCompanyProjects(companyId, employeeId = null) {
    const params = new URLSearchParams();

    if (employeeId) {
        params.set('filter[employee_id]', employeeId);
    }

    const query = params.toString();
    const payload = await apiGet(`/api/v1/companies/${companyId}/projects${query ? `?${query}` : ''}`);

    return payload.data ?? [];
}

export async function getCompanyTasks(companyId) {
    const payload = await apiGet(`/api/v1/companies/${companyId}/tasks`);

    return payload.data ?? [];
}

export async function createTimeEntries(entries) {
    const payload = await apiPost('/api/v1/time-entries', { entries });

    return payload.data ?? [];
}
