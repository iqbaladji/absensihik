<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from '../stores/auth';
import { errMsg } from '../api';
import { toastOk, toastErr } from '../toast';

const auth = useAuth();
const router = useRouter();
const route = useRoute();

const form = ref({ username: '', password: '' });
const loading = ref(false);

async function submit() {
    loading.value = true;
    try {
        await auth.login(form.value.username, form.value.password);
        toastOk('Login berhasil');
        router.push(route.query.redirect || '/');
    } catch (e) {
        toastErr(errMsg(e, 'Login gagal. Periksa username dan password.'));
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-brand-700 to-brand-900 p-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-xl">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-xl bg-brand-100 text-2xl font-bold text-brand-700">A</div>
                <h1 class="text-xl font-bold text-slate-800">AbsensiHIK</h1>
                <p class="mt-1 text-sm text-slate-500">BPRS HIK Parahyangan</p>
            </div>
            <form @submit.prevent="submit">
                <div class="mb-4">
                    <label class="label">Username</label>
                    <input v-model="form.username" type="text" class="input" placeholder="Username" required autofocus />
                </div>
                <div class="mb-6">
                    <label class="label">Password</label>
                    <input v-model="form.password" type="password" class="input" placeholder="Password" required />
                </div>
                <button type="submit" class="btn-primary w-full" :disabled="loading">
                    {{ loading ? 'Memproses...' : 'Masuk' }}
                </button>
            </form>
        </div>
    </div>
</template>
