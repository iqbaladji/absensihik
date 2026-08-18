<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import api, { errMsg } from '../api';
import { useAuth } from '../stores/auth';
import { toastOk, toastErr } from '../toast';
import MobileSubHeader from '../components/MobileSubHeader.vue';
import PageHeader from '../components/PageHeader.vue';

const auth = useAuth();
const router = useRouter();
const isPegawaiMobile = computed(() => auth.roleSlug === 'pegawai');
const hasPin = computed(() => !!auth.user?.has_pin);

const pinLama = ref('');
const pinBaru = ref('');
const pinBaruConfirm = ref('');
const saving = ref(false);

async function submit() {
    if (pinBaru.value.length !== 6 || !/^\d{6}$/.test(pinBaru.value)) {
        return toastErr('PIN baru harus 6 digit angka.');
    }
    if (pinBaru.value !== pinBaruConfirm.value) {
        return toastErr('Konfirmasi PIN tidak sama.');
    }
    saving.value = true;
    try {
        const payload = { pin_baru: pinBaru.value, pin_baru_confirmation: pinBaruConfirm.value };
        if (hasPin.value) payload.pin_lama = pinLama.value;
        await api.post('/auth/change-pin', payload);
        toastOk('PIN berhasil disimpan.');
        try { await auth.fetchMe(); } catch (_) {}
        router.push('/profil');
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div v-if="isPegawaiMobile" class="lg:hidden">
        <MobileSubHeader title="Ubah PIN Payslip" to="/profil" />

        <div class="mt-4 px-2">
            <div class="rounded-2xl bg-white p-4 shadow-sm">
                <p class="mb-4 text-sm text-slate-600">
                    PIN 6 digit digunakan untuk membuka Slip Gaji Anda.
                    <span v-if="!hasPin" class="font-medium text-hijau-700">Anda belum mengatur PIN — buat baru sekarang.</span>
                </p>
                <form @submit.prevent="submit" class="space-y-3">
                    <div v-if="hasPin">
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">PIN Lama</label>
                        <input v-model="pinLama" type="password" inputmode="numeric" maxlength="6"
                               class="w-full rounded-lg border-none bg-slate-100 px-3 py-3 text-center text-lg tracking-widest focus:outline-none focus:ring-2 focus:ring-hijau-500"
                               placeholder="• • • • • •" required />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">PIN Baru</label>
                        <input v-model="pinBaru" type="password" inputmode="numeric" maxlength="6"
                               class="w-full rounded-lg border-none bg-slate-100 px-3 py-3 text-center text-lg tracking-widest focus:outline-none focus:ring-2 focus:ring-hijau-500"
                               placeholder="• • • • • •" required />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Konfirmasi PIN Baru</label>
                        <input v-model="pinBaruConfirm" type="password" inputmode="numeric" maxlength="6"
                               class="w-full rounded-lg border-none bg-slate-100 px-3 py-3 text-center text-lg tracking-widest focus:outline-none focus:ring-2 focus:ring-hijau-500"
                               placeholder="• • • • • •" required />
                    </div>
                </form>
            </div>
        </div>

        <button type="button"
                class="fixed inset-x-4 bottom-20 z-20 rounded-full bg-hijau-600 py-3.5 text-center text-sm font-semibold text-white shadow-lg active:bg-hijau-700 disabled:opacity-50"
                :disabled="saving" @click="submit">
            {{ saving ? 'Menyimpan...' : (hasPin ? 'Ubah PIN' : 'Simpan PIN') }}
        </button>
    </div>

    <div v-else class="max-w-md">
        <PageHeader :title="hasPin ? 'Ubah PIN Payslip' : 'Buat PIN Payslip'" subtitle="PIN 6 digit untuk membuka slip gaji" />
        <div class="card-pad">
            <form @submit.prevent="submit" class="space-y-4">
                <div v-if="hasPin">
                    <label class="label">PIN Lama</label>
                    <input v-model="pinLama" type="password" class="input" maxlength="6" required />
                </div>
                <div>
                    <label class="label">PIN Baru</label>
                    <input v-model="pinBaru" type="password" class="input" maxlength="6" required />
                </div>
                <div>
                    <label class="label">Konfirmasi PIN Baru</label>
                    <input v-model="pinBaruConfirm" type="password" class="input" maxlength="6" required />
                </div>
                <button class="btn-hijau" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </form>
        </div>
    </div>
</template>
