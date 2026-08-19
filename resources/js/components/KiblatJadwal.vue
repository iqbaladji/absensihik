<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';

const emit = defineEmits(['close']);

const loading = ref(true);
const error = ref('');
const coords = ref(null);
const city = ref('');
const timings = ref(null);
const qiblaBearing = ref(null);
const heading = ref(null);
const orientationSupported = ref(false);
const orientationGranted = ref(false);
let orientationHandler = null;

const jam5 = ['Fajr', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'];
const labelId = { Fajr: 'Subuh', Dhuhr: 'Dzuhur', Asr: 'Ashar', Maghrib: 'Maghrib', Isha: 'Isya' };
const emojiJam = { Fajr: '🌅', Dhuhr: '☀️', Asr: '🌤️', Maghrib: '🌇', Isha: '🌙' };

const nowMins = ref(new Date().getHours() * 60 + new Date().getMinutes());
let tickId = null;

function parseHM(hm) {
    if (!hm) return null;
    const [h, m] = hm.split(':').map(Number);
    return h * 60 + m;
}

const jadwalHariIni = computed(() => {
    if (!timings.value) return [];
    return jam5.map((k) => ({
        key: k,
        nama: labelId[k],
        emoji: emojiJam[k],
        jam: timings.value[k] || '-',
        mins: parseHM(timings.value[k]),
    }));
});

const sholatBerikutnya = computed(() => {
    const list = jadwalHariIni.value.filter((j) => j.mins != null);
    if (!list.length) return null;
    const upcoming = list.find((j) => j.mins > nowMins.value);
    return upcoming || null;
});

const qiblaRotasi = computed(() => {
    if (qiblaBearing.value == null) return 0;
    // If we have live heading, rotate arrow relative to device orientation.
    if (heading.value != null) return qiblaBearing.value - heading.value;
    return qiblaBearing.value;
});

async function loadData(lat, lng) {
    try {
        const timingsRes = await fetch(`https://api.aladhan.com/v1/timings?latitude=${lat}&longitude=${lng}&method=20`);
        const timingsJson = await timingsRes.json();
        timings.value = timingsJson.data?.timings || null;
        city.value = timingsJson.data?.meta?.timezone || '';

        const qiblaRes = await fetch(`https://api.aladhan.com/v1/qibla/${lat}/${lng}`);
        const qiblaJson = await qiblaRes.json();
        qiblaBearing.value = qiblaJson.data?.direction ?? null;
    } catch (e) {
        error.value = 'Gagal mengambil data dari server. Cek koneksi internet.';
    } finally {
        loading.value = false;
    }
}

function detectLocation() {
    if (!navigator.geolocation) {
        error.value = 'Perangkat tidak mendukung GPS.';
        loading.value = false;
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            coords.value = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            loadData(pos.coords.latitude, pos.coords.longitude);
        },
        (err) => {
            error.value = 'Izin lokasi ditolak. Aktifkan lokasi lalu coba lagi.';
            loading.value = false;
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 },
    );
}

function handleOrientation(e) {
    // webkitCompassHeading (iOS Safari) is 0..360 with 0 = North
    // alpha (Android/most) needs conversion
    if (typeof e.webkitCompassHeading === 'number') {
        heading.value = e.webkitCompassHeading;
    } else if (e.alpha != null) {
        heading.value = 360 - e.alpha;
    }
}

async function enableCompass() {
    if (typeof DeviceOrientationEvent === 'undefined') {
        orientationSupported.value = false;
        return;
    }
    if (typeof DeviceOrientationEvent.requestPermission === 'function') {
        try {
            const state = await DeviceOrientationEvent.requestPermission();
            if (state !== 'granted') return;
        } catch (_) { return; }
    }
    orientationGranted.value = true;
    orientationHandler = handleOrientation;
    window.addEventListener('deviceorientationabsolute', orientationHandler, true);
    window.addEventListener('deviceorientation', orientationHandler, true);
}

onMounted(() => {
    detectLocation();
    orientationSupported.value = typeof DeviceOrientationEvent !== 'undefined';
    tickId = setInterval(() => { nowMins.value = new Date().getHours() * 60 + new Date().getMinutes(); }, 30000);
});

onUnmounted(() => {
    if (orientationHandler) {
        window.removeEventListener('deviceorientationabsolute', orientationHandler, true);
        window.removeEventListener('deviceorientation', orientationHandler, true);
    }
    if (tickId) clearInterval(tickId);
});
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 sm:items-center" @click.self="emit('close')">
        <div class="w-full max-w-sm rounded-t-3xl bg-white shadow-xl sm:rounded-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h3 class="font-semibold text-slate-800">Kiblat & Jadwal Sholat</h3>
                <button class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100" @click="emit('close')" aria-label="Tutup">&#10005;</button>
            </div>

            <div class="px-5 py-4">
                <div v-if="loading" class="py-8 text-center text-sm text-slate-400">Mendeteksi lokasi...</div>
                <div v-else-if="error" class="rounded-lg bg-rose-50 p-3 text-sm text-rose-700">
                    {{ error }}
                    <button class="mt-2 block text-xs font-semibold text-rose-800 underline" @click="loading = true; error = ''; detectLocation()">Coba lagi</button>
                </div>

                <template v-else>
                    <!-- Location + qibla compass -->
                    <div class="rounded-2xl bg-gradient-to-br from-hijau-50 to-hijau-100 p-4 text-center">
                        <div class="text-[11px] uppercase tracking-wide text-hijau-700">Arah Kiblat</div>
                        <div class="mx-auto mt-2 flex h-32 w-32 items-center justify-center rounded-full border-4 border-hijau-600 bg-white shadow-inner">
                            <div class="relative h-24 w-24">
                                <!-- Kaaba emoji at center as reference -->
                                <div class="absolute inset-0 flex items-center justify-center text-slate-300 text-xs font-semibold">N</div>
                                <div class="absolute inset-0 flex items-center justify-center" :style="{ transform: `rotate(${qiblaRotasi}deg)`, transition: 'transform 0.3s ease' }">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-hijau-700" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 L15 12 H13 V22 H11 V12 H9 Z"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 text-sm font-semibold text-hijau-800">{{ qiblaBearing != null ? Math.round(qiblaBearing) + '°' : '—' }} dari Utara</div>
                        <div class="text-[11px] text-slate-500">
                            <template v-if="orientationGranted">Panah menunjuk ke Kiblat, ikuti panah.</template>
                            <template v-else-if="orientationSupported">
                                <button class="mt-1 rounded-full bg-hijau-600 px-3 py-1 text-xs font-semibold text-white" @click="enableCompass">Aktifkan Kompas Live</button>
                            </template>
                            <template v-else>Arahkan HP ke Utara, ikuti arah panah.</template>
                        </div>
                    </div>

                    <!-- Prayer schedule -->
                    <div class="mt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-slate-700">Jadwal Sholat Hari Ini</h4>
                            <span v-if="city" class="text-[11px] text-slate-500">{{ city }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <div v-for="s in jadwalHariIni" :key="s.key"
                                 class="flex items-center justify-between rounded-lg px-3 py-2"
                                 :class="sholatBerikutnya?.key === s.key ? 'bg-hijau-50 border border-hijau-200' : 'bg-slate-50'">
                                <div class="flex items-center gap-2">
                                    <span>{{ s.emoji }}</span>
                                    <span class="text-sm font-medium text-slate-700">{{ s.nama }}</span>
                                    <span v-if="sholatBerikutnya?.key === s.key" class="rounded-full bg-hijau-600 px-2 py-0.5 text-[10px] font-semibold text-white">Berikutnya</span>
                                </div>
                                <span class="text-sm font-bold tabular-nums" :class="sholatBerikutnya?.key === s.key ? 'text-hijau-800' : 'text-slate-700'">{{ s.jam }}</span>
                            </div>
                        </div>
                        <div v-if="coords" class="mt-3 text-center text-[10px] text-slate-400">
                            {{ coords.lat.toFixed(4) }}, {{ coords.lng.toFixed(4) }} · Sumber: aladhan.com (Kemenag)
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
