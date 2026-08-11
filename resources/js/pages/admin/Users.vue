<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastOk, toastErr } from '../../toast';
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
const roles = ref([]);
const kantors = ref([]);

const showForm = ref(false);
const editing = ref(null);
const form = ref({ name: '', username: '', nip: '', email: '', password: '', id_role: '', id_kantor: '', status: 'aktif' });
const saving = ref(false);
const resetting = ref(false);

const columns = [
    { key: 'name', label: 'Nama' },
    { key: 'username', label: 'Username' },
    { key: 'nip', label: 'NIP' },
    { key: 'role.nama', label: 'Role' },
    { key: 'kantor.nama', label: 'Kantor' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const params = { page: page.value };
        if (search.value) params.q = search.value;
        const { data } = await api.get('/admin/users', { params });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}

async function loadRefs() {
    const [r, k] = await Promise.all([
        api.get('/admin/roles?per_page=200').catch(() => ({ data: { data: [] } })),
        api.get('/master/kantor?per_page=200').catch(() => ({ data: { data: [] } })),
    ]);
    roles.value = r.data.data || r.data;
    kantors.value = k.data.data || k.data;
}

onMounted(() => { load(); loadRefs(); });

function openCreate() {
    editing.value = null;
    form.value = { name: '', username: '', nip: '', email: '', password: '', id_role: '', id_kantor: '', status: 'aktif' };
    showForm.value = true;
}

function openEdit(row) {
    editing.value = row;
    form.value = { name: row.name, username: row.username, nip: row.nip, email: row.email || '', password: '', id_role: row.id_role, id_kantor: row.id_kantor || '', status: row.status };
    showForm.value = true;
}

async function save() {
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (!payload.password) delete payload.password;
        if (editing.value) {
            await api.put(`/admin/users/${editing.value.id}`, payload);
            toastOk('Pengguna diperbarui.');
        } else {
            await api.post('/admin/users', payload);
            toastOk('Pengguna dibuat.');
        }
        showForm.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}

async function resetPassword(row) {
    if (!confirm(`Reset password ${row.name}?`)) return;
    resetting.value = true;
    try {
        const { data } = await api.post(`/admin/users/${row.id}/reset-password`);
        toastOk(data.message || 'Password direset.');
    } catch (e) { toastErr(errMsg(e)); }
    finally { resetting.value = false; }
}
</script>

<template>
    <PageHeader title="Pengguna" subtitle="Kelola akun pengguna">
        <template #actions><button class="btn-primary" @click="openCreate">+ Tambah</button></template>
    </PageHeader>
    <SearchFilter placeholder="Cari nama/username/NIP..." @search="(q) => { search = q; page = 1; load(); }" />
    <DataTable :columns="columns" :rows="rows" :loading="loading">
        <template #actions="{ row }">
            <button class="btn-ghost btn-sm" @click="openEdit(row)">Edit</button>
            <button class="btn-ghost btn-sm text-amber-600" @click="resetPassword(row)">Reset PW</button>
        </template>
    </DataTable>
    <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />

    <Modal v-if="showForm" :title="editing ? 'Edit Pengguna' : 'Tambah Pengguna'" @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div><label class="label">Nama</label><input v-model="form.name" type="text" class="input" required /></div>
            <div><label class="label">Username</label><input v-model="form.username" type="text" class="input" required /></div>
            <div><label class="label">NIP</label><input v-model="form.nip" type="text" class="input" required /></div>
            <div><label class="label">Email</label><input v-model="form.email" type="email" class="input" /></div>
            <div><label class="label">Password {{ editing ? '(kosongkan jika tidak diubah)' : '' }}</label><input v-model="form.password" type="password" class="input" :required="!editing" /></div>
            <div><label class="label">Role</label><select v-model="form.id_role" class="input" required><option value="">-- Pilih --</option><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.nama }}</option></select></div>
            <div><label class="label">Kantor</label><select v-model="form.id_kantor" class="input"><option value="">-- Pilih --</option><option v-for="k in kantors" :key="k.id" :value="k.id">{{ k.nama }}</option></select></div>
            <div><label class="label">Status</label><select v-model="form.status" class="input"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select></div>
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
        </template>
    </Modal>
</template>
