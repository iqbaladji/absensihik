<script setup>
import { ref, onMounted, computed } from 'vue';
import api, { errMsg } from '../../api';
import { useAuth } from '../../stores/auth';
import { toastOk, toastErr } from '../../toast';
import { tanggal } from '../../util';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';
import Modal from '../../components/Modal.vue';
import FileUpload from '../../components/FileUpload.vue';
import MobileListPegawai from '../../components/MobileListPegawai.vue';

const auth = useAuth();
const isPegawaiMobile = computed(() => auth.roleSlug === 'pegawai');
function openCreate() { form.value = { tanggal_mulai: '', jumlah_hari: 90, tipe: 'melahirkan', catatan: '' }; lampiran.value = null; showForm.value = true; }
const canCreate = auth.can('cuti_melahirkan', 'C');
const canApprove = auth.can('cuti_melahirkan', 'A');

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const showForm = ref(false);
const form = ref({ tanggal_mulai: '', jumlah_hari: 90, tipe: 'melahirkan', catatan: '' });
const lampiran = ref(null);
const saving = ref(false);
const showApproval = ref(null);
const catatan = ref('');
const processing = ref(false);

const columns = [
    { key: 'user.name', label: 'Nama' },
    { key: 'tanggal_mulai', label: 'Mulai', format: (v) => tanggal(v) },
    { key: 'tanggal_selesai', label: 'Selesai', format: (v) => tanggal(v) },
    { key: 'jumlah_hari', label: 'Durasi (hari)' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/cuti-melahirkan', { params: { page: page.value } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}
onMounted(load);

async function save() {
    saving.value = true;
    try {
        const fd = new FormData();
        Object.entries(form.value).forEach(([k, v]) => fd.append(k, v));
        if (lampiran.value) fd.append('lampiran', lampiran.value);
        await api.post('/cuti-melahirkan', fd);
        toastOk('Pengajuan berhasil.');
        showForm.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}

async function doApproval(status) {
    processing.value = true;
    try {
        await api.post(`/cuti-melahirkan/${showApproval.value.id}/${status}`, { catatan: catatan.value });
        toastOk(status === 'approve' ? 'Disetujui.' : 'Ditolak.');
        showApproval.value = null;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { processing.value = false; }
}
</script>

<template>
    <MobileListPegawai
        v-if="isPegawaiMobile"
        title="Cuti Melahirkan"
        section-title="Riwayat Cuti Melahirkan"
        :items="rows"
        :loading="loading"
        :can-add="canCreate"
        :item-title="(r) => r.tipe === 'keguguran' ? 'Cuti Keguguran' : 'Cuti Melahirkan'"
        :item-periode="(r) => `${tanggal(r.tanggal_mulai)} — ${tanggal(r.tanggal_selesai)}`"
        :item-alasan="(r) => r.catatan"
        :item-status="(r) => r.status"
        @add="openCreate"
    />
    <template v-else>
        <PageHeader title="Cuti Melahirkan" subtitle="Pengajuan cuti melahirkan">
            <template #actions><button v-if="canCreate" class="btn-hijau" @click="openCreate">+ Ajukan</button></template>
        </PageHeader>
        <DataTable :columns="columns" :rows="rows" :loading="loading">
            <template #actions="{ row }">
                <button v-if="canApprove && row.status === 'menunggu'" class="btn-ghost btn-sm" @click="showApproval = row; catatan = ''">Review</button>
            </template>
        </DataTable>
        <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />
    </template>

    <Modal v-if="showForm" title="Ajukan Cuti Melahirkan" @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div><label class="label">Tipe</label>
                <select v-model="form.tipe" class="input" required>
                    <option value="melahirkan">Melahirkan</option>
                    <option value="keguguran">Keguguran</option>
                </select>
            </div>
            <div><label class="label">Tanggal Mulai</label><input v-model="form.tanggal_mulai" type="date" class="input" required /></div>
            <div><label class="label">Durasi (hari)</label><input v-model.number="form.jumlah_hari" type="number" class="input" min="1" max="180" required /></div>
            <div><label class="label">Catatan</label><textarea v-model="form.catatan" class="input" rows="3" /></div>
            <div>
                <label class="label">Pengganti (atasan langsung)</label>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                    <template v-if="auth.user?.atasan">{{ auth.user.atasan.name }}</template>
                    <span v-else class="text-rose-600">Belum ada atasan langsung — hubungi HR.</span>
                </div>
            </div>
            <FileUpload label="Surat Keterangan Dokter" accept=".pdf,.jpg,.jpeg,.png" @selected="(f) => lampiran = f" />
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-hijau" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Ajukan' }}</button>
        </template>
    </Modal>

    <Modal v-if="showApproval" title="Review Cuti Melahirkan" @close="showApproval = null">
        <div class="space-y-3 text-sm">
            <div><span class="text-slate-500">Nama:</span> {{ showApproval.user?.name }}</div>
            <div><span class="text-slate-500">Periode:</span> {{ tanggal(showApproval.tanggal_mulai) }} - {{ tanggal(showApproval.tanggal_selesai) }}</div>
            <div><span class="text-slate-500">Durasi:</span> {{ showApproval.jumlah_hari }} hari</div>
            <div><label class="label">Catatan</label><textarea v-model="catatan" class="input" rows="2" /></div>
        </div>
        <template #footer>
            <button class="btn-danger" :disabled="processing" @click="doApproval('reject')">Tolak</button>
            <button class="btn-success" :disabled="processing" @click="doApproval('approve')">Setujui</button>
        </template>
    </Modal>
</template>
