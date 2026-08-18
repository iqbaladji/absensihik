<script setup>
import MobileSubHeader from './MobileSubHeader.vue';

defineProps({
    title: { type: String, required: true },
    sectionTitle: { type: String, default: 'Riwayat' },
    items: { type: Array, default: () => [] },
    loading: Boolean,
    empty: { type: String, default: 'Belum ada data.' },
    canAdd: { type: Boolean, default: false },
    addLabel: { type: String, default: 'Tambah' },
    itemTitle: { type: Function, required: true },
    itemPeriode: { type: Function, required: true },
    itemAlasan: { type: Function, default: () => '' },
    itemStatus: { type: Function, default: () => 'pending' },
});
const emit = defineEmits(['add', 'click-row']);

function statusPill(s) {
    const v = String(s || '').toLowerCase();
    if (v.includes('setuj') || v.includes('approve')) return { text: 'Approved', cls: 'bg-hijau-600 text-white' };
    if (v.includes('tolak') || v.includes('reject')) return { text: 'Rejected', cls: 'bg-rose-500 text-white' };
    if (v.includes('batal') || v.includes('cancel')) return { text: 'Batal', cls: 'bg-slate-400 text-white' };
    return { text: 'Pending', cls: 'bg-amber-400 text-white' };
}
</script>

<template>
    <div class="lg:hidden">
        <MobileSubHeader :title="title" to="/" />

        <div class="mt-3 flex items-center justify-between px-1">
            <h2 class="text-base font-semibold text-slate-800">{{ sectionTitle }}</h2>
            <button class="rounded-lg p-1.5 text-hijau-600" aria-label="Filter">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M7 12h10M10 18h4"/></svg>
            </button>
        </div>

        <div v-if="loading" class="py-10 text-center text-sm text-slate-400">Memuat...</div>
        <div v-else-if="!items.length" class="py-10 text-center text-sm text-slate-400">{{ empty }}</div>
        <div v-else class="mt-2 space-y-3 pb-24">
            <div v-for="row in items" :key="row.id" class="rounded-xl bg-slate-100 p-3 shadow-sm active:bg-slate-200" @click="emit('click-row', row)">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-amber-400 bg-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-hijau-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4 0-7 2-7 5v1h14v-1c0-3-3-5-7-5z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-hijau-700">{{ itemTitle(row) }}</div>
                        <div class="text-sm text-slate-500">{{ itemPeriode(row) }}</div>
                    </div>
                </div>
                <div class="mt-2 flex items-center justify-between border-t border-slate-200 pt-2">
                    <div class="min-w-0 pr-2 text-xs text-slate-600 truncate">{{ itemAlasan(row) || '—' }}</div>
                    <span class="rounded-md px-4 py-1.5 text-xs font-semibold" :class="statusPill(itemStatus(row)).cls">{{ statusPill(itemStatus(row)).text }}</span>
                </div>
            </div>
        </div>

        <button v-if="canAdd" class="fixed inset-x-4 bottom-20 z-20 rounded-full bg-hijau-600 py-3.5 text-center text-sm font-semibold text-white shadow-lg active:bg-hijau-700" @click="emit('add')">
            {{ addLabel }}
        </button>
    </div>
</template>
