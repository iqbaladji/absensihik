<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api, { errMsg } from '../../api';
import { toastErr } from '../../toast';
import { rupiah } from '../../util';
import PageHeader from '../../components/PageHeader.vue';

const route = useRoute();
const item = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const { data } = await api.get(`/payslip/${route.params.id}`);
        item.value = data.data;
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        loading.value = false;
    }
});

function download() {
    window.open(`/api/payslip/${route.params.id}/download?token=${localStorage.getItem('absensihik_token')}`, '_blank');
}
</script>

<template>
    <div v-if="loading" class="py-12 text-center text-slate-400">Memuat...</div>
    <template v-else-if="item">
        <PageHeader :title="`Slip Gaji — ${item.periode?.nama || ''}`">
            <template #actions>
                <RouterLink to="/payslip" class="btn-ghost">Kembali</RouterLink>
                <button class="btn-primary" @click="download">Download PDF</button>
            </template>
        </PageHeader>

        <div class="card p-6">
            <div class="mb-6 border-b border-slate-100 pb-4">
                <div class="text-sm text-slate-500">{{ item.user?.name }} &middot; {{ item.user?.nip }}</div>
                <div class="text-sm text-slate-500">{{ item.periode?.nama }} — {{ item.periode?.bulan }}/{{ item.periode?.tahun }}</div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <h4 class="mb-3 font-semibold text-emerald-700">Pendapatan</h4>
                    <div class="space-y-2">
                        <div v-for="k in (item.komponen || []).filter((c) => c.tipe === 'pendapatan')" :key="k.id" class="flex justify-between text-sm">
                            <span class="text-slate-600">{{ k.nama }}</span>
                            <span class="font-medium">{{ rupiah(k.nominal) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold">
                            <span>Total Pendapatan</span>
                            <span class="text-emerald-700">{{ rupiah(item.total_pendapatan) }}</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="mb-3 font-semibold text-rose-700">Potongan</h4>
                    <div class="space-y-2">
                        <div v-for="k in (item.komponen || []).filter((c) => c.tipe === 'potongan')" :key="k.id" class="flex justify-between text-sm">
                            <span class="text-slate-600">{{ k.nama }}</span>
                            <span class="font-medium">{{ rupiah(k.nominal) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold">
                            <span>Total Potongan</span>
                            <span class="text-rose-700">{{ rupiah(item.total_potongan) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-lg bg-brand-50 p-4 text-center">
                <div class="text-sm text-slate-500">Gaji Bersih</div>
                <div class="text-3xl font-bold text-brand-700">{{ rupiah(item.gaji_bersih) }}</div>
            </div>
        </div>
    </template>
</template>
