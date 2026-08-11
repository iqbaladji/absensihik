<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastOk, toastErr } from '../../toast';
import { tanggal } from '../../util';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';
import Modal from '../../components/Modal.vue';

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const showForm = ref(false);
const form = ref({ id_delegator: '', id_penerima: '', tanggal_mulai: '', tanggal_selesai: '', alasan: '' });
const saving = ref(false);
const users = ref([]);

const columns = [
    { key: 'delegator.name', label: 'Delegator' },
    { key: 'penerima.name', label: 'Penerima' },
    { key: 'tanggal_mulai', label: 'Mulai', format: (v) => tanggal(v) },
    { key: 'tanggal_selesai', label: 'Selesai', format: (v) => tanggal(v) },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/organisasi/delegasi', { params: { page: page.value } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}

async function loadUsers() {
    try {
        const { data } = await api.get('/admin/users?per_page=200');
        users.value = data.data || data;
    } catch (_) {}
}

onMounted(() => { load(); loadUsers(); });

async function save() {
    saving.value = true;
    try {
        await api.post('/organisasi/delegasi', form.value);
        toastOk('Delegasi dibuat.');
        showForm.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}
</script>

<template>
    <PageHeader title="Delegasi" subtitle="Kelola delegasi wewenang approval">
        <template #actions><button class="btn-primary" @click="form = { id_delegator: '', id_penerima: '', tanggal_mulai: '', tanggal_selesai: '', alasan: '' }; showForm = true">+ Tambah</button></template>
    </PageHeader>
    <DataTable :columns="columns" :rows="rows" :loading="loading" />
    <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />

    <Modal v-if="showForm" title="Buat Delegasi" @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div><label class="label">Delegator</label><select v-model="form.id_delegator" class="input" required><option value="">-- Pilih --</option><option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option></select></div>
            <div><label class="label">Penerima</label><select v-model="form.id_penerima" class="input" required><option value="">-- Pilih --</option><option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option></select></div>
            <div><label class="label">Tanggal Mulai</label><input v-model="form.tanggal_mulai" type="date" class="input" required /></div>
            <div><label class="label">Tanggal Selesai</label><input v-model="form.tanggal_selesai" type="date" class="input" required /></div>
            <div><label class="label">Alasan</label><textarea v-model="form.alasan" class="input" rows="2" /></div>
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
        </template>
    </Modal>
</template>
