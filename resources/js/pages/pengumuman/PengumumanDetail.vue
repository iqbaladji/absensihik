<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api, { errMsg } from '../../api';
import { useAuth } from '../../stores/auth';
import { toastOk, toastErr } from '../../toast';
import { tanggalJam } from '../../util';
import PageHeader from '../../components/PageHeader.vue';

const route = useRoute();
const auth = useAuth();
const item = ref(null);
const loading = ref(true);
const tracking = ref(null);
const canTrack = auth.can('pengumuman', 'R') && (auth.roleSlug === 'hr' || auth.roleSlug === 'administrator');

onMounted(async () => {
    try {
        const { data } = await api.get(`/pengumuman/${route.params.id}`);
        item.value = data.data;
        if (canTrack) loadTracking();
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        loading.value = false;
    }
});

async function loadTracking() {
    try {
        const { data } = await api.get(`/pengumuman/${route.params.id}/tracking`);
        tracking.value = data.data || data;
    } catch (_) {}
}

async function confirmRead() {
    try {
        await api.post(`/pengumuman/${route.params.id}/confirm`);
        toastOk('Konfirmasi baca berhasil.');
        if (item.value) item.value.sudah_dibaca = true;
    } catch (e) {
        toastErr(errMsg(e));
    }
}
</script>

<template>
    <div v-if="loading" class="py-12 text-center text-slate-400">Memuat...</div>
    <template v-else-if="item">
        <PageHeader :title="item.judul">
            <template #actions>
                <RouterLink to="/pengumuman" class="btn-ghost">Kembali</RouterLink>
            </template>
        </PageHeader>

        <div class="card p-6">
            <div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <span class="badge" :class="item.prioritas === 'urgent' ? 'badge-red' : item.prioritas === 'tinggi' ? 'badge-amber' : 'badge-gray'">{{ item.prioritas }}</span>
                <span>{{ item.jenis?.nama }}</span>
                <span v-if="item.published_at">{{ tanggalJam(item.published_at) }}</span>
                <span v-if="item.user">oleh {{ item.user?.name }}</span>
            </div>
            <div class="prose max-w-none whitespace-pre-wrap text-slate-700">{{ item.isi }}</div>
        </div>

        <div v-if="item.wajib_konfirmasi && !item.sudah_dibaca" class="mt-4">
            <button class="btn-primary" @click="confirmRead">Konfirmasi Sudah Membaca</button>
        </div>
        <div v-else-if="item.wajib_konfirmasi && item.sudah_dibaca" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Anda sudah mengonfirmasi pengumuman ini.
        </div>

        <div v-if="tracking" class="card mt-6 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-3">
                <h3 class="font-semibold text-slate-700">Tracking Penerimaan</h3>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-3">
                <div class="text-center">
                    <div class="text-2xl font-bold text-slate-800">{{ tracking.total_penerima ?? 0 }}</div>
                    <div class="text-sm text-slate-500">Total Penerima</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-emerald-600">{{ tracking.dibaca ?? 0 }}</div>
                    <div class="text-sm text-slate-500">Sudah Dibaca</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-amber-600">{{ tracking.dikonfirmasi ?? 0 }}</div>
                    <div class="text-sm text-slate-500">Dikonfirmasi</div>
                </div>
            </div>
        </div>
    </template>
</template>
