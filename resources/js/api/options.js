import { apiGet, apiPatch, apiPost } from './client';

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

export async function updateTimeEntry(entryId, entry) {
    const payload = await apiPatch(`/api/v1/time-entries/${entryId}`, entry);

    return payload.data ?? null;
}

export async function getTimeEntries(options = {}) {
    const normalizedOptions = typeof options === 'string'
        ? { companyId: options }
        : options ?? {};
    const params = new URLSearchParams();

    if (normalizedOptions.companyId) {
        params.set('filter[company_id]', normalizedOptions.companyId);
    }

    if (normalizedOptions.search) {
        params.set('filter[search]', normalizedOptions.search);
    }

    if (normalizedOptions.page) {
        params.set('page', normalizedOptions.page);
    }

    if (normalizedOptions.perPage) {
        params.set('per_page', normalizedOptions.perPage);
    }

    const query = params.toString();
    const payload = await apiGet(`/api/v1/time-entries${query ? `?${query}` : ''}`);

    return {
        entries: payload.data ?? [],
        links: payload.links ?? {},
        meta: payload.meta ?? {},
    };
}
