<script setup>
defineProps({ status: String });

const map = {
    diajukan: 'badge-amber',
    pending: 'badge-amber',
    menunggu: 'badge-amber',
    disetujui: 'badge-green',
    approved: 'badge-green',
    selesai: 'badge-green',
    ditolak: 'badge-red',
    rejected: 'badge-red',
    batal: 'badge-red',
    hadir: 'badge-green',
    tepat_waktu: 'badge-green',
    terlambat: 'badge-amber',
    alpha: 'badge-red',
    wfh: 'badge-blue',
    wfa: 'badge-blue',
    dinas_luar: 'badge-blue',
    draft: 'badge-gray',
};

function cls(s) {
    if (!s) return 'badge-gray';
    return map[s.toLowerCase()] || 'badge-gray';
}
</script>

<template>
    <span class="badge" :class="cls(status)">{{ status || '-' }}</span>
</template>
