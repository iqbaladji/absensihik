<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { useAuth } from '../../stores/auth';
import { toastOk, toastErr } from '../../toast';
import { tanggal } from '../../util';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';
import Modal from '../../components/Modal.vue';

const auth = useAuth();
const canCreate = auth.can('wfa', 'C');
const canApprove = auth.can('wfa', 'A');

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const showForm = ref(false);
const editing = ref(null);
const form = ref({ tanggal_mulai: '', tanggal_selesai: '', lokasi: '', alasan: '' });
const saving = ref(false);
const showApproval = ref(null);
const catatan = ref('');
const processing = ref(false);

const columns = [
    { key: 'user.name', label: 'Nama' },
    { key: 'tanggal_mulai', label: 'Mulai', format: (v) => tanggal(v) },
    { key: 'tanggal_selesai', label: 'Selesai', format: (v) => tanggal(v) },
    { key: 'lokasi', label: 'Lokasi' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/wfa', { params: { page: page.value } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}
onMounted(load);

function openCreate() { editing.value = null; form.value = { tanggal_mulai: '', tanggal_selesai: '', lokasi: '', alasan: '' }; showForm.value = true; }
function openEdit(row) { editing.value = row; form.value = { tanggal_mulai: row.tanggal_mulai, tanggal_selesai: row.tanggal_selesai, lokasi: row.lokasi || '', alasan: row.alasan || '' }; showForm.value = true; }

async function save() {
    saving.value = true;
    try {
        if (editing.value) { await api.put(`/wfa/${editing.value.id}`, form.value); toastOk('Data diperbarui.'); }
        else { await api.post('/wfa', form.value); toastOk('Pengajuan berhasil.'); }
        showForm.value = false; load();
    } catch (e) { toastErr(errMsg(e)); } finally { saving.value = false; }
}

async function doApproval(status) {
    processing.value = true;
    try {
        await api.post(`/wfa/${showApproval.value.id}/${status}`, { catatan: catatan.value });
        toastOk(status === 'approve' ? 'Disetujui.' : 'Ditolak.');
        showApproval.value = null; load();
    } catch (e) { toastErr(errMsg(e)); } finally { processing.value = false; }
}
</script>

<template>
    <PageHeader title="Work From Anywhere" subtitle="Pengajuan WFA">
        <template #actions><button v-if="canCreate" class="btn-primary" @click="openCreate">+ Ajukan</button></template>
    </PageHeader>
    <DataTable :columns="columns" :rows="rows" :loading="loading">
        <template #actions="{ row }">
            <button v-if="row.status === 'menunggu' && row.id_user === auth.user?.id" class="btn-ghost btn-sm" @click="openEdit(row)">Edit</button>
            <button v-if="canApprove && row.status === 'menunggu'" class="btn-ghost btn-sm" @click="showApproval = row; catatan = ''">Review</button>
        </template>
    </DataTable>
    <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />

    <Modal v-if="showForm" :title="editing ? 'Edit WFA' : 'Ajukan WFA'" @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div><label class="label">Tanggal Mulai</label><input v-model="form.tanggal_mulai" type="date" class="input" required /></div>
            <div><label class="label">Tanggal Selesai</label><input v-model="form.tanggal_selesai" type="date" class="input" required /></div>
            <div><label class="label">Lokasi</label><input v-model="form.lokasi" type="text" class="input" required /></div>
            <div><label class="label">Alasan</label><textarea v-model="form.alasan" class="input" rows="3" required /></div>
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
        </template>
    </Modal>

    <Modal v-if="showApproval" title="Review WFA" @close="showApproval = null">
        <div class="space-y-3 text-sm">
            <div><span class="text-slate-500">Nama:</span> {{ showApproval.user?.name }}</div>
            <div><span class="text-slate-500">Periode:</span> {{ tanggal(showApproval.tanggal_mulai) }} - {{ tanggal(showApproval.tanggal_selesai) }}</div>
            <div><span class="text-slate-500">Lokasi:</span> {{ showApproval.lokasi }}</div>
            <div><label class="label">Catatan</label><textarea v-model="catatan" class="input" rows="2" /></div>
        </div>
        <template #footer>
            <button class="btn-danger" :disabled="processing" @click="doApproval('reject')">Tolak</button>
            <button class="btn-success" :disabled="processing" @click="doApproval('approve')">Setujui</button>
        </template>
    </Modal>
</template>
