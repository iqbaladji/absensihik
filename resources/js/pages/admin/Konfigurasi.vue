<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastOk, toastErr } from '../../toast';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Modal from '../../components/Modal.vue';

const rows = ref([]);
const loading = ref(false);
const showForm = ref(false);
const editing = ref(null);
const form = ref({ kunci: '', nilai: '', deskripsi: '' });
const saving = ref(false);

const columns = [
    { key: 'kunci', label: 'Kunci' },
    { key: 'nilai', label: 'Nilai' },
    { key: 'deskripsi', label: 'Deskripsi' },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/admin/konfigurasi?per_page=200');
        rows.value = data.data || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}
onMounted(load);

function openCreate() {
    editing.value = null;
    form.value = { kunci: '', nilai: '', deskripsi: '' };
    showForm.value = true;
}

function openEdit(row) {
    editing.value = row;
    form.value = { kunci: row.kunci, nilai: row.nilai, deskripsi: row.deskripsi || '' };
    showForm.value = true;
}

async function save() {
    saving.value = true;
    try {
        if (editing.value) {
            await api.put(`/admin/konfigurasi/${editing.value.id}`, form.value);
            toastOk('Konfigurasi diperbarui.');
        } else {
            await api.post('/admin/konfigurasi', form.value);
            toastOk('Konfigurasi dibuat.');
        }
        showForm.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}
</script>

<template>
    <PageHeader title="Konfigurasi" subtitle="Pengaturan sistem">
        <template #actions><button class="btn-primary" @click="openCreate">+ Tambah</button></template>
    </PageHeader>
    <DataTable :columns="columns" :rows="rows" :loading="loading">
        <template #actions="{ row }"><button class="btn-ghost btn-sm" @click="openEdit(row)">Edit</button></template>
    </DataTable>

    <Modal v-if="showForm" :title="editing ? 'Edit Konfigurasi' : 'Tambah Konfigurasi'" @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div><label class="label">Kunci</label><input v-model="form.kunci" type="text" class="input" required :disabled="!!editing" /></div>
            <div><label class="label">Nilai</label><input v-model="form.nilai" type="text" class="input" required /></div>
            <div><label class="label">Deskripsi</label><input v-model="form.deskripsi" type="text" class="input" /></div>
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
        </template>
    </Modal>
</template>
