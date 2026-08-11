<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastOk, toastErr } from '../../toast';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';
import Modal from '../../components/Modal.vue';

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const showForm = ref(false);
const editing = ref(null);
const form = ref({ modul: '', id_jabatan_pengaju: '', id_jabatan_approver: '', urutan: 1 });
const saving = ref(false);
const jabatans = ref([]);

const modules = ['izin', 'cuti_tahunan', 'block_leave', 'cuti_melahirkan', 'cuti_besar', 'lembur', 'dinas_luar', 'wfh', 'wfa'];

const columns = [
    { key: 'modul', label: 'Modul' },
    { key: 'jabatan_pengaju.nama', label: 'Jabatan Pengaju' },
    { key: 'jabatan_approver.nama', label: 'Approver' },
    { key: 'urutan', label: 'Urutan' },
    { key: 'tipe', label: 'Tipe', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/organisasi/approval-matrix', { params: { page: page.value } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}

async function loadJabatans() {
    try {
        const { data } = await api.get('/master/jabatan?per_page=200');
        jabatans.value = data.data || data;
    } catch (_) {}
}

onMounted(() => { load(); loadJabatans(); });

function openCreate() {
    editing.value = null;
    form.value = { modul: '', id_jabatan_pengaju: '', id_jabatan_approver: '', urutan: 1, tipe: 'atasan_langsung' };
    showForm.value = true;
}

function openEdit(row) {
    editing.value = row;
    form.value = { modul: row.modul, id_jabatan_pengaju: row.id_jabatan_pengaju, id_jabatan_approver: row.id_jabatan_approver, urutan: row.urutan, tipe: row.tipe };
    showForm.value = true;
}

async function save() {
    saving.value = true;
    try {
        if (editing.value) {
            await api.put(`/organisasi/approval-matrix/${editing.value.id}`, form.value);
            toastOk('Data diperbarui.');
        } else {
            await api.post('/organisasi/approval-matrix', form.value);
            toastOk('Data tersimpan.');
        }
        showForm.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}
</script>

<template>
    <PageHeader title="Approval Matrix" subtitle="Konfigurasi alur persetujuan">
        <template #actions><button class="btn-primary" @click="openCreate">+ Tambah</button></template>
    </PageHeader>
    <DataTable :columns="columns" :rows="rows" :loading="loading">
        <template #actions="{ row }"><button class="btn-ghost btn-sm" @click="openEdit(row)">Edit</button></template>
    </DataTable>
    <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />

    <Modal v-if="showForm" :title="editing ? 'Edit Approval Matrix' : 'Tambah Approval Matrix'" @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div><label class="label">Modul</label><select v-model="form.modul" class="input" required><option value="">-- Pilih --</option><option v-for="m in modules" :key="m" :value="m">{{ m }}</option></select></div>
            <div><label class="label">Jabatan Pengaju</label><select v-model="form.id_jabatan_pengaju" class="input" required><option value="">-- Pilih --</option><option v-for="j in jabatans" :key="j.id" :value="j.id">{{ j.nama }}</option></select></div>
            <div><label class="label">Jabatan Approver</label><select v-model="form.id_jabatan_approver" class="input" required><option value="">-- Pilih --</option><option v-for="j in jabatans" :key="j.id" :value="j.id">{{ j.nama }}</option></select></div>
            <div><label class="label">Urutan</label><input v-model.number="form.urutan" type="number" class="input" min="1" required /></div>
            <div><label class="label">Tipe</label><select v-model="form.tipe" class="input"><option value="atasan_langsung">Atasan Langsung</option><option value="jabatan_tertentu">Jabatan Tertentu</option></select></div>
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
        </template>
    </Modal>
</template>
