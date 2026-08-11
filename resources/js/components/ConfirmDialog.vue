<script setup>
defineProps({
    title: { type: String, default: 'Konfirmasi' },
    message: { type: String, default: 'Apakah Anda yakin?' },
    confirmLabel: { type: String, default: 'Ya' },
    cancelLabel: { type: String, default: 'Batal' },
    danger: Boolean,
    loading: Boolean,
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-slate-800">{{ title }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ message }}</p>
            <div class="mt-5 flex justify-end gap-2">
                <button class="btn-ghost" :disabled="loading" @click="emit('cancel')">{{ cancelLabel }}</button>
                <button :class="danger ? 'btn-danger' : 'btn-primary'" :disabled="loading" @click="emit('confirm')">
                    {{ loading ? 'Memproses...' : confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
