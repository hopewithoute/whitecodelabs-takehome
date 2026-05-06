export async function apiGet(path) {
    const response = await apiRequest(path);

    return response.json();
}

export async function apiPost(path, body) {
    const response = await apiRequest(path, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
    });

    return response.json();
}

export async function apiPatch(path, body) {
    const response = await apiRequest(path, {
        method: 'PATCH',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
    });

    return response.json();
}

async function apiRequest(path, options = {}) {
    const response = await fetch(path, {
        ...options,
        headers: {
            Accept: 'application/json',
            ...options.headers,
        },
    });

    if (!response.ok) {
        const payload = await response.json().catch(() => ({}));
        const error = new Error(payload.message ?? `Request failed with status ${response.status}`);
        error.status = response.status;
        error.payload = payload;
        error.errors = payload.errors ?? {};

        throw error;
    }

    return response;
}
