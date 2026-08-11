<script setup>
import { ref, onMounted } from 'vue';
import api, { errMsg } from '../../api';
import { toastErr } from '../../toast';
import { rupiah } from '../../util';
import PageHeader from '../../components/PageHeader.vue';
import DataTable from '../../components/DataTable.vue';
import Pagination from '../../components/Pagination.vue';

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
    <PageHeader title="Slip Gaji" subtitle="Riwayat slip gaji Anda" />

    <template v-if="!pinVerified">
        <div class="mx-auto max-w-sm py-12">
            <div class="card p-6 text-center">
                <div class="mb-4 text-4xl">🔒</div>
                <h3 class="mb-2 font-semibold text-slate-700">Verifikasi PIN</h3>
                <p class="mb-4 text-sm text-slate-500">Masukkan PIN payslip Anda untuk melihat slip gaji.</p>
                <form @submit.prevent="verifyPin">
                    <input v-model="pin" type="password" class="input mb-3 text-center" placeholder="PIN" maxlength="6" required />
                    <button class="btn-primary w-full" :disabled="verifying">{{ verifying ? 'Memverifikasi...' : 'Verifikasi' }}</button>
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
