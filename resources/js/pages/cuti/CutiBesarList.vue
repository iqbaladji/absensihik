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
const canCreate = auth.can('cuti_besar', 'C');
const canApprove = auth.can('cuti_besar', 'A');

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const showForm = ref(false);
const form = ref({ tanggal_mulai: '', tanggal_selesai: '', jumlah_hari: 30, alasan: '' });
const saving = ref(false);
const showApproval = ref(null);
const catatan = ref('');
const processing = ref(false);

const columns = [
    { key: 'user.name', label: 'Nama' },
    { key: 'tanggal_mulai', label: 'Mulai', format: (v) => tanggal(v) },
    { key: 'tanggal_selesai', label: 'Selesai', format: (v) => tanggal(v) },
    { key: 'jumlah_hari', label: 'Hari' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/cuti-besar', { params: { page: page.value } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}
onMounted(load);

async function save() {
    saving.value = true;
    try {
        await api.post('/cuti-besar', form.value);
        toastOk('Pengajuan berhasil.');
        showForm.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}

async function doApproval(status) {
    processing.value = true;
    try {
        await api.post(`/cuti-besar/${showApproval.value.id}/${status}`, { catatan: catatan.value });
        toastOk(status === 'approve' ? 'Disetujui.' : 'Ditolak.');
        showApproval.value = null;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { processing.value = false; }
}
</script>

<template>
    <PageHeader title="Cuti Besar" subtitle="Pengajuan cuti besar (masa kerja min. 6 tahun)">
        <template #actions><button v-if="canCreate" class="btn-primary" @click="form = { tanggal_mulai: '', tanggal_selesai: '', jumlah_hari: 30, alasan: '' }; showForm = true">+ Ajukan</button></template>
    </PageHeader>
    <DataTable :columns="columns" :rows="rows" :loading="loading">
        <template #actions="{ row }">
            <button v-if="canApprove && row.status === 'menunggu'" class="btn-ghost btn-sm" @click="showApproval = row; catatan = ''">Review</button>
        </template>
    </DataTable>
    <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />

    <Modal v-if="showForm" title="Ajukan Cuti Besar" @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div><label class="label">Tanggal Mulai</label><input v-model="form.tanggal_mulai" type="date" class="input" required /></div>
            <div><label class="label">Tanggal Selesai</label><input v-model="form.tanggal_selesai" type="date" class="input" required /></div>
            <div><label class="label">Jumlah Hari</label><input v-model.number="form.jumlah_hari" type="number" class="input" min="1" required /></div>
            <div><label class="label">Alasan</label><textarea v-model="form.alasan" class="input" rows="3" required /></div>
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Ajukan' }}</button>
        </template>
    </Modal>

    <Modal v-if="showApproval" title="Review Cuti Besar" @close="showApproval = null">
        <div class="space-y-3 text-sm">
            <div><span class="text-slate-500">Nama:</span> {{ showApproval.user?.name }}</div>
            <div><span class="text-slate-500">Periode:</span> {{ tanggal(showApproval.tanggal_mulai) }} - {{ tanggal(showApproval.tanggal_selesai) }}</div>
            <div><label class="label">Catatan</label><textarea v-model="catatan" class="input" rows="2" /></div>
        </div>
        <template #footer>
            <button class="btn-danger" :disabled="processing" @click="doApproval('reject')">Tolak</button>
            <button class="btn-success" :disabled="processing" @click="doApproval('approve')">Setujui</button>
        </template>
    </Modal>
</template>
