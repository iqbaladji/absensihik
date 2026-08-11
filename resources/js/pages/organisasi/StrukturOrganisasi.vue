<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastOk, toastErr } from '../../toast';
import PageHeader from '../../components/PageHeader.vue';
import Modal from '../../components/Modal.vue';

const entitas = ref([]);
const direktorat = ref([]);
const divisi = ref([]);
const departemen = ref([]);
const loading = ref(true);
const activeTab = ref('entitas');

const showForm = ref(false);
const formType = ref('');
const editing = ref(null);
const form = ref({});
const saving = ref(false);

const tabs = [
    { key: 'entitas', label: 'Entitas' },
    { key: 'direktorat', label: 'Direktorat' },
    { key: 'divisi', label: 'Divisi' },
    { key: 'departemen', label: 'Departemen' },
];

async function loadAll() {
    loading.value = true;
    try {
        const [e, dir, div, dep] = await Promise.all([
            api.get('/organisasi/entitas?per_page=200'),
            api.get('/organisasi/direktorat?per_page=200'),
            api.get('/organisasi/divisi?per_page=200'),
            api.get('/organisasi/departemen?per_page=200'),
        ]);
        entitas.value = e.data.data || e.data;
        direktorat.value = dir.data.data || dir.data;
        divisi.value = div.data.data || div.data;
        departemen.value = dep.data.data || dep.data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}
onMounted(loadAll);

function currentList() {
    return { entitas, direktorat, divisi, departemen }[activeTab.value]?.value || [];
}

function openCreate(type) {
    formType.value = type;
    editing.value = null;
    form.value = { kode: '', nama: '' };
    showForm.value = true;
}

function openEdit(type, row) {
    formType.value = type;
    editing.value = row;
    form.value = { ...row };
    showForm.value = true;
}

async function save() {
    saving.value = true;
    try {
        const ep = `/organisasi/${formType.value}`;
        if (editing.value) {
            await api.put(`${ep}/${editing.value.id}`, form.value);
            toastOk('Data diperbarui.');
        } else {
            await api.post(ep, form.value);
            toastOk('Data tersimpan.');
        }
        showForm.value = false;
        loadAll();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}
</script>

<template>
    <PageHeader title="Struktur Organisasi" subtitle="Kelola hierarki organisasi perusahaan" />

    <div class="mb-4 flex gap-1 rounded-lg bg-slate-100 p-1">
        <button v-for="t in tabs" :key="t.key" class="flex-1 rounded-md px-3 py-2 text-sm font-medium transition" :class="activeTab === t.key ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'" @click="activeTab = t.key">{{ t.label }}</button>
    </div>

    <div class="mb-4 flex justify-end">
        <button class="btn-primary" @click="openCreate(activeTab)">+ Tambah {{ tabs.find((t) => t.key === activeTab)?.label }}</button>
    </div>

    <div v-if="loading" class="py-12 text-center text-slate-400">Memuat...</div>
    <div v-else class="space-y-2">
        <div v-for="item in currentList()" :key="item.id" class="card flex items-center justify-between p-4">
            <div>
                <div class="font-medium text-slate-700">{{ item.nama }}</div>
                <div class="text-xs text-slate-500">{{ item.kode }}</div>
            </div>
            <button class="btn-ghost btn-sm" @click="openEdit(activeTab, item)">Edit</button>
        </div>
        <div v-if="!currentList().length" class="py-8 text-center text-sm text-slate-400">Tidak ada data.</div>
    </div>

    <Modal v-if="showForm" :title="editing ? 'Edit' : 'Tambah'" @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div><label class="label">Kode</label><input v-model="form.kode" type="text" class="input" required /></div>
            <div><label class="label">Nama</label><input v-model="form.nama" type="text" class="input" required /></div>
            <div v-if="formType === 'direktorat'">
                <label class="label">Entitas</label>
                <select v-model="form.id_entitas" class="input" required>
                    <option value="">-- Pilih --</option>
                    <option v-for="e in entitas" :key="e.id" :value="e.id">{{ e.nama }}</option>
                </select>
            </div>
            <div v-if="formType === 'divisi'">
                <label class="label">Direktorat</label>
                <select v-model="form.id_direktorat" class="input" required>
                    <option value="">-- Pilih --</option>
                    <option v-for="d in direktorat" :key="d.id" :value="d.id">{{ d.nama }}</option>
                </select>
            </div>
            <div v-if="formType === 'departemen'">
                <label class="label">Divisi</label>
                <select v-model="form.id_divisi" class="input" required>
                    <option value="">-- Pilih --</option>
                    <option v-for="d in divisi" :key="d.id" :value="d.id">{{ d.nama }}</option>
                </select>
            </div>
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
        </template>
    </Modal>
</template>
