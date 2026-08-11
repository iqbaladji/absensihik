<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastErr } from '../../toast';
import { tanggal, jam } from '../../util';
import PageHeader from '../../components/PageHeader.vue';
import StatCard from '../../components/StatCard.vue';

const stats = ref({});
const loading = ref(true);
const today = ref(null);

onMounted(async () => {
    try {
        const { data } = await api.get('/dashboard');
        stats.value = data.data || data;
        today.value = stats.value.presensi_hari_ini;
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <PageHeader title="Dashboard" subtitle="Ringkasan kehadiran dan aktivitas Anda" />

    <div v-if="loading" class="py-12 text-center text-slate-400">Memuat...</div>
    <template v-else>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard icon="⏰" label="Status Hari Ini" :value="today?.status || 'Belum Absen'" :tone="today?.status === 'hadir' ? 'green' : 'amber'" />
            <StatCard icon="🏖️" label="Sisa Cuti" :value="stats.sisa_cuti ?? '-'" tone="brand" />
            <StatCard icon="📋" label="Pengajuan Aktif" :value="stats.pengajuan_aktif ?? 0" tone="amber" />
            <StatCard icon="📢" label="Pengumuman Baru" :value="stats.pengumuman_belum_dibaca ?? 0" tone="slate" />
        </div>

        <div v-if="today" class="card mt-6 p-5">
            <h3 class="mb-3 font-semibold text-slate-700">Presensi Hari Ini</h3>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <div class="text-xs text-slate-500">Jam Masuk</div>
                    <div class="text-lg font-semibold text-slate-800">{{ jam(today.waktu_masuk) }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Jam Pulang</div>
                    <div class="text-lg font-semibold text-slate-800">{{ today.waktu_pulang ? jam(today.waktu_pulang) : '-' }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Tipe</div>
                    <div class="text-lg font-semibold text-slate-800">{{ today.tipe_kehadiran || '-' }}</div>
                </div>
            </div>
        </div>
    </template>
</template>
