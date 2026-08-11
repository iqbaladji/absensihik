<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastErr } from '../../toast';
import PageHeader from '../../components/PageHeader.vue';
import StatCard from '../../components/StatCard.vue';

const stats = ref({});
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await api.get('/dashboard/hr');
        stats.value = data.data || data;
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <PageHeader title="Dashboard HR" subtitle="Ringkasan kehadiran dan workforce seluruh perusahaan" />

    <div v-if="loading" class="py-12 text-center text-slate-400">Memuat...</div>
    <template v-else>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard icon="👥" label="Total Pegawai" :value="stats.total_pegawai ?? 0" tone="brand" />
            <StatCard icon="✅" label="Tingkat Kehadiran" :value="`${stats.tingkat_kehadiran ?? 0}%`" tone="green" />
            <StatCard icon="🔍" label="Perlu Verifikasi" :value="stats.perlu_verifikasi ?? 0" tone="amber" />
            <StatCard icon="🏖️" label="Sedang Cuti" :value="stats.sedang_cuti ?? 0" tone="slate" />
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="card p-5">
                <h3 class="mb-3 font-semibold text-slate-700">Ringkasan Cuti</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Pengajuan Pending</span><span class="font-medium">{{ stats.cuti_pending ?? 0 }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Disetujui Bulan Ini</span><span class="font-medium">{{ stats.cuti_disetujui_bulan_ini ?? 0 }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Ditolak Bulan Ini</span><span class="font-medium">{{ stats.cuti_ditolak_bulan_ini ?? 0 }}</span></div>
                </div>
            </div>
            <div class="card p-5">
                <h3 class="mb-3 font-semibold text-slate-700">Ringkasan Lembur</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Pengajuan Pending</span><span class="font-medium">{{ stats.lembur_pending ?? 0 }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Total Jam Bulan Ini</span><span class="font-medium">{{ stats.total_jam_lembur ?? 0 }} jam</span></div>
                </div>
            </div>
        </div>
    </template>
</template>
