<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../stores/auth';
import { navGroups } from '../nav';
import NotifBell from '../components/NotifBell.vue';
import BottomNav from '../components/BottomNav.vue';

const auth = useAuth();
const router = useRouter();
const sidebarOpen = ref(false);
const expanded = ref({});

const isPegawaiMobile = computed(() => auth.roleSlug === 'pegawai');

const visibleGroups = computed(() => {
    const isAdmin = auth.roleSlug === 'administrator';
    return navGroups
        .map((g) => ({
            ...g,
            items: g.items.filter((it) => {
                if (isAdmin && it.personal) return false;
                return !it.modul || auth.can(it.modul);
            }),
        }))
        .filter((g) => g.items.length);
});

function toggle(label) {
    expanded.value[label] = !expanded.value[label];
}

async function doLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="flex min-h-full">
        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full overflow-y-auto bg-brand-700 text-slate-100 transition-transform lg:translate-x-0"
            :class="{ 'translate-x-0': sidebarOpen }"
        >
            <div class="flex items-center gap-2 px-5 py-4">
                <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-lg bg-white/95">
                    <img src="/hikp-logo.png" alt="HIKP" class="h-8 w-auto object-contain" />
                </div>
                <div>
                    <div class="text-sm font-bold leading-tight tracking-wide">SIHADIR</div>
                    <div class="text-[11px] text-slate-300">BPRS HIK Parahyangan</div>
                </div>
            </div>

            <nav class="px-3 pb-8">
                <div v-for="group in visibleGroups" :key="group.label" class="mt-4">
                    <div class="px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ group.label }}</div>
                    <div class="mt-1 space-y-0.5">
                        <template v-for="item in group.items" :key="item.label">
                            <div v-if="item.children">
                                <button
                                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm text-slate-200 hover:bg-white/10"
                                    @click="toggle(item.label)"
                                >
                                    <span class="flex items-center gap-2.5"><span>{{ item.icon }}</span>{{ item.label }}</span>
                                    <span class="text-xs">{{ expanded[item.label] ? '▾' : '▸' }}</span>
                                </button>
                                <div v-show="expanded[item.label]" class="ml-4 mt-0.5 space-y-0.5 border-l border-white/10 pl-2">
                                    <RouterLink
                                        v-for="child in item.children"
                                        :key="child.to"
                                        :to="child.to"
                                        class="block rounded-md px-3 py-1.5 text-[13px] text-slate-300 hover:bg-white/10"
                                        active-class="bg-white/15 text-white"
                                        @click="sidebarOpen = false"
                                    >{{ child.label }}</RouterLink>
                                </div>
                            </div>
                            <RouterLink
                                v-else
                                :to="item.to"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-200 hover:bg-white/10"
                                active-class="bg-white/15 text-white"
                                @click="sidebarOpen = false"
                            >
                                <span>{{ item.icon }}</span>{{ item.label }}
                            </RouterLink>
                        </template>
                    </div>
                </div>
            </nav>
        </aside>

        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="sidebarOpen = false"></div>

        <div class="flex min-h-full flex-1 flex-col lg:pl-64">
            <header
                class="sticky top-0 z-20 flex items-center justify-between gap-2 border-b border-slate-200 bg-white/90 px-3 py-2.5 backdrop-blur sm:px-4 sm:py-3 lg:px-8"
                :class="{ 'hidden lg:flex': isPegawaiMobile }"
            >
                <button class="-ml-1 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = true" aria-label="Buka menu">&#9776;</button>
                <div class="hidden text-sm text-slate-500 lg:block">Kehadiran, Workforce &amp; Komunikasi Internal</div>
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <NotifBell />
                    <div class="hidden text-right sm:block">
                        <div class="truncate text-sm font-semibold text-slate-700">{{ auth.user?.name }}</div>
                        <div class="truncate text-[11px] text-slate-500">
                            {{ auth.user?.role?.nama }}<span v-if="auth.user?.kantor"> &middot; {{ auth.user.kantor.nama }}</span>
                        </div>
                    </div>
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 font-semibold text-brand-700">
                        {{ (auth.user?.name || '?').charAt(0) }}
                    </div>
                    <button class="btn-ghost btn-sm hidden sm:inline-flex" @click="doLogout">Keluar</button>
                    <button class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 sm:hidden" @click="doLogout" aria-label="Keluar" title="Keluar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h5a2 2 0 012 2v1"/></svg>
                    </button>
                </div>
            </header>

            <main
                class="flex-1 px-3 py-4 sm:px-4 sm:py-6 lg:px-8"
                :class="{ 'pb-24 lg:pb-6': isPegawaiMobile }"
            >
                <slot />
            </main>
        </div>
        <BottomNav v-if="isPegawaiMobile" />
    </div>
</template>
