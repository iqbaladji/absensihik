<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    placeholder: { type: String, default: 'Cari...' },
    filters: { type: Array, default: () => [] },
});

const emit = defineEmits(['search', 'filter']);
const search = ref('');
const filterValues = ref({});

props.filters.forEach((f) => { filterValues.value[f.key] = ''; });

let debounce = null;
watch(search, (v) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => emit('search', v), 300);
});

function onFilter(key, val) {
    filterValues.value[key] = val;
    emit('filter', { ...filterValues.value });
}
</script>

<template>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input v-model="search" type="text" class="input pl-9" :placeholder="placeholder" />
        </div>
        <select
            v-for="f in filters"
            :key="f.key"
            class="select w-full sm:w-auto sm:min-w-[140px]"
            :value="filterValues[f.key]"
            @change="onFilter(f.key, $event.target.value)"
        >
            <option value="">{{ f.label }}</option>
            <option v-for="opt in f.options" :key="opt.value ?? opt" :value="opt.value ?? opt">{{ opt.label ?? opt }}</option>
        </select>
    </div>
</template>
