<script setup>
import { ref, onMounted, computed } from 'vue';
import api, { errMsg } from '../../api';
import { toastErr } from '../../toast';
import { rupiah } from '../../util';
import { useAuth } from '../../stores/auth';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';
import MobileSubHeader from '../../components/MobileSubHeader.vue';

const auth = useAuth();
const isPegawaiMobile = computed(() => auth.roleSlug === 'pegawai');

const rows = ref([]);
const meta = ref(null);
const loading = ref(false);
const page = ref(1);
const pinVerified = ref(false);
const pin = ref('');
const verifying = ref(false);

const columns = [
    { key: 'periode.nama', label: 'Periode' },
    { key: 'gaji_bruto', label: 'Pendapatan', format: (v) => rupiah(v) },
    { key: 'total_potongan', label: 'Potongan', format: (v) => rupiah(v) },
    { key: 'gaji_netto', label: 'Gaji Bersih', format: (v) => rupiah(v) },
];

async function verifyPin() {
    verifying.value = true;
    try {
        await api.post('/payslip/verify-pin', { pin: pin.value });
        pinVerified.value = true;
        load();
    } catch (e) {
        toastErr(errMsg(e, 'PIN salah'));
    } finally {
        verifying.value = false;
    }
}

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/payslip', { params: { page: page.value } });
        rows.value = data.data;
        meta.value = data.meta || data;
    } catch (e) { toastErr(errMsg(e)); }
    finally { loading.value = false; }
}

onMounted(load);
</script>

<template>
    <!-- ==================== MOBILE (pegawai) ==================== -->
    <template v-if="isPegawaiMobile">
        <MobileSubHeader title="Slip Gaji" to="/" />

        <div v-if="!pinVerified" class="mt-6 px-2">
            <div class="rounded-2xl bg-white p-6 text-center shadow-sm">
                <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-hijau-100 text-hijau-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0v4"/></svg>
                </div>
                <h3 class="mb-1 text-base font-semibold text-slate-800">Verifikasi PIN</h3>
                <p class="mb-4 text-sm text-slate-500">Masukkan PIN payslip 6 digit Anda.</p>
                <form @submit.prevent="verifyPin">
                    <input v-model="pin" type="password" inputmode="numeric" maxlength="6"
                           class="mb-3 w-full rounded-lg border-none bg-slate-100 px-3 py-3 text-center text-lg tracking-widest text-slate-800 focus:outline-none focus:ring-2 focus:ring-hijau-500"
                           placeholder="• • • • • •" required />
                    <button class="w-full rounded-full bg-hijau-600 py-3 text-sm font-semibold text-white shadow-sm active:bg-hijau-700 disabled:opacity-50" :disabled="verifying">
                        {{ verifying ? 'Memverifikasi...' : 'Verifikasi' }}
                    </button>
                </form>
            </div>
        </div>

        <div v-else class="mt-3 pb-24">
            <div v-if="loading" class="py-10 text-center text-sm text-slate-400">Memuat...</div>
            <div v-else-if="!rows.length" class="py-10 text-center text-sm text-slate-400">Belum ada slip gaji.</div>
            <div v-else class="space-y-3">
                <RouterLink v-for="row in rows" :key="row.id" :to="`/payslip/${row.id}`"
                            class="block rounded-xl bg-white p-4 shadow-sm active:bg-slate-50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-800">{{ row.periode?.nama || '-' }}</div>
                            <div class="mt-0.5 text-xs text-slate-500">{{ row.periode?.bulan }}/{{ row.periode?.tahun }}</div>
                        </div>
                        <span class="text-slate-400">›</span>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-center text-xs">
                        <div>
                            <div class="text-slate-500">Pendapatan</div>
                            <div class="mt-0.5 font-semibold text-emerald-700">{{ rupiah(row.gaji_bruto) }}</div>
                        </div>
                        <div>
                            <div class="text-slate-500">Potongan</div>
                            <div class="mt-0.5 font-semibold text-rose-600">{{ rupiah(row.total_potongan) }}</div>
                        </div>
                        <div>
                            <div class="text-slate-500">Bersih</div>
                            <div class="mt-0.5 font-semibold text-hijau-700">{{ rupiah(row.gaji_netto) }}</div>
                        </div>
                    </div>
                </RouterLink>
            </div>
        </div>
    </template>

    <!-- ==================== DESKTOP ==================== -->
    <template v-else>
        <PageHeader title="Slip Gaji" subtitle="Riwayat slip gaji Anda" />

        <template v-if="!pinVerified">
            <div class="mx-auto max-w-sm py-12">
                <div class="card p-6 text-center">
                    <div class="mb-4 text-4xl">🔒</div>
                    <h3 class="mb-2 font-semibold text-slate-700">Verifikasi PIN</h3>
                    <p class="mb-4 text-sm text-slate-500">Masukkan PIN payslip Anda untuk melihat slip gaji.</p>
                    <form @submit.prevent="verifyPin">
                        <input v-model="pin" type="password" class="input mb-3 text-center" placeholder="PIN" maxlength="6" required />
                        <button class="btn-hijau w-full" :disabled="verifying">{{ verifying ? 'Memverifikasi...' : 'Verifikasi' }}</button>
                    </form>
                </div>
            </div>
        </template>
        <template v-else>
            <DataTable :columns="columns" :rows="rows" :loading="loading">
                <template #actions="{ row }">
                    <RouterLink :to="`/payslip/${row.id}`" class="btn-ghost btn-sm">Detail</RouterLink>
                </template>
            </DataTable>
            <Pagination :meta="meta" @go="(p) => { page = p; load(); }" />
        </template>
    </template>
</template>
