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
        const { data } = await api.get('/dashboard/supervisor');
        stats.value = data.data || data;
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <PageHeader title="Dashboard Supervisor" subtitle="Ringkasan tim dan approval" />

    <div v-if="loading" class="py-12 text-center text-slate-400">Memuat...</div>
    <template v-else>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard icon="👥" label="Jumlah Tim" :value="stats.jumlah_tim ?? 0" tone="brand" />
            <StatCard icon="📋" label="Pending Approval" :value="stats.pending_approval ?? 0" tone="amber" />
            <StatCard icon="✅" label="Hadir Hari Ini" :value="stats.hadir_hari_ini ?? 0" tone="green" />
            <StatCard icon="❌" label="Tidak Hadir" :value="stats.tidak_hadir ?? 0" tone="red" />
        </div>

        <div v-if="stats.tim_hari_ini?.length" class="card mt-6 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-3">
                <h3 class="font-semibold text-slate-700">Kehadiran Tim Hari Ini</h3>
            </div>
            <div class="divide-y divide-slate-100">
                <div v-for="m in stats.tim_hari_ini" :key="m.id" class="flex items-center justify-between px-5 py-3">
                    <div>
                        <div class="text-sm font-medium text-slate-700">{{ m.name }}</div>
                        <div class="text-xs text-slate-500">{{ m.jabatan }}</div>
                    </div>
                    <span class="badge" :class="m.status === 'hadir' ? 'badge-green' : m.status === 'alpha' ? 'badge-red' : 'badge-amber'">{{ m.status || 'Belum' }}</span>
                </div>
            </div>
        </div>
    </template>
</template>
