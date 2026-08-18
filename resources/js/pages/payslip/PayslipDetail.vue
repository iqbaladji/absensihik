<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import api, { errMsg } from '../../api';
import { toastErr } from '../../toast';
import { rupiah } from '../../util';
import { useAuth } from '../../stores/auth';
import PageHeader from '../../components/PageHeader.vue';
import MobileSubHeader from '../../components/MobileSubHeader.vue';

const route = useRoute();
const auth = useAuth();
const isPegawaiMobile = computed(() => auth.roleSlug === 'pegawai');
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
        <MobileSubHeader v-if="isPegawaiMobile" title="Slip Gaji" to="/payslip">
            <template #right>
                <button class="rounded-full p-1.5 hover:bg-white/10" @click="download" aria-label="Download PDF">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                </button>
            </template>
        </MobileSubHeader>

        <PageHeader v-if="!isPegawaiMobile" :title="`Slip Gaji — ${item.periode?.nama || ''}`">
            <template #actions>
                <RouterLink to="/payslip" class="btn-ghost">Kembali</RouterLink>
                <button class="btn-hijau" @click="download">Download PDF</button>
            </template>
        </PageHeader>

        <div class="card mt-3 p-4 pb-24 sm:p-6">
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

            <div class="mt-6 rounded-lg p-4 text-center" :class="isPegawaiMobile ? 'bg-hijau-50' : 'bg-brand-50'">
                <div class="text-sm text-slate-500">Gaji Bersih</div>
                <div class="text-2xl font-bold sm:text-3xl" :class="isPegawaiMobile ? 'text-hijau-700' : 'text-brand-700'">{{ rupiah(item.gaji_bersih) }}</div>
            </div>
        </div>
    </template>
</template>
