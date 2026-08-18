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
import MobileSubHeader from '../../components/MobileSubHeader.vue';

const auth = useAuth();
const isPegawaiMobile = computed(() => auth.roleSlug === 'pegawai');

function prioritasCls(p) {
    const v = String(p || '').toLowerCase();
    if (v === 'urgent') return 'bg-rose-500 text-white';
    if (v === 'tinggi') return 'bg-amber-400 text-white';
    if (v === 'rendah') return 'bg-slate-300 text-slate-700';
    return 'bg-hijau-100 text-hijau-700';
}

function snippet(txt, len = 120) {
    if (!txt) return '';
    const clean = String(txt).replace(/\s+/g, ' ').trim();
    return clean.length > len ? clean.slice(0, len) + '…' : clean;
}
const canCreate = auth.can('pengumuman', 'C');
const canPublish = auth.can('pengumuman', 'P');

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const jenisOptions = ref([]);

const showForm = ref(false);
const editing = ref(null);
const form = ref({ judul: '', isi: '', id_jenis: '', prioritas: 'normal', wajib_konfirmasi: false, target_tipe: 'semua', target_ids: [] });
const saving = ref(false);

const columns = [
    { key: 'judul', label: 'Judul' },
    { key: 'jenis.nama', label: 'Jenis' },
    { key: 'prioritas', label: 'Prioritas', badge: true },
    { key: 'status', label: 'Status', badge: true },
    { key: 'published_at', label: 'Dipublikasi', format: (v) => tanggal(v) },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/pengumuman', { params: { page: page.value } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}

async function loadJenis() {
    try {
        const { data } = await api.get('/master/jenis-pengumuman?per_page=200');
        jenisOptions.value = (data.data || data).filter((j) => j.status === 'aktif');
    } catch (_) {}
}

onMounted(() => { load(); loadJenis(); });

function openCreate() {
    editing.value = null;
    form.value = { judul: '', isi: '', id_jenis: '', prioritas: 'normal', wajib_konfirmasi: false, target_tipe: 'semua', target_ids: [] };
    showForm.value = true;
}

function openEdit(row) {
    editing.value = row;
    form.value = { judul: row.judul, isi: row.isi, id_jenis: row.id_jenis, prioritas: row.prioritas, wajib_konfirmasi: row.wajib_konfirmasi, target_tipe: row.target_tipe || 'semua', target_ids: row.target_ids || [] };
    showForm.value = true;
}

async function save() {
    saving.value = true;
    try {
        if (editing.value) {
            await api.put(`/pengumuman/${editing.value.id}`, form.value);
            toastOk('Pengumuman diperbarui.');
        } else {
            await api.post('/pengumuman', form.value);
            toastOk('Pengumuman dibuat.');
        }
        showForm.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}

async function publish(row) {
    try {
        await api.post(`/pengumuman/${row.id}/publish`);
        toastOk('Pengumuman dipublikasikan.');
        load();
    } catch (e) { toastErr(errMsg(e)); }
}

async function retract(row) {
    if (!confirm('Tarik kembali pengumuman ini?')) return;
    try {
        await api.post(`/pengumuman/${row.id}/retract`);
        toastOk('Pengumuman ditarik.');
        load();
    } catch (e) { toastErr(errMsg(e)); }
}
</script>

<template>
    <div v-if="isPegawaiMobile" class="lg:hidden">
        <MobileSubHeader title="Pengumuman" to="/" />
        <div v-if="loading" class="py-10 text-center text-sm text-slate-400">Memuat...</div>
        <div v-else-if="!rows.length" class="py-10 text-center text-sm text-slate-400">Belum ada pengumuman.</div>
        <div v-else class="mt-3 space-y-3 pb-24">
            <RouterLink v-for="row in rows" :key="row.id" :to="`/pengumuman/${row.id}`"
                        class="block rounded-xl bg-white p-4 shadow-sm active:bg-slate-50">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-slate-800 line-clamp-2">{{ row.judul }}</div>
                        <div class="mt-0.5 text-xs text-slate-500">{{ row.jenis?.nama || '-' }} · {{ tanggal(row.published_at || row.created_at) }}</div>
                    </div>
                    <span v-if="row.prioritas && row.prioritas !== 'normal'"
                          class="shrink-0 rounded-md px-2 py-0.5 text-[10px] font-semibold uppercase" :class="prioritasCls(row.prioritas)">
                        {{ row.prioritas }}
                    </span>
                </div>
                <div class="mt-2 text-xs leading-relaxed text-slate-600">{{ snippet(row.isi) }}</div>
                <div v-if="row.wajib_konfirmasi" class="mt-2 inline-flex items-center gap-1 rounded bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                    <span>Perlu konfirmasi baca</span>
                </div>
            </RouterLink>
        </div>
    </div>

    <template v-else>
        <PageHeader title="Pengumuman" subtitle="Daftar pengumuman internal">
            <template #actions><button v-if="canCreate" class="btn-primary" @click="openCreate">+ Buat</button></template>
        </PageHeader>
        <DataTable :columns="columns" :rows="rows" :loading="loading">
            <template #actions="{ row }">
                <RouterLink :to="`/pengumuman/${row.id}`" class="btn-ghost btn-sm">Detail</RouterLink>
                <button v-if="canCreate && row.status === 'draft'" class="btn-ghost btn-sm" @click="openEdit(row)">Edit</button>
                <button v-if="canPublish && row.status === 'draft'" class="btn-ghost btn-sm text-emerald-600" @click="publish(row)">Publish</button>
                <button v-if="canPublish && row.status === 'published'" class="btn-ghost btn-sm text-amber-600" @click="retract(row)">Tarik</button>
            </template>
        </DataTable>
        <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />
    </template>

    <Modal v-if="showForm" :title="editing ? 'Edit Pengumuman' : 'Buat Pengumuman'" wide @close="showForm = false">
        <form @submit.prevent="save" class="space-y-4">
            <div><label class="label">Judul</label><input v-model="form.judul" type="text" class="input" required /></div>
            <div>
                <label class="label">Jenis</label>
                <select v-model="form.id_jenis" class="input" required>
                    <option value="">-- Pilih --</option>
                    <option v-for="j in jenisOptions" :key="j.id" :value="j.id">{{ j.nama }}</option>
                </select>
            </div>
            <div>
                <label class="label">Prioritas</label>
                <select v-model="form.prioritas" class="input">
                    <option value="rendah">Rendah</option>
                    <option value="normal">Normal</option>
                    <option value="tinggi">Tinggi</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div>
                <label class="label">Target Penerima</label>
                <select v-model="form.target_tipe" class="input">
                    <option value="semua">Semua Karyawan</option>
                    <option value="kantor">Per Kantor</option>
                    <option value="unit">Per Unit Kerja</option>
                    <option value="jabatan">Per Jabatan</option>
                </select>
            </div>
            <div><label class="label">Isi</label><textarea v-model="form.isi" class="input" rows="8" required /></div>
            <label class="flex items-center gap-2"><input type="checkbox" v-model="form.wajib_konfirmasi" class="h-4 w-4 rounded border-slate-300" /><span class="text-sm text-slate-600">Perlu konfirmasi baca dari penerima</span></label>
        </form>
        <template #footer>
            <button class="btn-ghost" @click="showForm = false">Batal</button>
            <button class="btn-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
        </template>
    </Modal>
</template>
