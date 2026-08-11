<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { useAuth } from '../../stores/auth';
import { toastOk, toastErr } from '../../toast';
import { tanggal, jam } from '../../util';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';
import Modal from '../../components/Modal.vue';

const auth = useAuth();
const canCreate = auth.can('lembur', 'C');
const canApprove = auth.can('lembur', 'A');

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const showForm = ref(false);
const form = ref({ tanggal: '', jam_mulai_rencana: '', jam_selesai_rencana: '', uraian_pekerjaan: '' });
const saving = ref(false);
const showApproval = ref(null);
const catatan = ref('');
const processing = ref(false);

const columns = [
    { key: 'user.name', label: 'Nama' },
    { key: 'tanggal', label: 'Tanggal', format: (v) => tanggal(v) },
    { key: 'jam_mulai_rencana', label: 'Rencana Mulai' },
    { key: 'jam_selesai_rencana', label: 'Rencana Selesai' },
    { key: 'jam_mulai_aktual', label: 'Aktual Mulai', format: (v) => jam(v) },
    { key: 'jam_selesai_aktual', label: 'Aktual Selesai', format: (v) => jam(v) },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/lembur', { params: { page: page.value } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}
onMounted(load);

async function save() {
    saving.value = true;
    try {
        await api.post('/lembur', form.value);
        toastOk('Pengajuan lembur berhasil.');
        showForm.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}

async function doApproval(status) {
    processing.value = true;
    try {
        await api.post(`/lembur/${showApproval.value.id}/${status}`, { catatan: catatan.value });
        toastOk(status === 'approve' ? 'Disetujui.' : 'Ditolak.');
        showApproval.value = null;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { processing.value = false; }
}

async function mulaiLembur(row) {
    try {
        await api.post(`/lembur/${row.id}/mulai`);
        toastOk('Lembur dimulai.');
        load();
    } catch (e) { toastErr(errMsg(e)); }
}

const showSelesai = ref(null);
const hasilPekerjaan = ref('');

async function selesaiLembur() {
    try {
        await api.post(`/lembur/${showSelesai.value.id}/selesai`, { hasil_pekerjaan: hasilPekerjaan.value });
        toastOk('Lembur selesai.');
        showSelesai.value = null;
        load();
    } catch (e) { toastErr(errMsg(e)); }
}
</script>

<template>
    <PageHeader title="Lembur" subtitle="Pengajuan dan pelaksanaan lembur">
        <template #actions><button v-if="canCreate" class="btn-primary" @click="form = { tanggal: '', jam_mulai_rencana: '', jam_selesai_rencana: '', uraian_pekerjaan: '' }; showForm = true">+ Ajukan</button></template>
    </PageHeader>
    <DataTable :columns="columns" :rows="rows" :loading="loading">
        <template #actions="{ row }">
            <button v-if="row.status === 'disetujui' && !row.jam_mulai_aktual && row.id_user === auth.user?.id" class="btn-ghost btn-sm text-emerald-600" @click="mulaiLembur(row)">Mulai</button>
            <button v-if="row.status === 'berlangsung' && row.jam_mulai_aktual && !row.jam_selesai_aktual && row.id_user === auth.user?.id" class="btn-ghost btn-sm text-amber-600" @click="showSelesai = row; hasilPekerjaan = ''">Selesai</button>
            <button v-if="canApprove && row.status === 'menunggu'" class="btn-ghost btn-sm" @click="showApproval = row; catatan = ''">Review</button>
        </template>
    </DataTable>
    <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />

    <Modal v-if="showForm" title="Ajukan Lembur" @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div><label class="label">Tanggal</label><input v-model="form.tanggal" type="date" class="input" required /></div>
            <div><label class="label">Jam Mulai (Rencana)</label><input v-model="form.jam_mulai_rencana" type="time" class="input" required /></div>
            <div><label class="label">Jam Selesai (Rencana)</label><input v-model="form.jam_selesai_rencana" type="time" class="input" required /></div>
            <div><label class="label">Alasan</label><textarea v-model="form.uraian_pekerjaan" class="input" rows="3" required /></div>
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Ajukan' }}</button>
        </template>
    </Modal>

    <Modal v-if="showApproval" title="Review Lembur" @close="showApproval = null">
        <div class="space-y-3 text-sm">
            <div><span class="text-slate-500">Nama:</span> {{ showApproval.user?.name }}</div>
            <div><span class="text-slate-500">Tanggal:</span> {{ tanggal(showApproval.tanggal) }}</div>
            <div><span class="text-slate-500">Rencana:</span> {{ showApproval.jam_mulai_rencana }} - {{ showApproval.jam_selesai_rencana }}</div>
            <div><span class="text-slate-500">Uraian Pekerjaan:</span> {{ showApproval.uraian_pekerjaan }}</div>
            <div><label class="label">Catatan</label><textarea v-model="catatan" class="input" rows="2" /></div>
        </div>
        <template #footer>
            <button class="btn-danger" :disabled="processing" @click="doApproval('reject')">Tolak</button>
            <button class="btn-success" :disabled="processing" @click="doApproval('approve')">Setujui</button>
        </template>
    </Modal>

    <Modal v-if="showSelesai" title="Selesai Lembur" @close="showSelesai = null">
        <div class="space-y-4">
            <div class="text-sm text-slate-500">Tanggal: {{ tanggal(showSelesai.tanggal) }}, Mulai: {{ showSelesai.jam_mulai_aktual }}</div>
            <div><label class="label">Hasil Pekerjaan</label><textarea v-model="hasilPekerjaan" class="input" rows="3" required placeholder="Jelaskan hasil pekerjaan lembur..." /></div>
        </div>
        <template #footer>
            <button class="btn-ghost" @click="showSelesai = null">Batal</button>
            <button class="btn-primary" :disabled="!hasilPekerjaan.trim()" @click="selesaiLembur">Selesai</button>
        </template>
    </Modal>
</template>
