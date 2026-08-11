<script setup>
import { toasts } from '../toast';
</script>

<template>
    <div class="fixed right-4 top-4 z-[100] flex w-80 flex-col gap-2">
        <transition-group name="toast">
            <div
                v-for="t in toasts"
                :key="t.id"
                class="rounded-lg px-4 py-3 text-sm shadow-lg ring-1"
                :class="t.type === 'error'
                    ? 'bg-rose-50 text-rose-800 ring-rose-200'
                    : 'bg-emerald-50 text-emerald-800 ring-emerald-200'"
            >
                {{ t.message }}
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(20px); }
</style>
