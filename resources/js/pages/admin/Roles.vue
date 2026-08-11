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
const form = ref({ slug: '', nama: '', deskripsi: '', hak_akses: {} });
const saving = ref(false);

const modules = [
    'dashboard', 'dashboard_supervisor', 'dashboard_hr',
    'presensi', 'presensi_tim', 'verifikasi',
    'dinas_luar', 'wfh', 'wfa',
    'izin', 'cuti_tahunan', 'block_leave', 'cuti_melahirkan', 'cuti_besar',
    'lembur', 'pengumuman', 'payslip', 'payslip_admin',
    'organisasi', 'master_data', 'reporting',
    'administrasi_sistem', 'konfigurasi', 'audit_trail',
];
const abilities = ['C', 'R', 'U', 'D', 'A', 'P'];

const columns = [
    { key: 'slug', label: 'Slug' },
    { key: 'nama', label: 'Nama' },
    { key: 'deskripsi', label: 'Deskripsi' },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/admin/roles?per_page=200');
        rows.value = data.data || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}
onMounted(load);

function openCreate() {
    editing.value = null;
    const ha = {};
    modules.forEach((m) => { ha[m] = []; });
    form.value = { slug: '', nama: '', deskripsi: '', hak_akses: ha };
    showForm.value = true;
}

function openEdit(row) {
    editing.value = row;
    const ha = {};
    modules.forEach((m) => { ha[m] = row.hak_akses?.[m] || []; });
    form.value = { slug: row.slug, nama: row.nama, deskripsi: row.deskripsi || '', hak_akses: ha };
    showForm.value = true;
}

function toggleAbility(modul, ability) {
    const arr = form.value.hak_akses[modul];
    const idx = arr.indexOf(ability);
    if (idx >= 0) arr.splice(idx, 1);
    else arr.push(ability);
}

async function save() {
    saving.value = true;
    try {
        const payload = { ...form.value };
        const cleaned = {};
        Object.entries(payload.hak_akses).forEach(([k, v]) => { if (v.length) cleaned[k] = v; });
        payload.hak_akses = cleaned;

        if (editing.value) {
            await api.put(`/admin/roles/${editing.value.id}`, payload);
            toastOk('Role diperbarui.');
        } else {
            await api.post('/admin/roles', payload);
            toastOk('Role dibuat.');
        }
        showForm.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}
</script>

<template>
    <PageHeader title="Peran & Hak Akses" subtitle="Kelola role dan permission">
        <template #actions><button class="btn-primary" @click="openCreate">+ Tambah</button></template>
    </PageHeader>
    <DataTable :columns="columns" :rows="rows" :loading="loading">
        <template #actions="{ row }"><button class="btn-ghost btn-sm" @click="openEdit(row)">Edit</button></template>
    </DataTable>

    <Modal v-if="showForm" :title="editing ? 'Edit Role' : 'Tambah Role'" wide @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label class="label">Slug</label><input v-model="form.slug" type="text" class="input" required :disabled="editing?.is_system" /></div>
                <div><label class="label">Nama</label><input v-model="form.nama" type="text" class="input" required /></div>
                <div><label class="label">Deskripsi</label><input v-model="form.deskripsi" type="text" class="input" /></div>
            </div>
            <div>
                <label class="label mb-2">Hak Akses</label>
                <div class="max-h-96 overflow-y-auto rounded-lg border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-slate-600">Modul</th>
                                <th v-for="a in abilities" :key="a" class="px-2 py-2 text-center font-medium text-slate-600">{{ a }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="m in modules" :key="m">
                                <td class="px-3 py-1.5 text-slate-700">{{ m }}</td>
                                <td v-for="a in abilities" :key="a" class="px-2 py-1.5 text-center">
                                    <input type="checkbox" :checked="(form.hak_akses[m] || []).includes(a)" @change="toggleAbility(m, a)" class="h-4 w-4 rounded border-slate-300" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
        </template>
    </Modal>
</template>
