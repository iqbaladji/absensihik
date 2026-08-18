<script setup>
import { RouterLink } from 'vue-router';

const items = [
    { to: '/', label: 'Home', icon: 'home' },
    { to: '/pengumuman', label: 'Pengumuman', icon: 'chat' },
    { to: '/profil', label: 'Profil', icon: 'user' },
];
</script>

<template>
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white pb-safe lg:hidden">
        <div class="mx-auto flex max-w-md items-stretch justify-around">
            <RouterLink
                v-for="it in items"
                :key="it.to"
                :to="it.to"
                class="flex flex-1 flex-col items-center gap-1 py-2.5 text-xs text-slate-500"
                active-class="text-hijau-700 font-semibold"
            >
                <svg v-if="it.icon === 'home'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
                <svg v-else-if="it.icon === 'chat'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5z"/></svg>
                <span>{{ it.label }}</span>
            </RouterLink>
        </div>
    </nav>
</template>

<style scoped>
.pb-safe { padding-bottom: env(safe-area-inset-bottom, 0); }
</style>
