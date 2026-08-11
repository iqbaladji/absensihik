<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    officeLatitude: Number,
    officeLongitude: Number,
    radiusMeter: { type: Number, default: 100 },
});

const emit = defineEmits(['update']);

const lat = ref(null);
const lng = ref(null);
const accuracy = ref(null);
const distance = ref(null);
const status = ref('loading');
const error = ref('');
let watchId = null;

function haversine(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a = Math.sin(dLat / 2) ** 2 + Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) * Math.sin(dLon / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

const inRadius = computed(() => distance.value !== null && distance.value <= props.radiusMeter);

function onPosition(pos) {
    lat.value = pos.coords.latitude;
    lng.value = pos.coords.longitude;
    accuracy.value = Math.round(pos.coords.accuracy);
    status.value = 'ok';

    if (props.officeLatitude && props.officeLongitude) {
        distance.value = Math.round(haversine(lat.value, lng.value, props.officeLatitude, props.officeLongitude));
    }

    emit('update', {
        latitude: lat.value,
        longitude: lng.value,
        accuracy: accuracy.value,
        distance: distance.value,
        inRadius: inRadius.value,
    });
}

function onError(err) {
    status.value = 'error';
    if (err.code === 1) error.value = 'Izin lokasi ditolak';
    else if (err.code === 2) error.value = 'Lokasi tidak tersedia';
    else error.value = 'Timeout mendapatkan lokasi';
}

onMounted(() => {
    if (!navigator.geolocation) {
        status.value = 'error';
        error.value = 'Geolocation tidak didukung browser';
        return;
    }
    watchId = navigator.geolocation.watchPosition(onPosition, onError, {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0,
    });
});

onUnmounted(() => {
    if (watchId !== null) navigator.geolocation.clearWatch(watchId);
});
</script>

<template>
    <div class="flex flex-wrap items-center gap-3 rounded-lg border p-3 text-sm"
         :class="status === 'error' ? 'border-rose-200 bg-rose-50' : inRadius ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
        <div v-if="status === 'loading'" class="flex items-center gap-2 text-slate-500">
            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
            Mendapatkan lokasi...
        </div>
        <div v-else-if="status === 'error'" class="text-rose-700">{{ error }}</div>
        <template v-else>
            <span class="badge" :class="inRadius ? 'badge-green' : 'badge-amber'">
                {{ inRadius ? 'Dalam Radius' : 'Di Luar Radius' }}
            </span>
            <span v-if="distance !== null" class="text-slate-600">Jarak: {{ distance }} m</span>
            <span class="text-slate-500">Akurasi: {{ accuracy }} m</span>
        </template>
    </div>
</template>
