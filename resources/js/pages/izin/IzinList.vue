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
import SearchFilter from '../../components/SearchFilter.vue';
import FileUpload from '../../components/FileUpload.vue';

const auth = useAuth();
const canCreate = auth.can('izin', 'C');
const canApprove = auth.can('izin', 'A');

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const jenisIzinList = ref([]);

const showForm = ref(false);
const editing = ref(null);
const form = ref({ id_jenis_izin: '', tanggal_mulai: '', tanggal_selesai: '', alasan: '' });
const lampiran = ref(null);
const saving = ref(false);

const showApproval = ref(null);
const catatan = ref('');
const processing = ref(false);

const columns = [
    { key: 'user.name', label: 'Nama' },
    { key: 'jenis_izin.nama', label: 'Jenis' },
    { key: 'tanggal_mulai', label: 'Mulai', format: (v) => tanggal(v) },
    { key: 'tanggal_selesai', label: 'Selesai', format: (v) => tanggal(v) },
    { key: 'jumlah_hari', label: 'Hari' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/izin', { params: { page: page.value } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}

async function loadJenisIzin() {
    try {
        const { data } = await api.get('/master/jenis-izin?per_page=200');
        jenisIzinList.value = (data.data || data).filter((j) => j.status === 'aktif');
    } catch (_) {}
}

onMounted(() => { load(); loadJenisIzin(); });

function openCreate() {
    editing.value = null;
    form.value = { id_jenis_izin: '', tanggal_mulai: '', tanggal_selesai: '', alasan: '' };
    lampiran.value = null;
    showForm.value = true;
}

async function save() {
    saving.value = true;
    try {
        const fd = new FormData();
        Object.entries(form.value).forEach(([k, v]) => fd.append(k, v));
        if (lampiran.value) fd.append('lampiran', lampiran.value);
        if (editing.value) {
            fd.append('_method', 'PUT');
            await api.post(`/izin/${editing.value.id}`, fd);
            toastOk('Data diperbarui.');
        } else {
            await api.post('/izin', fd);
            toastOk('Pengajuan berhasil.');
        }
        showForm.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}

async function doApproval(status) {
    processing.value = true;
    try {
        await api.post(`/izin/${showApproval.value.id}/${status}`, { catatan: catatan.value });
        toastOk(status === 'approve' ? 'Disetujui.' : 'Ditolak.');
        showApproval.value = null;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { processing.value = false; }
}

async function cancel(row) {
    if (!confirm('Batalkan pengajuan ini?')) return;
    try {
        await api.post(`/izin/${row.id}/cancel`);
        toastOk('Pengajuan dibatalkan.');
        load();
    } catch (e) { toastErr(errMsg(e)); }
}
</script>

<template>
    <PageHeader title="Izin" subtitle="Pengajuan izin">
        <template #actions><button v-if="canCreate" class="btn-primary" @click="openCreate">+ Ajukan</button></template>
    </PageHeader>

    <DataTable :columns="columns" :rows="rows" :loading="loading">
        <template #actions="{ row }">
            <button v-if="row.status === 'menunggu' && row.id_user === auth.user?.id" class="btn-ghost btn-sm text-rose-600" @click="cancel(row)">Batal</button>
            <button v-if="canApprove && row.status === 'menunggu'" class="btn-ghost btn-sm" @click="showApproval = row; catatan = ''">Review</button>
        </template>
    </DataTable>
    <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />

    <Modal v-if="showForm" title="Ajukan Izin" @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div>
                <label class="label">Jenis Izin</label>
                <select v-model="form.id_jenis_izin" class="input" required>
                    <option value="">-- Pilih --</option>
                    <option v-for="j in jenisIzinList" :key="j.id" :value="j.id">{{ j.nama }}</option>
                </select>
            </div>
            <div><label class="label">Tanggal Mulai</label><input v-model="form.tanggal_mulai" type="date" class="input" required /></div>
            <div><label class="label">Tanggal Selesai</label><input v-model="form.tanggal_selesai" type="date" class="input" required /></div>
            <div><label class="label">Alasan</label><textarea v-model="form.alasan" class="input" rows="3" required /></div>
            <FileUpload label="Lampiran (opsional)" @selected="(f) => lampiran = f" />
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Ajukan' }}</button>
        </template>
    </Modal>

    <Modal v-if="showApproval" title="Review Izin" @close="showApproval = null">
        <div class="space-y-3 text-sm">
            <div><span class="text-slate-500">Nama:</span> {{ showApproval.user?.name }}</div>
            <div><span class="text-slate-500">Jenis:</span> {{ showApproval.jenis_izin?.nama }}</div>
            <div><span class="text-slate-500">Periode:</span> {{ tanggal(showApproval.tanggal_mulai) }} - {{ tanggal(showApproval.tanggal_selesai) }}</div>
            <div><span class="text-slate-500">Alasan:</span> {{ showApproval.alasan }}</div>
            <div><label class="label">Catatan</label><textarea v-model="catatan" class="input" rows="2" /></div>
        </div>
        <template #footer>
            <button class="btn-danger" :disabled="processing" @click="doApproval('reject')">Tolak</button>
            <button class="btn-success" :disabled="processing" @click="doApproval('approve')">Setujui</button>
        </template>
    </Modal>
</template>
