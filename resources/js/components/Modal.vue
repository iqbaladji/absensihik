<script setup>
defineProps({ title: String, wide: Boolean });
const emit = defineEmits(['close']);
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4 sm:p-6">
        <div class="my-8 w-full rounded-xl bg-white shadow-xl" :class="wide ? 'max-w-3xl' : 'max-w-lg'">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                <h3 class="font-semibold text-slate-800">{{ title }}</h3>
                <button class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100" @click="emit('close')">&#10005;</button>
            </div>
            <div class="px-5 py-4"><slot /></div>
            <div v-if="$slots.footer" class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3.5">
                <slot name="footer" />
            </div>
        </div>
    </div>
</template>
