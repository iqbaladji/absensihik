<script setup>
import { tanggalJam } from '../util';

defineProps({
    logs: { type: Array, default: () => [] },
});

function statusColor(status) {
    const s = (status || '').toLowerCase();
    if (['disetujui', 'approved'].includes(s)) return 'bg-emerald-500';
    if (['ditolak', 'rejected'].includes(s)) return 'bg-rose-500';
    return 'bg-amber-400';
}
</script>

<template>
    <div v-if="logs.length" class="space-y-0">
        <div v-for="(log, i) in logs" :key="i" class="relative flex gap-3 pb-6 last:pb-0">
            <div class="flex flex-col items-center">
                <div class="h-3 w-3 rounded-full" :class="statusColor(log.status)"></div>
                <div v-if="i < logs.length - 1" class="w-px flex-1 bg-slate-200"></div>
            </div>
            <div class="-mt-0.5">
                <div class="text-sm font-medium text-slate-700">{{ log.approver_name || log.approver?.name || '-' }}</div>
                <div class="text-xs text-slate-500">{{ log.status }} &middot; {{ tanggalJam(log.acted_at || log.created_at) }}</div>
                <div v-if="log.catatan" class="mt-1 text-xs text-slate-500">{{ log.catatan }}</div>
            </div>
        </div>
    </div>
    <div v-else class="text-sm text-slate-400">Belum ada riwayat approval.</div>
</template>
