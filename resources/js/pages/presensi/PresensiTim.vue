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
const search = ref('');
const tanggalFilter = ref(new Date().toISOString().slice(0, 10));

const columns = [
    { key: 'user.name', label: 'Nama' },
    { key: 'user.nip', label: 'NIP' },
    { key: 'waktu_masuk', label: 'Masuk', format: (v) => jam(v) },
    { key: 'waktu_pulang', label: 'Pulang', format: (v) => jam(v) },
    { key: 'tipe_kehadiran', label: 'Tipe', badge: true },
    { key: 'status_kehadiran', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const params = { page: page.value, per_page: 25, tanggal: tanggalFilter.value };
        if (search.value) params.q = search.value;
        const { data } = await api.get('/presensi/tim', { params });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <PageHeader title="Kehadiran Tim" subtitle="Monitor kehadiran anggota tim Anda" />

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <input v-model="tanggalFilter" type="date" class="input w-auto" @change="load" />
        <SearchFilter placeholder="Cari nama..." @search="(q) => { search = q; page = 1; load(); }" />
    </div>

    <DataTable :columns="columns" :rows="rows" :loading="loading" />
    <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />
</template>
