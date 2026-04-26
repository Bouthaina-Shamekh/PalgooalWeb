import { q } from './dom';

export async function fetchJson(url, { method = 'GET', body = null, headers = {} } = {}) {
    const csrf = q('meta[name="csrf-token"]')?.content || '';

    const res = await fetch(url, {
        method,
        credentials: 'include',
        redirect: 'manual',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(method !== 'GET' ? { 'Content-Type': 'application/json' } : {}),
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            ...headers,
        },
        ...(body ? { body: JSON.stringify(body) } : {}),
    });

    if (res.status >= 300 && res.status < 400) {
        throw new Error(`Redirect detected (${res.status}). Check auth/CSRF/middleware for: ${url}`);
    }

    const contentType = res.headers.get('content-type') || '';
    const isJson = contentType.includes('application/json');
    const data = isJson ? await res.json() : await res.text();

    const isWriteMethod = method !== 'GET';
    if (isWriteMethod && !isJson) {
        const preview = String(data || '').slice(0, 200).replace(/\s+/g, ' ');
        throw new Error(`Expected JSON but got "${contentType}". Response preview: ${preview}`);
    }

    if (!res.ok) {
        const msg = (data && data.message) ? data.message : `Request failed (${res.status})`;
        throw new Error(msg);
    }

    return data;
}
