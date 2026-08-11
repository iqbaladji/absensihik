import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('absensihik_token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

let onUnauthorized = null;
export function setUnauthorizedHandler(fn) { onUnauthorized = fn; }

api.interceptors.response.use(
    (res) => res,
    (err) => {
        if (err.response && err.response.status === 401) {
            if (onUnauthorized) onUnauthorized();
        }
        return Promise.reject(err);
    },
);

export function errMsg(err, fallback = 'Terjadi kesalahan.') {
    const r = err?.response?.data;
    if (!r) return err?.message || fallback;
    if (r.message && r.errors) {
        const first = Object.values(r.errors)[0];
        return Array.isArray(first) ? first[0] : r.message;
    }
    return r.message || fallback;
}

export default api;
