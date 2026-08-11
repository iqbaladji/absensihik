<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastErr } from '../../toast';
import { tanggalJam } from '../../util';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';
import SearchFilter from '../../components/SearchFilter.vue';

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const filters = ref({});

const columns = [
    { key: 'created_at', label: 'Waktu', format: (v) => tanggalJam(v) },
    { key: 'user.name', label: 'Pengguna' },
    { key: 'modul', label: 'Modul' },
    { key: 'aksi', label: 'Aksi', badge: true },
    { key: 'tabel', label: 'Tabel' },
    { key: 'id_referensi', label: 'ID Ref' },
];

async function load() {
    loading.value = true;
    try {
        const params = { page: page.value, ...filters.value };
        const { data } = await api.get('/admin/audit-trail', { params });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}
onMounted(load);

function onFilter(f) {
    filters.value = { ...filters.value, ...f };
    page.value = 1;
    load();
}
</script>

<template>
    <PageHeader title="Jejak Audit" subtitle="Log aktivitas sistem (read-only)" />

    <SearchFilter
        placeholder="Cari modul/tabel..."
        :filters="[
            { key: 'aksi', label: 'Aksi', options: ['create', 'update', 'delete', 'nonaktif', 'login', 'logout'] },
        ]"
        @search="(q) => { filters.q = q; page = 1; load(); }"
        @filter="onFilter"
    />

    <DataTable :columns="columns" :rows="rows" :loading="loading" />
    <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />
</template>
