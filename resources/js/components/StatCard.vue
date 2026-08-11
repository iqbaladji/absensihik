<script setup>
defineProps({
    label: String,
    value: [String, Number],
    icon: String,
    tone: { type: String, default: 'brand' },
});
const tones = {
    brand: 'bg-brand-50 text-brand-700',
    green: 'bg-emerald-50 text-emerald-700',
    amber: 'bg-amber-50 text-amber-700',
    red: 'bg-rose-50 text-rose-700',
    slate: 'bg-slate-100 text-slate-600',
};
</script>

<template>
    <div class="card-pad flex items-center gap-4">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-xl" :class="tones[tone]">{{ icon }}</div>
        <div class="min-w-0">
            <div class="truncate text-2xl font-bold text-slate-800">{{ value }}</div>
            <div class="text-sm text-slate-500">{{ label }}</div>
        </div>
    </div>
</template>
