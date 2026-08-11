<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastOk, toastErr } from '../../toast';
import { tanggal } from '../../util';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';
import Modal from '../../components/Modal.vue';
import SearchFilter from '../../components/SearchFilter.vue';

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const search = ref('');

const showForm = ref(false);
const editing = ref(null);
const form = ref({ id_user: '', id_kantor: '', id_unit_kerja: '', id_jabatan: '', tanggal_mulai: '', is_aktif: true });
const saving = ref(false);
const users = ref([]);
const kantors = ref([]);
const units = ref([]);
const jabatans = ref([]);

const columns = [
    { key: 'user.name', label: 'Nama' },
    { key: 'kantor.nama', label: 'Kantor' },
    { key: 'unit_kerja.nama', label: 'Unit' },
    { key: 'jabatan.nama', label: 'Jabatan' },
    { key: 'tanggal_mulai', label: 'Mulai', format: (v) => tanggal(v) },
    { key: 'is_aktif', label: 'Aktif', format: (v) => v ? 'Ya' : 'Tidak' },
];

async function load() {
    loading.value = true;
    try {
        const params = { page: page.value };
        if (search.value) params.q = search.value;
        const { data } = await api.get('/organisasi/penempatan', { params });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}

async function loadRefs() {
    const [u, k, un, j] = await Promise.all([
        api.get('/admin/users?per_page=200').catch(() => ({ data: { data: [] } })),
        api.get('/master/kantor?per_page=200').catch(() => ({ data: { data: [] } })),
        api.get('/master/unit-kerja?per_page=200').catch(() => ({ data: { data: [] } })),
        api.get('/master/jabatan?per_page=200').catch(() => ({ data: { data: [] } })),
    ]);
    users.value = u.data.data || u.data;
    kantors.value = k.data.data || k.data;
    units.value = un.data.data || un.data;
    jabatans.value = j.data.data || j.data;
}

onMounted(() => { load(); loadRefs(); });

function openCreate() {
    editing.value = null;
    form.value = { id_user: '', id_kantor: '', id_unit_kerja: '', id_jabatan: '', tanggal_mulai: '', is_aktif: true };
    showForm.value = true;
}

function openEdit(row) {
    editing.value = row;
    form.value = { id_user: row.id_user, id_kantor: row.id_kantor, id_unit_kerja: row.id_unit_kerja, id_jabatan: row.id_jabatan, tanggal_mulai: row.tanggal_mulai, is_aktif: row.is_aktif };
    showForm.value = true;
}

async function save() {
    saving.value = true;
    try {
        if (editing.value) {
            await api.put(`/organisasi/penempatan/${editing.value.id}`, form.value);
            toastOk('Data diperbarui.');
        } else {
            await api.post('/organisasi/penempatan', form.value);
            toastOk('Data tersimpan.');
        }
        showForm.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}
</script>

<template>
    <PageHeader title="Penempatan" subtitle="Kelola penempatan pegawai">
        <template #actions><button class="btn-primary" @click="openCreate">+ Tambah</button></template>
    </PageHeader>
    <SearchFilter placeholder="Cari nama pegawai..." @search="(q) => { search = q; page = 1; load(); }" />
    <DataTable :columns="columns" :rows="rows" :loading="loading">
        <template #actions="{ row }"><button class="btn-ghost btn-sm" @click="openEdit(row)">Edit</button></template>
    </DataTable>
    <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />

    <Modal v-if="showForm" :title="editing ? 'Edit Penempatan' : 'Tambah Penempatan'" @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div><label class="label">Pegawai</label><select v-model="form.id_user" class="input" required><option value="">-- Pilih --</option><option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.nip }})</option></select></div>
            <div><label class="label">Kantor</label><select v-model="form.id_kantor" class="input" required><option value="">-- Pilih --</option><option v-for="k in kantors" :key="k.id" :value="k.id">{{ k.nama }}</option></select></div>
            <div><label class="label">Unit Kerja</label><select v-model="form.id_unit_kerja" class="input"><option value="">-- Pilih --</option><option v-for="u in units" :key="u.id" :value="u.id">{{ u.nama }}</option></select></div>
            <div><label class="label">Jabatan</label><select v-model="form.id_jabatan" class="input"><option value="">-- Pilih --</option><option v-for="j in jabatans" :key="j.id" :value="j.id">{{ j.nama }}</option></select></div>
            <div><label class="label">Tanggal Mulai</label><input v-model="form.tanggal_mulai" type="date" class="input" required /></div>
            <label class="flex items-center gap-2"><input type="checkbox" v-model="form.is_aktif" class="h-4 w-4 rounded border-slate-300" /><span class="text-sm">Aktif</span></label>
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
        </template>
    </Modal>
</template>
