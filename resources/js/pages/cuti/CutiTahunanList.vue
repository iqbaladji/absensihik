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
const canCreate = auth.can('cuti_tahunan', 'C');
const canApprove = auth.can('cuti_tahunan', 'A');

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const showForm = ref(false);
const form = ref({ tanggal_mulai: '', tanggal_selesai: '', alasan: '' });
const saving = ref(false);
const showApproval = ref(null);
const catatan = ref('');
const processing = ref(false);
const sisaCuti = ref(null);

const columns = [
    { key: 'user.name', label: 'Nama' },
    { key: 'pengganti.name', label: 'Pengganti' },
    { key: 'tanggal_mulai', label: 'Mulai', format: (v) => tanggal(v) },
    { key: 'tanggal_selesai', label: 'Selesai', format: (v) => tanggal(v) },
    { key: 'jumlah_hari', label: 'Hari' },
    { key: 'alasan', label: 'Alasan' },
    { key: 'status', label: 'Status', badge: true },
];

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/cuti-tahunan', { params: { page: page.value } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}
async function loadSaldo() {
    try {
        const { data } = await api.get('/cuti-tahunan-saldo');
        sisaCuti.value = data.data?.sisa ?? data.sisa ?? data.data?.saldo ?? null;
    } catch (_) {}
}
onMounted(() => { load(); loadSaldo(); });

function openForm() {
    form.value = { tanggal_mulai: '', tanggal_selesai: '', alasan: '' };
    showForm.value = true;
}

async function save() {
    if (!form.value.tanggal_mulai || !form.value.tanggal_selesai || !form.value.alasan) {
        toastErr('Lengkapi semua kolom.');
        return;
    }
    saving.value = true;
    try {
        await api.post('/cuti-tahunan', form.value);
        toastOk('Pengajuan cuti berhasil.');
        showForm.value = false;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { saving.value = false; }
}

async function doApproval(status) {
    processing.value = true;
    try {
        await api.post(`/cuti-tahunan/${showApproval.value.id}/${status}`, { catatan: catatan.value });
        toastOk(status === 'approve' ? 'Cuti disetujui.' : 'Cuti ditolak.');
        showApproval.value = null;
        load();
    } catch (e) { toastErr(errMsg(e)); }
    finally { processing.value = false; }
}

function statusPill(s) {
    const v = String(s || '').toLowerCase();
    if (v.includes('setuj') || v.includes('approve')) return { text: 'Approved', cls: 'bg-hijau-600 text-white' };
    if (v.includes('tolak') || v.includes('reject')) return { text: 'Rejected', cls: 'bg-rose-500 text-white' };
    return { text: 'Pending', cls: 'bg-amber-400 text-white' };
}
</script>

<template>
    <!-- ==================== MOBILE (pegawai) ==================== -->
    <template v-if="isPegawaiMobile">
        <div class="lg:hidden">
            <MobileSubHeader title="Cuti" to="/" />

            <div class="mt-3 flex items-center justify-between px-1">
                <h2 class="text-base font-semibold text-slate-800">Riwayat Cuti</h2>
                <button class="rounded-lg p-1.5 text-hijau-600" aria-label="Filter">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M7 12h10M10 18h4"/></svg>
                </button>
            </div>

            <div v-if="loading" class="py-10 text-center text-sm text-slate-400">Memuat...</div>
            <div v-else-if="!rows.length" class="py-10 text-center text-sm text-slate-400">Belum ada pengajuan.</div>
            <div v-else class="mt-2 space-y-3 pb-24">
                <div v-for="row in rows" :key="row.id" class="rounded-xl bg-slate-100 p-3 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-amber-400 bg-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-hijau-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4 0-7 2-7 5v1h14v-1c0-3-3-5-7-5z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-hijau-700">Cuti Tahunan</div>
                            <div class="text-sm text-slate-500">{{ tanggal(row.tanggal_mulai) }} — {{ tanggal(row.tanggal_selesai) }}</div>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between border-t border-slate-200 pt-2">
                        <div class="min-w-0 pr-2 text-xs text-slate-600 truncate">{{ row.alasan || '—' }}</div>
                        <span class="rounded-md px-4 py-1.5 text-xs font-semibold" :class="statusPill(row.status).cls">{{ statusPill(row.status).text }}</span>
                    </div>
                </div>
            </div>

            <button v-if="canCreate" class="fixed inset-x-4 bottom-20 z-20 rounded-full bg-hijau-600 py-3.5 text-center text-sm font-semibold text-white shadow-lg active:bg-hijau-700" @click="openForm">
                Tambah
            </button>
        </div>

        <!-- Mobile form (full sheet) -->
        <div v-if="showForm" class="fixed inset-0 z-50 flex flex-col bg-slate-50 lg:hidden">
            <div class="flex items-center gap-2 bg-hijau-600 px-3 py-3 text-white shadow-sm">
                <button class="rounded-full p-1.5 hover:bg-white/10" @click="showForm = false" aria-label="Kembali">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <h1 class="flex-1 text-center pr-8 text-base font-semibold">Cuti</h1>
            </div>
            <div class="bg-amber-100/70 px-4 py-2.5 text-sm">
                <span class="text-slate-700">Sisa Cuti : </span>
                <span class="font-semibold text-hijau-700">{{ sisaCuti ?? '-' }} Hari</span>
            </div>
            <form @submit.prevent="save" class="flex-1 space-y-4 overflow-y-auto px-4 py-4">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tanggal awal</label>
                    <input v-model="form.tanggal_mulai" type="date" class="w-full rounded-lg border-none bg-slate-200/70 px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-hijau-500" required />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tanggal Akhir</label>
                    <input v-model="form.tanggal_selesai" type="date" class="w-full rounded-lg border-none bg-slate-200/70 px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-hijau-500" required />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Alasan</label>
                    <textarea v-model="form.alasan" rows="4" class="w-full rounded-lg border-none bg-slate-200/70 px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-hijau-500" required></textarea>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Pengganti</label>
                    <div class="rounded-lg bg-slate-200/70 px-3 py-2.5 text-sm text-slate-700">
                        <template v-if="auth.user?.atasan">
                            {{ auth.user.atasan.name }}
                            <span v-if="auth.user.atasan.jabatan" class="text-slate-500">· {{ auth.user.atasan.jabatan }}</span>
                        </template>
                        <span v-else class="text-rose-500">Belum ada atasan langsung — hubungi HR.</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Otomatis diambil dari atasan langsung Anda.</p>
                </div>
            </form>
            <div class="border-t border-slate-200 bg-white px-4 pb-6 pt-3">
                <button type="button" class="w-full rounded-full bg-hijau-600 py-3.5 text-center text-sm font-semibold text-white shadow-lg active:bg-hijau-700 disabled:opacity-50" :disabled="saving" @click="save">
                    {{ saving ? 'Menyimpan...' : 'Tambah' }}
                </button>
            </div>
        </div>
    </template>

    <!-- ==================== DESKTOP / OTHER ROLES ==================== -->
    <template v-else>
        <PageHeader title="Cuti Tahunan" subtitle="Pengajuan cuti tahunan">
            <template #actions><button v-if="canCreate" class="btn-hijau" @click="openForm">+ Ajukan</button></template>
        </PageHeader>
        <DataTable :columns="columns" :rows="rows" :loading="loading">
            <template #actions="{ row }">
                <button v-if="canApprove && row.status === 'menunggu'" class="btn-ghost btn-sm" @click="showApproval = row; catatan = ''">Review</button>
            </template>
        </DataTable>
        <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />

        <Modal v-if="showForm" title="Ajukan Cuti Tahunan" @close="showForm = false">
            <form @submit.prevent="save" class="space-y-4">
                <div><label class="label">Tanggal Mulai</label><input v-model="form.tanggal_mulai" type="date" class="input" required /></div>
                <div><label class="label">Tanggal Selesai</label><input v-model="form.tanggal_selesai" type="date" class="input" required /></div>
                <div><label class="label">Alasan</label><textarea v-model="form.alasan" class="input" rows="3" required /></div>
                <div>
                    <label class="label">Pengganti (atasan langsung)</label>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        <template v-if="auth.user?.atasan">{{ auth.user.atasan.name }}</template>
                        <span v-else class="text-rose-600">Belum ada atasan langsung — hubungi HR.</span>
                    </div>
                </div>
            </form>
            <template #footer>
                <button class="btn-ghost" @click="showForm = false">Batal</button>
                <button class="btn-hijau" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Ajukan' }}</button>
            </template>
        </Modal>
    </template>

    <Modal v-if="showApproval" title="Review Cuti Tahunan" @close="showApproval = null">
        <div class="space-y-3 text-sm">
            <div><span class="text-slate-500">Nama:</span> {{ showApproval.user?.name }}</div>
            <div><span class="text-slate-500">Pengganti:</span> {{ showApproval.pengganti?.name || '-' }}</div>
            <div><span class="text-slate-500">Periode:</span> {{ tanggal(showApproval.tanggal_mulai) }} - {{ tanggal(showApproval.tanggal_selesai) }}</div>
            <div><span class="text-slate-500">Jumlah:</span> {{ showApproval.jumlah_hari }} hari</div>
            <div><label class="label">Catatan</label><textarea v-model="catatan" class="input" rows="2" /></div>
        </div>
        <template #footer>
            <button class="btn-danger" :disabled="processing" @click="doApproval('reject')">Tolak</button>
            <button class="btn-success" :disabled="processing" @click="doApproval('approve')">Setujui</button>
        </template>
    </Modal>
</template>
