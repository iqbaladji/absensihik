<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../stores/auth';
import { navGroups } from '../nav';
import NotifBell from '../components/NotifBell.vue';

const auth = useAuth();
const router = useRouter();
const sidebarOpen = ref(false);
const expanded = ref({});

const visibleGroups = computed(() =>
    navGroups
        .map((g) => ({
            ...g,
            items: g.items.filter((it) => !it.modul || auth.can(it.modul)),
        }))
        .filter((g) => g.items.length),
);

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
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 text-lg font-bold">A</div>
                <div>
                    <div class="text-sm font-bold leading-tight">AbsensiHIK</div>
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
            <header class="sticky top-0 z-20 flex items-center justify-between border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur lg:px-8">
                <button class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = true">&#9776;</button>
                <div class="hidden text-sm text-slate-500 lg:block">Kehadiran, Workforce &amp; Komunikasi Internal</div>
                <div class="flex items-center gap-3">
                    <NotifBell />
                    <div class="text-right">
                        <div class="text-sm font-semibold text-slate-700">{{ auth.user?.name }}</div>
                        <div class="text-[11px] text-slate-500">
                            {{ auth.user?.role?.nama }}<span v-if="auth.user?.kantor"> &middot; {{ auth.user.kantor.nama }}</span>
                        </div>
                    </div>
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 font-semibold text-brand-700">
                        {{ (auth.user?.name || '?').charAt(0) }}
                    </div>
                    <button class="btn-ghost btn-sm" @click="doLogout">Keluar</button>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>
