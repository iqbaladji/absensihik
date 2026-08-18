<script setup>
import { ref, onMounted, computed } from 'vue';
import api, { errMsg } from '../../api';
import { toastErr } from '../../toast';
import { tanggal, jam } from '../../util';
import { useAuth } from '../../stores/auth';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';
import SearchFilter from '../../components/SearchFilter.vue';
import MobileSubHeader from '../../components/MobileSubHeader.vue';

const auth = useAuth();
const isPegawaiMobile = computed(() => auth.roleSlug === 'pegawai');

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const filters = ref({ bulan: '', status: '' });

function statusPill(s) {
    const v = String(s || '').toLowerCase();
    if (v.includes('tepat')) return { text: 'Tepat Waktu', cls: 'bg-hijau-600 text-white' };
    if (v.includes('lambat')) return { text: 'Terlambat', cls: 'bg-amber-400 text-white' };
    if (v.includes('alpha')) return { text: 'Alpha', cls: 'bg-rose-500 text-white' };
    if (v.includes('awal')) return { text: 'Pulang Awal', cls: 'bg-amber-400 text-white' };
    return { text: s || '-', cls: 'bg-slate-400 text-white' };
}

const columns = [
    { key: 'tanggal', label: 'Tanggal', format: (v) => tanggal(v) },
    { key: 'waktu_masuk', label: 'Masuk', format: (v) => jam(v) },
    { key: 'waktu_pulang', label: 'Pulang', format: (v) => jam(v) },
    { key: 'tipe_kehadiran', label: 'Tipe', badge: true },
    { key: 'status_kehadiran', label: 'Status', badge: true },
    { key: 'jarak_masuk_meter', label: 'Jarak (m)' },
];

async function load() {
    loading.value = true;
    try {
        const params = { page: page.value, per_page: 25, ...filters.value };
        const { data } = await api.get('/presensi/riwayat', { params });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        loading.value = false;
    }
}

onMounted(load);

function onFilter(f) {
    filters.value = { ...filters.value, ...f };
    page.value = 1;
    load();
}
</script>

<template>
    <div v-if="isPegawaiMobile" class="lg:hidden">
        <MobileSubHeader title="Riwayat Presensi" to="/" />
        <div class="mt-3 flex items-center justify-between px-1">
            <h2 class="text-base font-semibold text-slate-800">Kehadiran Saya</h2>
            <button class="rounded-lg p-1.5 text-hijau-600" aria-label="Filter">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M7 12h10M10 18h4"/></svg>
            </button>
        </div>
        <div v-if="loading" class="py-10 text-center text-sm text-slate-400">Memuat...</div>
        <div v-else-if="!rows.length" class="py-10 text-center text-sm text-slate-400">Belum ada data.</div>
        <div v-else class="mt-2 space-y-3 pb-24">
            <div v-for="row in rows" :key="row.id" class="rounded-xl bg-slate-100 p-3 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="font-semibold text-hijau-700">{{ tanggal(row.tanggal) }}</div>
                    <span class="rounded-md px-3 py-1 text-xs font-semibold" :class="statusPill(row.status_kehadiran).cls">{{ statusPill(row.status_kehadiran).text }}</span>
                </div>
                <div class="mt-2 grid grid-cols-3 gap-2 border-t border-slate-200 pt-2 text-center text-xs">
                    <div><div class="text-slate-500">Masuk</div><div class="font-semibold text-slate-700">{{ row.waktu_masuk ? jam(row.waktu_masuk) : '-' }}</div></div>
                    <div><div class="text-slate-500">Pulang</div><div class="font-semibold text-slate-700">{{ row.waktu_pulang ? jam(row.waktu_pulang) : '-' }}</div></div>
                    <div><div class="text-slate-500">Tipe</div><div class="font-semibold text-slate-700">{{ row.tipe_kehadiran || '-' }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <template v-else>
        <PageHeader title="Riwayat Presensi" subtitle="Histori kehadiran Anda" />

        <SearchFilter
            placeholder="Filter..."
            :filters="[
                { key: 'status', label: 'Status', options: ['tepat_waktu', 'terlambat', 'pulang_awal', 'alpha'] },
            ]"
            @search="(q) => { filters.bulan = q; page = 1; load(); }"
            @filter="onFilter"
        />

        <DataTable :columns="columns" :rows="rows" :loading="loading" />
        <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />
    </template>
</template>
