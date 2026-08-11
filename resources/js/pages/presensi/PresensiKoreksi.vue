<script setup>
import { ref, watch, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { useAuth } from '../../stores/auth';
import { toastOk, toastErr } from '../../toast';
import { tanggal, jam } from '../../util';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';
import Modal from '../../components/Modal.vue';
import ApprovalBadge from '../../components/ApprovalBadge.vue';

const auth = useAuth();
const isApprover = auth.can('presensi', 'A');

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);

const showForm = ref(false);
const form = ref({ id_presensi: '', tanggal: '', jam_masuk_koreksi: '', jam_keluar_koreksi: '', alasan: '' });
const saving = ref(false);
const presensiList = ref([]);
const loadingPresensi = ref(false);

watch(() => form.value.tanggal, async (tgl) => {
    form.value.id_presensi = '';
    presensiList.value = [];
    if (!tgl) return;
    loadingPresensi.value = true;
    try {
        const { data } = await api.get('/presensi/riwayat', { params: { dari: tgl, sampai: tgl, per_page: 10 } });
        const items = data.data || [];
        presensiList.value = items;
        if (items.length === 1) form.value.id_presensi = items[0].id;
    } catch (_) {}
    finally { loadingPresensi.value = false; }
});

const selected = ref(null);
const catatan = ref('');
const processing = ref(false);

const columns = [
    { key: 'user.name', label: 'Nama' },
    { key: 'tanggal', label: 'Tanggal', format: (v) => tanggal(v) },
    { key: 'jam_masuk_koreksi', label: 'Masuk (Koreksi)', format: (v) => jam(v) },
    { key: 'jam_keluar_koreksi', label: 'Pulang (Koreksi)', format: (v) => jam(v) },
    { key: 'alasan', label: 'Alasan' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/presensi/koreksi', { params: { page: page.value } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        loading.value = false;
    }
}

onMounted(load);

async function submit() {
    saving.value = true;
    try {
        await api.post('/presensi/koreksi', form.value);
        toastOk('Pengajuan koreksi berhasil.');
        showForm.value = false;
        form.value = { id_presensi: '', tanggal: '', jam_masuk_koreksi: '', jam_keluar_koreksi: '', alasan: '' };
        load();
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        saving.value = false;
    }
}

async function approve() {
    processing.value = true;
    try {
        await api.post(`/presensi/koreksi/${selected.value.id}/approve`, { catatan: catatan.value });
        toastOk('Koreksi disetujui.');
        selected.value = null;
        load();
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        processing.value = false;
    }
}

async function reject() {
    processing.value = true;
    try {
        await api.post(`/presensi/koreksi/${selected.value.id}/reject`, { catatan: catatan.value });
        toastOk('Koreksi ditolak.');
        selected.value = null;
        load();
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <PageHeader title="Koreksi Presensi" subtitle="Ajukan atau kelola koreksi presensi">
        <template #actions>
            <button class="btn-primary" @click="showForm = true">+ Ajukan Koreksi</button>
        </template>
    </PageHeader>

    <DataTable :columns="columns" :rows="rows" :loading="loading">
        <template #actions="{ row }">
            <button v-if="isApprover && row.status === 'menunggu'" class="btn-ghost btn-sm" @click="selected = row; catatan = ''">Review</button>
        </template>
    </DataTable>
    <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />

    <Modal v-if="showForm" title="Ajukan Koreksi Presensi" @close="showForm = false">
        <form @submit.prevent="submit" class="space-y-4">
            <div><label class="label">Tanggal</label><input v-model="form.tanggal" type="date" class="input" required /></div>
            <div v-if="form.tanggal">
                <label class="label">Presensi</label>
                <div v-if="loadingPresensi" class="text-sm text-slate-500">Mencari data presensi...</div>
                <div v-else-if="presensiList.length === 0" class="text-sm text-red-500">Tidak ada data presensi pada tanggal ini.</div>
                <select v-else v-model="form.id_presensi" class="input" required>
                    <option value="" disabled>Pilih presensi</option>
                    <option v-for="p in presensiList" :key="p.id" :value="p.id">{{ p.jam_masuk ? jam(p.jam_masuk) : '-' }} / {{ p.jam_keluar ? jam(p.jam_keluar) : '-' }} ({{ p.status }})</option>
                </select>
            </div>
            <div><label class="label">Jam Masuk (Koreksi)</label><input v-model="form.jam_masuk_koreksi" type="time" class="input" /></div>
            <div><label class="label">Jam Pulang (Koreksi)</label><input v-model="form.jam_keluar_koreksi" type="time" class="input" /></div>
            <div><label class="label">Alasan</label><textarea v-model="form.alasan" class="input" rows="3" required /></div>
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="submit">{{ saving ? 'Menyimpan...' : 'Ajukan' }}</button>
        </template>
    </Modal>

    <Modal v-if="selected" title="Review Koreksi" @close="selected = null">
        <div class="space-y-3 text-sm">
            <div><span class="text-slate-500">Nama:</span> {{ selected.user?.name }}</div>
            <div><span class="text-slate-500">Tanggal:</span> {{ tanggal(selected.tanggal) }}</div>
            <div><span class="text-slate-500">Alasan:</span> {{ selected.alasan }}</div>
            <div><label class="label">Catatan</label><textarea v-model="catatan" class="input" rows="2" /></div>
        </div>
        <template #footer>
            <button class="btn-danger" :disabled="processing" @click="reject">Tolak</button>
            <button class="btn-success" :disabled="processing" @click="approve">Setujui</button>
        </template>
    </Modal>
</template>
