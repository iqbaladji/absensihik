<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from '../stores/auth';
import { errMsg } from '../api';
import { toastOk, toastErr } from '../toast';
import { isBiometricSupported, loginBiometric } from '../webauthn';
import KiblatJadwal from '../components/KiblatJadwal.vue';

const auth = useAuth();
const router = useRouter();
const route = useRoute();

const form = ref({ username: localStorage.getItem('sihadir_last_username') || '', password: '' });
const loading = ref(false);
const bioLoading = ref(false);
const bioSupported = ref(false);
const showKiblat = ref(false);

onMounted(() => { bioSupported.value = isBiometricSupported(); });

function salamAwal(nama) {
    const h = new Date().getHours();
    const waktu = h < 11 ? 'pagi' : h < 15 ? 'siang' : h < 18 ? 'sore' : 'malam';
    const emoji = h < 11 ? '☀️' : h < 15 ? '🌤️' : h < 18 ? '🌇' : '🌙';
    const nick = (nama || '').split(' ')[0] || 'kang';
    return `Selamat ${waktu}, ${nick}! ${emoji} Semangat berkarya hari ini 💚`;
}

async function submit() {
    loading.value = true;
    try {
        await auth.login(form.value.username, form.value.password);
        localStorage.setItem('sihadir_last_username', form.value.username);
        toastOk(salamAwal(auth.user?.name));
        router.push(route.query.redirect || '/');
    } catch (e) {
        toastErr(errMsg(e, 'Login gagal. Periksa username dan password.'));
    } finally {
        loading.value = false;
    }
}

async function loginBio() {
    if (!form.value.username) {
        return toastErr('Isi username dulu, lalu tap "Login Biometrik".');
    }
    bioLoading.value = true;
    try {
        const res = await loginBiometric(form.value.username);
        const payload = res.data || res;
        auth.setSession(payload.token, payload.user);
        localStorage.setItem('sihadir_last_username', form.value.username);
        toastOk(salamAwal(payload.user?.name));
        router.push(route.query.redirect || '/');
    } catch (e) {
        toastErr(errMsg(e, 'Login biometrik gagal.'));
    } finally {
        bioLoading.value = false;
    }
}
</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center p-4"
         :style="{ backgroundImage: `linear-gradient(rgba(16,60,25,0.72), rgba(16,60,25,0.72)), url('/gedung-hikp.jpg')`, backgroundSize: 'cover', backgroundPosition: 'center' }">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl sm:p-8">
            <div class="mb-6 text-center">
                <img src="/hikp-logo.png" alt="BPRS HIK Parahyangan" class="mx-auto mb-3 h-14 w-auto" />
                <h1 class="text-2xl font-bold tracking-wide text-hijau-700">SIHADIR</h1>
                <p class="mt-1 text-sm text-slate-500">Sistem Kehadiran BPRS HIK Parahyangan</p>
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
                <button type="submit" class="btn-hijau w-full py-3 text-base" :disabled="loading">
                    {{ loading ? 'Memproses...' : 'Masuk' }}
                </button>
            </form>

            <div v-if="bioSupported" class="mt-4">
                <div class="mb-3 flex items-center gap-2 text-xs uppercase tracking-wide text-slate-400">
                    <span class="h-px flex-1 bg-slate-200"></span>
                    <span>atau</span>
                    <span class="h-px flex-1 bg-slate-200"></span>
                </div>
                <button type="button"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-hijau-600 py-3 text-sm font-semibold text-hijau-700 hover:bg-hijau-50 active:bg-hijau-100 disabled:opacity-50"
                        :disabled="bioLoading" @click="loginBio">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4a4 4 0 014 4v1a5 5 0 00-8 0V8a4 4 0 014-4zm-7 8a7 7 0 0114 0m-7 8a7 7 0 007-6"/></svg>
                    {{ bioLoading ? 'Memindai...' : 'Login dengan Biometrik' }}
                </button>
            </div>

            <button type="button"
                    class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg py-2 text-sm font-medium text-hijau-700 hover:bg-hijau-50 active:bg-hijau-100"
                    @click="showKiblat = true">
                🕋 Kiblat & Jadwal Sholat
            </button>
        </div>
        <KiblatJadwal v-if="showKiblat" @close="showKiblat = false" />
    </div>
</template>
