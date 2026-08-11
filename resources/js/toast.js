import { reactive } from 'vue';

export const toasts = reactive([]);
let seq = 0;

export function toast(message, type = 'success', timeout = 3500) {
    const id = ++seq;
    toasts.push({ id, message, type });
    setTimeout(() => {
        const i = toasts.findIndex((t) => t.id === id);
        if (i !== -1) toasts.splice(i, 1);
    }, timeout);
}

export const toastOk = (m) => toast(m, 'success');
export const toastErr = (m) => toast(m, 'error', 5000);
