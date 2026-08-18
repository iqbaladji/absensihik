<script setup>
import { ref, onMounted, computed } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../../api';
import { useAuth } from '../../stores/auth';
import MobileSubHeader from '../../components/MobileSubHeader.vue';
import PageHeader from '../../components/PageHeader.vue';

const auth = useAuth();
const isPegawaiMobile = computed(() => auth.roleSlug === 'pegawai');
const sisaCuti = ref(null);

const items = computed(() => [
    {
        to: '/cuti-tahunan',
        label: 'Cuti Tahunan',
        desc: 'Cuti umum 12 hari/tahun',
        detail: sisaCuti.value != null ? `Sisa saldo: ${sisaCuti.value} hari` : '',
        visible: auth.can('cuti_tahunan', 'R'),
        icon: 'palm',
    },
    {
        to: '/cuti-besar',
        label: 'Cuti Besar',
        desc: 'Untuk masa kerja min. 6 tahun',
        detail: '',
        visible: auth.can('cuti_besar', 'R'),
        icon: 'medal',
    },
    {
        to: '/cuti-melahirkan',
        label: 'Cuti Melahirkan',
        desc: 'Cuti melahirkan / keguguran',
        detail: '',
        visible: auth.can('cuti_melahirkan', 'R'),
        icon: 'baby',
    },
    {
        to: '/block-leave',
        label: 'Block Leave',
        desc: 'Cuti wajib 5 hari berturut-turut',
        detail: '',
        visible: auth.can('block_leave', 'R'),
        icon: 'lock',
    },
].filter((it) => it.visible));

onMounted(async () => {
    try {
        const { data } = await api.get('/cuti-tahunan-saldo');
        sisaCuti.value = data.data?.sisa ?? data.sisa ?? null;
    } catch (_) {}
});
</script>

<template>
    <div v-if="isPegawaiMobile" class="lg:hidden">
        <MobileSubHeader title="Cuti" to="/" />
        <div class="mt-3 space-y-2.5 pb-24">
            <RouterLink
                v-for="it in items"
                :key="it.to"
                :to="it.to"
                class="flex items-center gap-3 rounded-xl bg-slate-100 p-4 shadow-sm active:bg-slate-200"
            >
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-hijau-100 text-hijau-700">
                    <svg v-if="it.icon === 'palm'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22V10m0 0c-2-2-6-2-8 0m8 0c2-2 6-2 8 0m-8 0c0-3-2-5-5-5m5 5c0-3 2-5 5-5"/></svg>
                    <svg v-else-if="it.icon === 'medal'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3l3 6 3-6M12 14a5 5 0 100-10 5 5 0 000 10zm-3 7l3-3 3 3-1-5h-4l-1 5z"/></svg>
                    <svg v-else-if="it.icon === 'baby'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 9c.5.5 1.5 1 3 1s2.5-.5 3-1M10 8h.01M14 8h.01M6 21c0-4 3-6 6-6s6 2 6 6"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="4" y="8" width="16" height="12" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 8V6a4 4 0 118 0v2M12 13v3"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-slate-800">{{ it.label }}</div>
                    <div class="text-xs text-slate-500">{{ it.desc }}</div>
                    <div v-if="it.detail" class="mt-0.5 text-xs font-medium text-hijau-700">{{ it.detail }}</div>
                </div>
                <span class="text-slate-400">›</span>
            </RouterLink>
        </div>
    </div>

    <div v-else>
        <PageHeader title="Cuti" subtitle="Pilih jenis cuti" />
        <div class="grid gap-4 sm:grid-cols-2">
            <RouterLink
                v-for="it in items"
                :key="it.to"
                :to="it.to"
                class="card-pad flex items-center gap-4 hover:border-hijau-500"
            >
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-hijau-100 text-hijau-700">
                    <svg v-if="it.icon === 'palm'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22V10m0 0c-2-2-6-2-8 0m8 0c2-2 6-2 8 0m-8 0c0-3-2-5-5-5m5 5c0-3 2-5 5-5"/></svg>
                    <svg v-else-if="it.icon === 'medal'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3l3 6 3-6M12 14a5 5 0 100-10 5 5 0 000 10zm-3 7l3-3 3 3-1-5h-4l-1 5z"/></svg>
                    <svg v-else-if="it.icon === 'baby'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 9c.5.5 1.5 1 3 1s2.5-.5 3-1M10 8h.01M14 8h.01M6 21c0-4 3-6 6-6s6 2 6 6"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="4" y="8" width="16" height="12" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 8V6a4 4 0 118 0v2M12 13v3"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-slate-800">{{ it.label }}</div>
                    <div class="text-sm text-slate-500">{{ it.desc }}</div>
                    <div v-if="it.detail" class="mt-0.5 text-sm font-medium text-hijau-700">{{ it.detail }}</div>
                </div>
            </RouterLink>
        </div>
    </div>
</template>
