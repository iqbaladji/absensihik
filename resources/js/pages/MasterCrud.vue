<script setup>
import { ref, watch, computed } from 'vue';
import { useRoute } from 'vue-router';
import api, { errMsg } from '../api';
import { masterConfig } from '../masters';
import { toastOk, toastErr } from '../toast';
import PageHeader from '../components/PageHeader.vue';
import DataTable from '../components/DataTable.vue';
import Pagination from '../components/Pagination.vue';
import SearchFilter from '../components/SearchFilter.vue';
import Modal from '../components/Modal.vue';

const route = useRoute();
const key = computed(() => route.params.key);
const cfg = computed(() => masterConfig[key.value] || null);

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const search = ref('');

const showModal = ref(false);
const editing = ref(null);
const form = ref({});
const saving = ref(false);
const selectOptions = ref({});

async function load() {
    if (!cfg.value) return;
    loading.value = true;
    try {
        const params = { page: page.value, per_page: 25 };
        if (search.value) params.q = search.value;
        const { data } = await api.get(cfg.value.endpoint, { params });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        loading.value = false;
    }
}

async function loadSelectOptions() {
    if (!cfg.value) return;
    for (const f of cfg.value.fields) {
        if (f.endpoint) {
            try {
                const { data } = await api.get(f.endpoint);
                selectOptions.value[f.key] = (data.data || data).map((item) => ({
                    value: item[f.optionValue || 'id'],
                    label: item[f.optionLabel || 'nama'],
                }));
            } catch (_) {}
        }
    }
}

function openCreate() {
    editing.value = null;
    form.value = {};
    cfg.value.fields.forEach((f) => {
        form.value[f.key] = f.type === 'checkbox' ? false : '';
    });
    showModal.value = true;
}

function openEdit(row) {
    editing.value = row;
    form.value = {};
    cfg.value.fields.forEach((f) => {
        form.value[f.key] = row[f.key] ?? (f.type === 'checkbox' ? false : '');
    });
    showModal.value = true;
}

async function save() {
    saving.value = true;
    try {
        if (editing.value) {
            await api.put(`${cfg.value.endpoint}/${editing.value.id}`, form.value);
            toastOk('Data diperbarui.');
        } else {
            await api.post(cfg.value.endpoint, form.value);
            toastOk('Data tersimpan.');
        }
        showModal.value = false;
        load();
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        saving.value = false;
    }
}

async function deactivate(row) {
    if (!confirm('Nonaktifkan data ini?')) return;
    try {
        await api.delete(`${cfg.value.endpoint}/${row.id}`);
        toastOk('Data dinonaktifkan.');
        load();
    } catch (e) {
        toastErr(errMsg(e));
    }
}

watch(key, () => {
    page.value = 1;
    search.value = '';
    load();
    loadSelectOptions();
}, { immediate: true });

function onSearch(q) {
    search.value = q;
    page.value = 1;
    load();
}
</script>

<template>
    <div v-if="cfg">
        <PageHeader :title="cfg.title">
            <template #actions>
                <button class="btn-primary" @click="openCreate">+ Tambah</button>
            </template>
        </PageHeader>

        <SearchFilter :placeholder="`Cari ${cfg.title.toLowerCase()}...`" @search="onSearch" />

        <DataTable :columns="cfg.columns" :rows="rows" :loading="loading">
            <template #actions="{ row }">
                <button class="btn-ghost btn-sm" @click="openEdit(row)">Edit</button>
                <button v-if="cfg.hasStatus !== false && row.status === 'aktif'" class="btn-ghost btn-sm text-rose-600" @click="deactivate(row)">Nonaktifkan</button>
            </template>
        </DataTable>

        <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />

        <Modal v-if="showModal" :title="editing ? `Edit ${cfg.title}` : `Tambah ${cfg.title}`" @close="showModal = false">
            <form @submit.prevent="save" class="space-y-4">
                <div v-for="f in cfg.fields" :key="f.key">
                    <label class="label">{{ f.label }}</label>
                    <template v-if="f.type === 'select' && f.endpoint">
                        <select v-model="form[f.key]" class="input" :required="f.required">
                            <option value="">-- Pilih --</option>
                            <option v-for="opt in selectOptions[f.key]" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </template>
                    <template v-else-if="f.type === 'select'">
                        <select v-model="form[f.key]" class="input" :required="f.required">
                            <option value="">-- Pilih --</option>
                            <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </template>
                    <template v-else-if="f.type === 'textarea'">
                        <textarea v-model="form[f.key]" class="input" rows="3" :required="f.required" />
                    </template>
                    <template v-else-if="f.type === 'checkbox'">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" v-model="form[f.key]" class="h-4 w-4 rounded border-slate-300" />
                            <span class="text-sm text-slate-600">{{ f.label }}</span>
                        </label>
                    </template>
                    <template v-else>
                        <input v-model="form[f.key]" :type="f.type || 'text'" class="input" :required="f.required" :step="f.step" />
                    </template>
                </div>
            </form>
            <template #footer>
                <button class="btn-ghost" @click="showModal = false">Batal</button>
                <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
            </template>
        </Modal>
    </div>
    <div v-else class="py-12 text-center text-slate-500">Konfigurasi master tidak ditemukan.</div>
</template>
