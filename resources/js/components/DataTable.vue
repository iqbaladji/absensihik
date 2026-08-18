<script setup>
import { get } from '../util';

defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    loading: Boolean,
    empty: { type: String, default: 'Tidak ada data.' },
});

function badgeClass(val) {
    const v = String(val || '').toLowerCase();
    if (['aktif', 'lunas', 'selesai', 'disetujui', 'ditemukan', 'hadir', 'tepat_waktu', 'approved', 'published'].some((s) => v.includes(s))) return 'badge-green';
    if (['nonaktif', 'ditolak', 'batal', 'tidak', 'rejected', 'alpha', 'retracted'].some((s) => v.includes(s))) return 'badge-red';
    if (['diajukan', 'dibuka', 'belum', 'perbaikan', 'pending', 'terlambat', 'draft'].some((s) => v.includes(s))) return 'badge-amber';
    if (['wfh', 'wfa', 'dinas'].some((s) => v.includes(s))) return 'badge-blue';
    return 'badge-gray';
}
</script>

<template>
    <div class="hidden sm:block table-wrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th v-for="c in columns" :key="c.key">{{ c.label }}</th>
                    <th v-if="$slots.actions" class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="loading">
                    <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="py-8 text-center text-slate-400">Memuat...</td>
                </tr>
                <tr v-else-if="!rows.length">
                    <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="py-8 text-center text-slate-400">{{ empty }}</td>
                </tr>
                <tr v-for="(row, i) in rows" v-else :key="row.id ?? i">
                    <td v-for="c in columns" :key="c.key">
                        <slot :name="`cell-${c.key}`" :row="row" :value="get(row, c.key)">
                            <span v-if="c.badge" class="badge" :class="badgeClass(get(row, c.key))">{{ get(row, c.key) ?? '-' }}</span>
                            <span v-else-if="c.format">{{ c.format(get(row, c.key), row) }}</span>
                            <span v-else>{{ get(row, c.key) ?? '-' }}</span>
                        </slot>
                    </td>
                    <td v-if="$slots.actions" class="text-right">
                        <div class="flex justify-end gap-1.5"><slot name="actions" :row="row" /></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="sm:hidden space-y-2">
        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white py-8 text-center text-sm text-slate-400">Memuat...</div>
        <div v-else-if="!rows.length" class="rounded-xl border border-slate-200 bg-white py-8 text-center text-sm text-slate-400">{{ empty }}</div>
        <div v-for="(row, i) in rows" v-else :key="'m-' + (row.id ?? i)" class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            <div class="divide-y divide-slate-100">
                <div v-for="c in columns" :key="c.key" class="flex items-start justify-between gap-3 py-1.5 text-sm">
                    <span class="shrink-0 text-xs font-medium uppercase tracking-wide text-slate-500">{{ c.label }}</span>
                    <span class="text-right text-slate-700">
                        <slot :name="`cell-${c.key}`" :row="row" :value="get(row, c.key)">
                            <span v-if="c.badge" class="badge" :class="badgeClass(get(row, c.key))">{{ get(row, c.key) ?? '-' }}</span>
                            <span v-else-if="c.format">{{ c.format(get(row, c.key), row) }}</span>
                            <span v-else>{{ get(row, c.key) ?? '-' }}</span>
                        </slot>
                    </span>
                </div>
            </div>
            <div v-if="$slots.actions" class="mt-2 flex flex-wrap justify-end gap-1.5 border-t border-slate-100 pt-2">
                <slot name="actions" :row="row" />
            </div>
        </div>
    </div>
</template>
