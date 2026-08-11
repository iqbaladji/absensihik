<script setup>
defineProps({
    icon: { type: String, default: '\u{1F4ED}' },
    title: { type: String, default: 'Tidak ada data' },
    description: String,
    actionLabel: String,
});
const emit = defineEmits(['action']);
</script>

<template>
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="text-4xl">{{ icon }}</div>
        <h3 class="mt-3 text-lg font-semibold text-slate-700">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-slate-500">{{ description }}</p>
        <button v-if="actionLabel" class="btn-primary mt-4" @click="emit('action')">{{ actionLabel }}</button>
    </div>
</template>
