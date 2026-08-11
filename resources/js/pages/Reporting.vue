<script setup>
import { ref } from 'vue';
import api, { errMsg } from '../api';
import { toastErr } from '../toast';
import PageHeader from '../components/PageHeader.vue';
import DataTable from '../components/DataTable.vue';
import Pagination from '../components/Pagination.vue';

const reportType = ref('kehadiran');
const filters = ref({ bulan: new Date().getMonth() + 1, tahun: new Date().getFullYear(), id_kantor: '' });
const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const columns = ref([]);

const reportTypes = [
    { value: 'kehadiran', label: 'Laporan Kehadiran' },
    { value: 'cuti', label: 'Laporan Cuti' },
    { value: 'lembur', label: 'Laporan Lembur' },
    { value: 'izin', label: 'Laporan Izin' },
    { value: 'dinas_luar', label: 'Laporan Dinas Luar' },
];

const columnSets = {
    kehadiran: [
        { key: 'name', label: 'Nama' },
        { key: 'nip', label: 'NIP' },
        { key: 'hadir', label: 'Hadir' },
        { key: 'terlambat', label: 'Terlambat' },
        { key: 'alpha', label: 'Alpha' },
        { key: 'wfh', label: 'WFH' },
        { key: 'dinas', label: 'Dinas' },
    ],
    cuti: [
        { key: 'name', label: 'Nama' },
        { key: 'nip', label: 'NIP' },
        { key: 'jatah', label: 'Jatah' },
        { key: 'terpakai', label: 'Terpakai' },
        { key: 'sisa', label: 'Sisa' },
    ],
    lembur: [
        { key: 'name', label: 'Nama' },
        { key: 'nip', label: 'NIP' },
        { key: 'total_jam', label: 'Total Jam' },
        { key: 'jumlah_pengajuan', label: 'Jumlah' },
    ],
    izin: [
        { key: 'name', label: 'Nama' },
        { key: 'nip', label: 'NIP' },
        { key: 'jenis', label: 'Jenis' },
        { key: 'total_hari', label: 'Total Hari' },
    ],
    dinas_luar: [
        { key: 'name', label: 'Nama' },
        { key: 'nip', label: 'NIP' },
        { key: 'total_hari', label: 'Total Hari' },
        { key: 'jumlah', label: 'Jumlah' },
    ],
};

async function generate() {
    loading.value = true;
    columns.value = columnSets[reportType.value] || [];
    try {
        const { data } = await api.get(`/laporan/${reportType.value}`, { params: { page: page.value, ...filters.value } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}
</script>

<template>
    <PageHeader title="Laporan" subtitle="Generate laporan kehadiran dan workforce" />

    <div class="card mb-6 p-5">
        <div class="grid gap-4 sm:grid-cols-4">
            <div>
                <label class="label">Jenis Laporan</label>
                <select v-model="reportType" class="input">
                    <option v-for="r in reportTypes" :key="r.value" :value="r.value">{{ r.label }}</option>
                </select>
            </div>
            <div>
                <label class="label">Bulan</label>
                <select v-model="filters.bulan" class="input">
                    <option v-for="m in 12" :key="m" :value="m">{{ m }}</option>
                </select>
            </div>
            <div>
                <label class="label">Tahun</label>
                <input v-model.number="filters.tahun" type="number" class="input" min="2020" max="2030" />
            </div>
            <div class="flex items-end">
                <button class="btn-primary w-full" @click="page = 1; generate()">Generate</button>
            </div>
        </div>
    </div>

    <DataTable :columns="columns" :rows="rows" :loading="loading" />
    <Pagination :meta="meta" @go="(p) => { page = p; generate(); }" />
</template>
