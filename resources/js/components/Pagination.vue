<script setup>
const props = defineProps({ meta: Object });
const emit = defineEmits(['go']);

function go(p) {
    if (p >= 1 && p <= props.meta.last_page && p !== props.meta.current_page) emit('go', p);
}
</script>

<template>
    <div v-if="meta && meta.total > 0" class="flex items-center justify-between px-1 py-3 text-sm text-slate-600">
        <div>
            Menampilkan {{ meta.from }}&ndash;{{ meta.to }} dari {{ meta.total }} data
        </div>
        <div class="flex items-center gap-1">
            <button class="btn-ghost btn-sm" :disabled="meta.current_page <= 1" @click="go(meta.current_page - 1)">&lsaquo; Sebelumnya</button>
            <span class="px-2">Hal. {{ meta.current_page }} / {{ meta.last_page }}</span>
            <button class="btn-ghost btn-sm" :disabled="meta.current_page >= meta.last_page" @click="go(meta.current_page + 1)">Berikutnya &rsaquo;</button>
        </div>
    </div>
</template>
