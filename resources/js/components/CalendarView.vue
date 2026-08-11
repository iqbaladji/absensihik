<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    events: { type: Array, default: () => [] },
    month: Number,
    year: Number,
});

const emit = defineEmits(['change']);

const currentMonth = ref(props.month ?? new Date().getMonth());
const currentYear = ref(props.year ?? new Date().getFullYear());

watch(() => [props.month, props.year], ([m, y]) => {
    if (m !== undefined) currentMonth.value = m;
    if (y !== undefined) currentYear.value = y;
});

const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

const days = computed(() => {
    const first = new Date(currentYear.value, currentMonth.value, 1);
    const last = new Date(currentYear.value, currentMonth.value + 1, 0);
    const startDay = first.getDay();
    const result = [];
    for (let i = 0; i < startDay; i++) result.push(null);
    for (let d = 1; d <= last.getDate(); d++) result.push(d);
    return result;
});

function eventForDay(d) {
    if (!d) return null;
    const dateStr = `${currentYear.value}-${String(currentMonth.value + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    return props.events.find((e) => e.date === dateStr || e.tanggal === dateStr);
}

function eventColor(ev) {
    if (!ev) return '';
    const t = (ev.type || ev.tipe || '').toLowerCase();
    if (['hadir', 'tepat_waktu'].includes(t)) return 'bg-emerald-100 text-emerald-700';
    if (['cuti', 'izin'].includes(t)) return 'bg-amber-100 text-amber-700';
    if (['alpha', 'absen'].includes(t)) return 'bg-rose-100 text-rose-700';
    if (['libur', 'weekend'].includes(t)) return 'bg-slate-100 text-slate-500';
    if (['terlambat'].includes(t)) return 'bg-amber-100 text-amber-700';
    return 'bg-brand-50 text-brand-700';
}

function prev() {
    if (currentMonth.value === 0) { currentMonth.value = 11; currentYear.value--; }
    else currentMonth.value--;
    emit('change', { month: currentMonth.value, year: currentYear.value });
}

function next() {
    if (currentMonth.value === 11) { currentMonth.value = 0; currentYear.value++; }
    else currentMonth.value++;
    emit('change', { month: currentMonth.value, year: currentYear.value });
}
</script>

<template>
    <div class="card-pad">
        <div class="mb-4 flex items-center justify-between">
            <button class="btn-ghost btn-sm" @click="prev">&lsaquo;</button>
            <span class="text-sm font-semibold text-slate-700">{{ monthNames[currentMonth] }} {{ currentYear }}</span>
            <button class="btn-ghost btn-sm" @click="next">&rsaquo;</button>
        </div>
        <div class="grid grid-cols-7 gap-px text-center text-xs">
            <div v-for="d in dayNames" :key="d" class="py-2 font-semibold text-slate-500">{{ d }}</div>
            <div v-for="(day, i) in days" :key="i" class="min-h-[36px] rounded-lg p-1">
                <template v-if="day">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm"
                         :class="eventColor(eventForDay(day))"
                         :title="eventForDay(day)?.label || ''">
                        {{ day }}
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
