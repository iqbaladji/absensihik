<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastOk, toastErr } from '../../toast';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Modal from '../../components/Modal.vue';
import FileUpload from '../../components/FileUpload.vue';

const periodes = ref([]);
const loading = ref(false);
const showImport = ref(false);
const importFile = ref(null);
const importing = ref(false);

const columns = [
    { key: 'nama', label: 'Nama Periode' },
    { key: 'bulan', label: 'Bulan' },
    { key: 'tahun', label: 'Tahun' },
    { key: 'jumlah_pegawai', label: 'Pegawai' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/payslip-admin/periode');
        periodes.value = data.data || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}
onMounted(load);

async function doImport() {
    if (!importFile.value) return toastErr('Pilih file CSV/JSON terlebih dahulu.');
    importing.value = true;
    try {
        const fd = new FormData();
        fd.append('file', importFile.value);
        await api.post('/payslip-admin/import', fd);
        toastOk('Import berhasil.');
        showImport.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { importing.value = false; }
}

async function validate(periode) {
    try {
        await api.post(`/payslip-admin/validate/${periode.id}`);
        toastOk('Periode divalidasi.');
        load();
    } catch (e) { toastErr(errMsg(e)); }
}

async function publish(periode) {
    try {
        await api.post(`/payslip-admin/publish/${periode.id}`);
        toastOk('Payslip dipublikasikan ke pegawai.');
        load();
    } catch (e) { toastErr(errMsg(e)); }
}

async function retract(periode) {
    if (!confirm('Tarik kembali payslip periode ini?')) return;
    try {
        await api.post(`/payslip-admin/retract/${periode.id}`);
        toastOk('Payslip ditarik.');
        load();
    } catch (e) { toastErr(errMsg(e)); }
}
</script>

<template>
    <PageHeader title="Kelola Payslip" subtitle="Import dan distribusi slip gaji">
        <template #actions>
            <button class="btn-primary" @click="showImport = true">Import Data</button>
        </template>
    </PageHeader>
    <DataTable :columns="columns" :rows="periodes" :loading="loading">
        <template #actions="{ row }">
            <button v-if="row.status === 'draft'" class="btn-ghost btn-sm text-blue-600" @click="validate(row)">Validasi</button>
            <button v-if="row.status === 'validated'" class="btn-ghost btn-sm text-emerald-600" @click="publish(row)">Publish</button>
            <button v-if="row.status === 'published'" class="btn-ghost btn-sm text-amber-600" @click="retract(row)">Tarik</button>
        </template>
    </DataTable>

    <Modal v-if="showImport" title="Import Data Payslip" @close="showImport = false">
        <div class="space-y-4">
            <p class="text-sm text-slate-500">Upload file CSV atau JSON berisi data gaji pegawai.</p>
            <FileUpload label="Pilih File" accept=".csv,.json" @selected="(f) => importFile = f" />
        </div>
        <template #footer>
            <button class="btn-ghost" @click="showImport = false">Batal</button>
            <button class="btn-primary" :disabled="importing" @click="doImport">{{ importing ? 'Mengimport...' : 'Import' }}</button>
        </template>
    </Modal>
</template>
