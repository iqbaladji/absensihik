<script setup>
import { ref, onMounted, computed } from 'vue';
import api, { errMsg } from '../../api';
import { useAuth } from '../../stores/auth';
import { toastOk, toastErr } from '../../toast';
import { jam } from '../../util';
import PageHeader from '../../components/PageHeader.vue';
import CameraCapture from '../../components/CameraCapture.vue';
import GpsStatus from '../../components/GpsStatus.vue';

const auth = useAuth();
const today = ref(null);
const loading = ref(true);
const submitting = ref(false);
const foto = ref(null);
const gps = ref(null);
const tipe = ref('');
const showTipeModal = ref(false);

const kantor = computed(() => auth.user?.kantor || {});
const sudahMasuk = computed(() => !!today.value?.waktu_masuk);
const sudahPulang = computed(() => !!today.value?.waktu_pulang);

onMounted(loadToday);

async function loadToday() {
    loading.value = true;
    try {
        const { data } = await api.get('/presensi/today');
        today.value = data.data;
    } catch (_) {}
    finally { loading.value = false; }
}

function onGpsUpdate(data) {
    gps.value = data;
}

function onFotoCaptured(dataUrl) {
    foto.value = dataUrl;
}

async function clockIn() {
    if (!foto.value) return toastErr('Ambil foto selfie terlebih dahulu.');
    if (!gps.value) return toastErr('Menunggu data lokasi...');
    submitting.value = true;
    try {
        const payload = {
            latitude: gps.value.latitude,
            longitude: gps.value.longitude,
            akurasi: gps.value.accuracy,
            foto: foto.value,
            device_id: navigator.userAgent.slice(0, 50),
            device_model: navigator.platform,
        };
        if (tipe.value) payload.tipe = tipe.value;
        const { data } = await api.post('/presensi/clock-in', payload);
        toastOk(data.message || 'Clock In berhasil');
        loadToday();
    } catch (e) {
        const body = e?.response?.data;
        if (body?.data?.needs_choice) {
            showTipeModal.value = true;
            submitting.value = false;
            return;
        }
        toastErr(errMsg(e));
    } finally {
        submitting.value = false;
    }
}

async function clockOut() {
    if (!foto.value) return toastErr('Ambil foto selfie terlebih dahulu.');
    if (!gps.value) return toastErr('Menunggu data lokasi...');
    submitting.value = true;
    try {
        const payload = {
            latitude: gps.value.latitude,
            longitude: gps.value.longitude,
            akurasi: gps.value.accuracy,
            foto: foto.value,
        };
        const { data } = await api.post('/presensi/clock-out', payload);
        toastOk(data.message || 'Clock Out berhasil');
        loadToday();
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        submitting.value = false;
    }
}

function selectTipe(t) {
    tipe.value = t;
    showTipeModal.value = false;
    clockIn();
}
</script>

<template>
    <PageHeader title="Presensi" subtitle="Clock In / Clock Out hari ini" />

    <div v-if="loading" class="py-12 text-center text-slate-400">Memuat...</div>
    <template v-else>
        <div v-if="sudahMasuk" class="card mb-6 p-5">
            <h3 class="mb-2 font-semibold text-slate-700">Status Hari Ini</h3>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><span class="text-xs text-slate-500">Masuk</span><div class="text-lg font-bold">{{ jam(today.waktu_masuk) }}</div></div>
                <div><span class="text-xs text-slate-500">Pulang</span><div class="text-lg font-bold">{{ today.waktu_pulang ? jam(today.waktu_pulang) : '-' }}</div></div>
                <div><span class="text-xs text-slate-500">Status</span><div class="text-lg"><span class="badge" :class="today.status_kehadiran === 'tepat_waktu' ? 'badge-green' : 'badge-amber'">{{ today.status_kehadiran }}</span></div></div>
            </div>
        </div>

        <div v-if="!sudahPulang" class="grid gap-6 lg:grid-cols-2">
            <div>
                <h3 class="mb-3 font-semibold text-slate-700">Foto Selfie</h3>
                <CameraCapture @captured="onFotoCaptured" />
            </div>
            <div>
                <h3 class="mb-3 font-semibold text-slate-700">Lokasi</h3>
                <GpsStatus
                    :office-latitude="kantor.latitude"
                    :office-longitude="kantor.longitude"
                    :radius-meter="kantor.radius_meter || 100"
                    @update="onGpsUpdate"
                />
                <div class="mt-6 flex gap-3">
                    <button v-if="!sudahMasuk" class="btn-hijau flex-1 py-3 text-base shadow-sm" :disabled="submitting || !foto || !gps" @click="clockIn">
                        {{ submitting ? 'Memproses...' : 'Clock In' }}
                    </button>
                    <button v-if="sudahMasuk" class="btn-warning flex-1 py-3 text-base shadow-sm" :disabled="submitting || !foto || !gps" @click="clockOut">
                        {{ submitting ? 'Memproses...' : 'Clock Out' }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="sudahPulang" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-5 text-center">
            <p class="text-emerald-700">Anda sudah menyelesaikan presensi hari ini.</p>
        </div>
    </template>

    <div v-if="showTipeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-slate-800">Pilih Tipe Kehadiran</h3>
            <p class="mt-2 text-sm text-slate-600">Anda berada di luar radius kantor. Pilih tipe kehadiran:</p>
            <div class="mt-4 space-y-2">
                <button class="btn-hijau w-full" @click="selectTipe('dinas_luar')">Dinas Luar</button>
                <button class="btn-hijau w-full" @click="selectTipe('wfh')">WFH</button>
                <button class="btn-hijau w-full" @click="selectTipe('wfa')">WFA</button>
            </div>
            <button class="btn-ghost mt-3 w-full" @click="showTipeModal = false">Batal</button>
        </div>
    </div>
</template>
