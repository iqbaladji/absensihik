<script setup>
import { useRouter } from 'vue-router';

defineProps({
    title: { type: String, required: true },
    to: { type: [String, Object], default: null },
});

const router = useRouter();
function goBack(to) {
    if (to) router.push(to);
    else if (window.history.length > 1) router.back();
    else router.push('/');
}
</script>

<template>
    <div class="lg:hidden -mx-3 -mt-4 sm:-mx-4 sm:-mt-6 sticky top-0 z-10">
        <div class="flex items-center gap-2 bg-hijau-600 px-3 py-3 text-white shadow-sm">
            <button class="rounded-full p-1.5 hover:bg-white/10" @click="goBack(to)" aria-label="Kembali">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <h1 class="flex-1 text-center pr-8 text-base font-semibold">{{ title }}</h1>
            <slot name="right" />
        </div>
    </div>
</template>
