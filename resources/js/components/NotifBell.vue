<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import api from '../api';

const count = ref(0);
const open = ref(false);
const items = ref([]);
const loading = ref(false);
let timer = null;

async function fetchCount() {
    try {
        const { data } = await api.get('/notifikasi/unread-count');
        count.value = data.count || 0;
    } catch (_) {}
}

async function fetchRecent() {
    if (loading.value) return;
    loading.value = true;
    try {
        const { data } = await api.get('/notifikasi', { params: { per_page: 10 } });
        items.value = data.data || [];
    } catch (_) {}
    loading.value = false;
}

function toggle() {
    open.value = !open.value;
    if (open.value) fetchRecent();
}

async function markRead(notif) {
    if (!notif.read_at) {
        try { await api.post(`/notifikasi/${notif.id}/read`); } catch (_) {}
        notif.read_at = new Date().toISOString();
        if (count.value > 0) count.value--;
    }
}

async function markAllRead() {
    try { await api.post('/notifikasi/read-all'); } catch (_) {}
    count.value = 0;
    items.value.forEach((n) => { if (!n.read_at) n.read_at = new Date().toISOString(); });
}

onMounted(() => {
    fetchCount();
    timer = setInterval(fetchCount, 60000);
});
onUnmounted(() => { if (timer) clearInterval(timer); });
</script>

<template>
    <div class="relative">
        <button class="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100" @click="toggle">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
            <span v-if="count > 0" class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ count > 99 ? '99+' : count }}</span>
        </button>

        <div v-if="open" class="absolute right-0 top-full z-50 mt-1 w-80 rounded-xl border border-slate-200 bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                <span class="text-sm font-semibold text-slate-700">Notifikasi</span>
                <button v-if="count > 0" class="text-xs text-brand-600 hover:underline" @click="markAllRead">Tandai semua dibaca</button>
            </div>
            <div class="max-h-80 overflow-y-auto">
                <div v-if="loading" class="p-4 text-center text-sm text-slate-400">Memuat...</div>
                <div v-else-if="!items.length" class="p-4 text-center text-sm text-slate-400">Tidak ada notifikasi</div>
                <button
                    v-for="n in items"
                    :key="n.id"
                    class="block w-full px-4 py-3 text-left text-sm hover:bg-slate-50"
                    :class="{ 'bg-brand-50/40': !n.read_at }"
                    @click="markRead(n)"
                >
                    <div class="font-medium text-slate-700" :class="{ 'font-semibold': !n.read_at }">{{ n.judul }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ n.pesan }}</div>
                </button>
            </div>
        </div>

        <div v-if="open" class="fixed inset-0 z-40" @click="open = false"></div>
    </div>
</template>
