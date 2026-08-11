import { defineStore } from 'pinia';
import api from '../api';

export const useAuth = defineStore('auth', {
    state: () => {
        let user = null;
        try { user = JSON.parse(localStorage.getItem('absensihik_user')); } catch (_) {}
        return {
            token: localStorage.getItem('absensihik_token') || null,
            user,
        };
    },
    getters: {
        isAuthenticated: (s) => !!s.token,
        roleSlug: (s) => s.user?.role?.slug || null,
        isAdmin: (s) => s.user?.role?.slug === 'administrator',
        hakAkses: (s) => s.user?.role?.hak_akses || {},
    },
    actions: {
        async login(username, password) {
            const { data } = await api.post('/auth/login', { username, password });
            const payload = data.data || data;
            this.setSession(payload.token, payload.user);
            return data;
        },
        async fetchMe() {
            const { data } = await api.get('/auth/me');
            const payload = data.data || data;
            this.user = payload.user || payload;
            localStorage.setItem('absensihik_user', JSON.stringify(this.user));
        },
        async logout() {
            try { await api.post('/auth/logout'); } catch (_) {}
            this.clear();
        },
        setSession(token, user) {
            this.token = token;
            this.user = user;
            localStorage.setItem('absensihik_token', token);
            localStorage.setItem('absensihik_user', JSON.stringify(user));
        },
        clear() {
            this.token = null;
            this.user = null;
            localStorage.removeItem('absensihik_token');
            localStorage.removeItem('absensihik_user');
        },
        can(modul, ability = 'R') {
            if (this.isAdmin) return true;
            const allowed = this.hakAkses[modul] || [];
            return allowed.includes(ability);
        },
    },
});
