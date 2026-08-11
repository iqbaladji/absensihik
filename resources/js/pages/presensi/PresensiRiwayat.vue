<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastErr } from '../../toast';
import { tanggal, jam } from '../../util';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';
import SearchFilter from '../../components/SearchFilter.vue';

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const filters = ref({ bulan: '', status: '' });

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
