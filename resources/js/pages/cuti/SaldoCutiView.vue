<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastErr } from '../../toast';
import PageHeader from '../../components/PageHeader.vue';
import StatCard from '../../components/StatCard.vue';

const saldo = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await api.get('/cuti-tahunan-saldo');
        saldo.value = data.data || data;
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <PageHeader title="Saldo Cuti" subtitle="Informasi sisa cuti tahunan Anda" />

    <div v-if="loading" class="py-12 text-center text-slate-400">Memuat...</div>
    <template v-else-if="saldo">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard icon="🎯" label="Jatah Cuti" :value="saldo.jatah ?? 12" tone="brand" />
            <StatCard icon="✅" label="Terpakai" :value="saldo.terpakai ?? 0" tone="amber" />
            <StatCard icon="💰" label="Sisa" :value="saldo.sisa ?? 0" tone="green" />
            <StatCard icon="📦" label="Block Leave" :value="saldo.block_leave_terpakai ? 'Sudah' : 'Belum'" :tone="saldo.block_leave_terpakai ? 'green' : 'amber'" />
        </div>

        <div v-if="saldo.riwayat?.length" class="card mt-6 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-3">
                <h3 class="font-semibold text-slate-700">Riwayat Penggunaan</h3>
            </div>
            <div class="divide-y divide-slate-100">
                <div v-for="r in saldo.riwayat" :key="r.id" class="flex items-center justify-between px-5 py-3 text-sm">
                    <div>
                        <div class="font-medium text-slate-700">{{ r.jenis }}</div>
                        <div class="text-xs text-slate-500">{{ r.tanggal_mulai }} - {{ r.tanggal_selesai }}</div>
                    </div>
                    <span class="font-semibold text-slate-700">{{ r.jumlah_hari }} hari</span>
                </div>
            </div>
        </div>
    </template>
</template>
