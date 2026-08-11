<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastOk, toastErr } from '../../toast';
import { tanggal, jam } from '../../util';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';
import Modal from '../../components/Modal.vue';

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const selected = ref(null);
const catatan = ref('');
const processing = ref(false);

const columns = [
    { key: 'user.name', label: 'Nama' },
    { key: 'tanggal', label: 'Tanggal', format: (v) => tanggal(v) },
    { key: 'waktu_masuk', label: 'Masuk', format: (v) => jam(v) },
    { key: 'tipe_kehadiran', label: 'Tipe', badge: true },
    { key: 'jarak_masuk_meter', label: 'Jarak (m)' },
    { key: 'status_verifikasi', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/presensi/tim', { params: { page: page.value, status: 'perlu_verifikasi' } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        loading.value = false;
    }
}

onMounted(load);

async function verify(status) {
    processing.value = true;
    try {
        await api.post(`/presensi/${selected.value.id}/verify`, { status, catatan: catatan.value });
        toastOk(status === 'disetujui' ? 'Presensi diverifikasi.' : 'Presensi ditolak.');
        selected.value = null;
        catatan.value = '';
        load();
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <PageHeader title="Verifikasi Presensi" subtitle="Verifikasi presensi di luar radius" />

    <DataTable :columns="columns" :rows="rows" :loading="loading">
        <template #actions="{ row }">
            <button class="btn-ghost btn-sm" @click="selected = row">Review</button>
        </template>
    </DataTable>
    <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />

    <Modal v-if="selected" title="Verifikasi Presensi" @close="selected = null">
        <div class="space-y-3 text-sm">
            <div><span class="text-slate-500">Nama:</span> {{ selected.user?.name }}</div>
            <div><span class="text-slate-500">Tanggal:</span> {{ tanggal(selected.tanggal) }}</div>
            <div><span class="text-slate-500">Masuk:</span> {{ jam(selected.waktu_masuk) }}</div>
            <div><span class="text-slate-500">Jarak:</span> {{ selected.jarak_masuk_meter }} m</div>
            <div v-if="selected.foto_masuk">
                <span class="text-slate-500">Foto:</span>
                <img :src="`/storage/${selected.foto_masuk}`" class="mt-1 h-40 rounded-lg object-cover" />
            </div>
            <div>
                <label class="label">Catatan</label>
                <textarea v-model="catatan" class="input" rows="2" />
            </div>
        </div>
        <template #footer>
            <button class="btn-danger" :disabled="processing" @click="verify('ditolak')">Tolak</button>
            <button class="btn-success" :disabled="processing" @click="verify('disetujui')">Setujui</button>
        </template>
    </Modal>
</template>
