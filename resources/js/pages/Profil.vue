<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../stores/auth';
import { toastOk, toastErr } from '../toast';
import { errMsg } from '../api';
import { isBiometricSupported, registerBiometric, listCredentials, deleteCredential } from '../webauthn';
import { isPushSupported, getPushStatus, enablePush, disablePush, testPush } from '../push';

const auth = useAuth();
const router = useRouter();

const bioSupported = ref(false);
const credentials = ref([]);
const bioBusy = ref(false);

const pushSupported = ref(false);
const pushEnabled = ref(false);
const pushBusy = ref(false);

onMounted(async () => {
    bioSupported.value = isBiometricSupported();
    if (bioSupported.value) refreshCredentials();

    pushSupported.value = isPushSupported();
    if (pushSupported.value) {
        try {
            const st = await getPushStatus();
            pushEnabled.value = st.enabled;
        } catch (_) {}
    }
});

async function togglePush() {
    pushBusy.value = true;
    try {
        if (pushEnabled.value) {
            await disablePush();
            pushEnabled.value = false;
            toastOk('Notifikasi dinonaktifkan.');
        } else {
            await enablePush();
            pushEnabled.value = true;
            toastOk('Notifikasi diaktifkan. Anda akan menerima pengingat absen.');
        }
    } catch (e) {
        toastErr(e?.message || errMsg(e, 'Gagal mengubah pengaturan notifikasi.'));
    } finally {
        pushBusy.value = false;
    }
}

async function doTestPush() {
    try {
        await testPush();
        toastOk('Test terkirim. Cek notifikasi HP Anda.');
    } catch (e) { toastErr(errMsg(e)); }
}

async function refreshCredentials() {
    try { credentials.value = await listCredentials(); } catch (_) {}
}

async function activate() {
    bioBusy.value = true;
    try {
        await registerBiometric();
        toastOk('Login biometrik diaktifkan untuk perangkat ini.');
        await refreshCredentials();
    } catch (e) {
        toastErr(errMsg(e, 'Gagal mengaktifkan biometrik.'));
    } finally {
        bioBusy.value = false;
    }
}

async function removeCred(id) {
    if (!confirm('Hapus perangkat biometrik ini?')) return;
    try {
        await deleteCredential(id);
        toastOk('Perangkat dihapus.');
        await refreshCredentials();
    } catch (e) { toastErr(errMsg(e)); }
}

async function doLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="lg:hidden -mx-3 -mt-4 sm:-mx-4 sm:-mt-6">
        <div class="bg-gradient-to-b from-hijau-500 to-hijau-700 px-5 pb-20 pt-8 text-center text-white"
             style="border-bottom-left-radius: 40% 24px; border-bottom-right-radius: 40% 24px;">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/25 text-3xl font-bold">
                {{ (auth.user?.name || '?').charAt(0) }}
            </div>
            <div class="mt-3 text-lg font-semibold">{{ auth.user?.name || '-' }}</div>
            <div class="text-sm text-white/80">{{ auth.user?.role?.nama || '-' }}</div>
        </div>

        <div class="-mt-12 px-4">
            <div class="rounded-2xl bg-white p-4 shadow-lg">
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500">NIP</dt><dd class="font-medium text-slate-700">{{ auth.user?.nip || '-' }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-700">{{ auth.user?.email || '-' }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500">Kantor</dt><dd class="font-medium text-slate-700">{{ auth.user?.kantor?.nama || '-' }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500">Unit</dt><dd class="font-medium text-slate-700">{{ auth.user?.unit?.nama || '-' }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500">Jabatan</dt><dd class="font-medium text-slate-700">{{ auth.user?.jabatan?.nama || '-' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="mt-4 space-y-2">
                <RouterLink to="/payslip" class="flex items-center justify-between rounded-xl bg-white p-4 shadow-sm">
                    <span class="text-sm font-medium text-slate-700">Slip Gaji Saya</span>
                    <span class="text-slate-400">›</span>
                </RouterLink>
                <RouterLink to="/saldo-cuti" class="flex items-center justify-between rounded-xl bg-white p-4 shadow-sm">
                    <span class="text-sm font-medium text-slate-700">Saldo Cuti</span>
                    <span class="text-slate-400">›</span>
                </RouterLink>
                <RouterLink to="/presensi/riwayat" class="flex items-center justify-between rounded-xl bg-white p-4 shadow-sm">
                    <span class="text-sm font-medium text-slate-700">Riwayat Presensi</span>
                    <span class="text-slate-400">›</span>
                </RouterLink>
                <RouterLink to="/ubah-pin" class="flex items-center justify-between rounded-xl bg-white p-4 shadow-sm">
                    <span class="text-sm font-medium text-slate-700">Ubah PIN Payslip</span>
                    <span class="text-slate-400">›</span>
                </RouterLink>

                <div v-if="pushSupported" class="rounded-xl bg-white p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-hijau-100 text-hijau-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.16V11a6 6 0 00-4-5.66V5a2 2 0 10-4 0v.34C7.67 6.16 6 8.39 6 11v3.16c0 .54-.21 1.05-.6 1.44L4 17h5m6 0v1a3 3 0 11-6 0v-1"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold text-slate-800">Notifikasi Pengingat</div>
                            <div class="text-xs text-slate-500">Pengingat absen masuk & pulang muncul di HP Anda walau app tertutup.</div>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button class="flex-1 rounded-full py-2.5 text-sm font-semibold text-white shadow-sm active:opacity-90 disabled:opacity-50"
                                :class="pushEnabled ? 'bg-rose-500' : 'bg-hijau-600'"
                                :disabled="pushBusy" @click="togglePush">
                            {{ pushBusy ? 'Memproses...' : (pushEnabled ? 'Nonaktifkan' : 'Aktifkan') }}
                        </button>
                        <button v-if="pushEnabled" class="rounded-full border border-hijau-600 px-4 text-sm font-semibold text-hijau-700 active:bg-hijau-50" @click="doTestPush">Test</button>
                    </div>
                </div>

                <div v-if="bioSupported" class="rounded-xl bg-white p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-hijau-100 text-hijau-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4a4 4 0 014 4v1a5 5 0 00-8 0V8a4 4 0 014-4zm-7 8a7 7 0 0114 0m-7 8a7 7 0 007-6"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold text-slate-800">Login Biometrik</div>
                            <div class="text-xs text-slate-500">Face ID / Sidik jari untuk login cepat di perangkat ini.</div>
                        </div>
                    </div>
                    <div v-if="credentials.length" class="mt-3 space-y-2 border-t border-slate-100 pt-3">
                        <div v-for="c in credentials" :key="c.id" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                            <div class="min-w-0">
                                <div class="truncate text-xs font-medium text-slate-700">{{ c.alias || 'Perangkat' }}</div>
                                <div class="text-[10px] text-slate-500">Terdaftar {{ new Date(c.created_at).toLocaleDateString('id-ID') }}</div>
                            </div>
                            <button class="text-xs font-semibold text-rose-600" @click="removeCred(c.id)">Hapus</button>
                        </div>
                    </div>
                    <button class="mt-3 w-full rounded-full bg-hijau-600 py-2.5 text-sm font-semibold text-white shadow-sm active:bg-hijau-700 disabled:opacity-50"
                            :disabled="bioBusy" @click="activate">
                        {{ bioBusy ? 'Memproses...' : (credentials.length ? 'Tambah Perangkat' : 'Aktifkan Biometrik') }}
                    </button>
                </div>

                <button class="w-full rounded-xl bg-rose-50 p-4 text-sm font-semibold text-rose-600" @click="doLogout">Keluar</button>
            </div>
        </div>
    </div>

    <div class="hidden lg:block">
        <h1 class="text-xl font-bold text-slate-800">Profil</h1>
        <div class="mt-4 card-pad max-w-lg">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-100 text-2xl font-bold text-brand-700">
                    {{ (auth.user?.name || '?').charAt(0) }}
                </div>
                <div>
                    <div class="text-lg font-semibold text-slate-800">{{ auth.user?.name }}</div>
                    <div class="text-sm text-slate-500">{{ auth.user?.role?.nama }}</div>
                </div>
            </div>
            <dl class="mt-6 divide-y divide-slate-100 text-sm">
                <div class="flex justify-between py-2"><dt class="text-slate-500">NIP</dt><dd>{{ auth.user?.nip || '-' }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-slate-500">Email</dt><dd>{{ auth.user?.email || '-' }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-slate-500">Kantor</dt><dd>{{ auth.user?.kantor?.nama || '-' }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-slate-500">Unit</dt><dd>{{ auth.user?.unit?.nama || '-' }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-slate-500">Jabatan</dt><dd>{{ auth.user?.jabatan?.nama || '-' }}</dd></div>
            </dl>
            <div class="mt-6 flex gap-2">
                <RouterLink to="/ubah-pin" class="btn-ghost">Ubah PIN Payslip</RouterLink>
                <button class="btn-danger" @click="doLogout">Keluar</button>
            </div>
        </div>
    </div>
</template>
