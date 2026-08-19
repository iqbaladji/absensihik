<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import api, { errMsg } from '../../api';
import { toastErr } from '../../toast';
import { jam } from '../../util';
import { useAuth } from '../../stores/auth';
import PageHeader from '../../components/PageHeader.vue';
import StatCard from '../../components/StatCard.vue';
import NotifBell from '../../components/NotifBell.vue';

const auth = useAuth();
const stats = ref({});
const loading = ref(true);
const today = ref(null);
const week = ref([]);
const sisaCuti = ref(null);
const nowTs = ref(Date.now());
let tickerId = null;

const durasiKerja = computed(() => {
    if (!today.value?.waktu_masuk) return null;
    const masuk = new Date(today.value.waktu_masuk).getTime();
    const akhir = today.value.waktu_pulang ? new Date(today.value.waktu_pulang).getTime() : nowTs.value;
    const diff = Math.max(0, akhir - masuk);
    const jamT = Math.floor(diff / 3600000);
    const menit = Math.floor((diff % 3600000) / 60000);
    const detik = Math.floor((diff % 60000) / 1000);
    return {
        live: !today.value.waktu_pulang,
        text: today.value.waktu_pulang
            ? `${jamT} jam ${menit} menit`
            : `${String(jamT).padStart(2, '0')}:${String(menit).padStart(2, '0')}:${String(detik).padStart(2, '0')}`,
    };
});

const namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const namaBulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

const tanggalHariIni = computed(() => {
    const d = new Date();
    return `${namaHari[d.getDay()]}, ${d.getDate()} ${namaBulan[d.getMonth()]} ${d.getFullYear()}`;
});

const salam = computed(() => {
    const h = new Date().getHours();
    if (h < 11) return { text: 'Selamat pagi', emoji: '☀️' };
    if (h < 15) return { text: 'Selamat siang', emoji: '🌤️' };
    if (h < 18) return { text: 'Selamat sore', emoji: '🌇' };
    return { text: 'Selamat malam', emoji: '🌙' };
});

const quotes = [
    'Rezeki dijemput, bukan ditunggu. Semangat! 💪',
    'Konsisten itu kunci — sedikit tapi rutin. 🌱',
    'Jangan lupa senyum, energi positif menular. 😊',
    'Kerja pintar > kerja keras. Fokus & tenang. ✍️',
    'Hari produktif dimulai dari niat yang baik. ✨',
    'Waktu adalah amanah, gunakan sebaik-baiknya. ⏱️',
    'Sekecil apa pun langkahmu hari ini, tetap berharga. 🎯',
    'Alhamdulillah masih diberi kesempatan berkarya. 🤲',
    'Semangat! Rezekimu di ujung ikhtiar. 🚀',
    'Nikmati proses, hasil akan mengikuti. 🌤️',
];

const quoteHariIni = computed(() => {
    const d = new Date();
    const idx = (d.getFullYear() * 366 + d.getMonth() * 31 + d.getDate()) % quotes.length;
    return quotes[idx];
});

const canApproveAny = ['izin', 'cuti_tahunan', 'cuti_besar', 'cuti_melahirkan', 'block_leave', 'lembur', 'wfh', 'wfa', 'dinas_luar']
    .some((m) => auth.can(m, 'A'));

const menus = computed(() => {
    const items = [
        { label: 'Lembur', to: '/lembur', icon: 'clock' },
        { label: 'Presensi', to: '/presensi', icon: 'check-list' },
        canApproveAny ? { label: 'Approval', to: '/approval', icon: 'check-circle' } : null,
        { label: 'Cuti', to: '/cuti', icon: 'palm' },
        { label: 'WFH', to: '/wfh', icon: 'home-heart' },
        { label: 'Izin', to: '/izin', icon: 'briefcase' },
        { label: 'Slip Gaji', to: '/payslip', icon: 'money' },
    ].filter(Boolean);
    return items;
});

onMounted(async () => {
    try {
        const { data } = await api.get('/dashboard');
        stats.value = data.data || data;
        today.value = stats.value.presensi_hari_ini;
    } catch (e) {
        toastErr(errMsg(e));
    } finally {
        loading.value = false;
    }
    try {
        const { data } = await api.get('/presensi/riwayat', { params: { per_page: 7 } });
        week.value = (data.data || []).slice(0, 7);
    } catch (_) {}
    try {
        const { data } = await api.get('/cuti-tahunan-saldo');
        sisaCuti.value = data.data?.sisa ?? data.sisa ?? null;
    } catch (_) {}
    tickerId = setInterval(() => { nowTs.value = Date.now(); }, 1000);
});

onUnmounted(() => { if (tickerId) clearInterval(tickerId); });

function shortHari(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return { tgl: d.getDate(), hari: namaHari[d.getDay()] };
}

function jamKerja(row) {
    if (!row.waktu_masuk || !row.waktu_pulang) return '-';
    const m = new Date(row.waktu_masuk);
    const p = new Date(row.waktu_pulang);
    const diff = Math.max(0, p - m);
    const jamT = Math.floor(diff / 3600000);
    const menit = Math.floor((diff % 3600000) / 60000);
    return `${String(jamT).padStart(2, '0')}:${String(menit).padStart(2, '0')}`;
}
</script>

<template>
    <!-- ==================== MOBILE (pegawai) ==================== -->
    <div class="lg:hidden -mx-3 -mt-4 sm:-mx-4 sm:-mt-6">
        <!-- Green curved header -->
        <div class="relative bg-gradient-to-b from-hijau-500 to-hijau-700 px-5 pb-16 pt-6 text-white"
             style="border-bottom-left-radius: 40% 24px; border-bottom-right-radius: 40% 24px;">
            <div class="flex items-start justify-between">
                <div class="min-w-0">
                    <div class="text-sm text-white/85">{{ salam.text }}, {{ salam.emoji }}</div>
                    <div class="truncate text-lg font-semibold leading-tight">{{ (auth.user?.name || '-').split(' ')[0] }}</div>
                    <div class="text-xs text-white/80">{{ auth.user?.role?.nama }}<span v-if="auth.user?.kantor"> · {{ auth.user.kantor.nama }}</span></div>
                </div>
                <NotifBell variant="light" />
            </div>
        </div>

        <!-- Floating today card -->
        <div class="-mt-12 px-4">
            <div class="rounded-2xl bg-white p-4 shadow-lg">
                <div class="text-center text-sm font-semibold text-hijau-700">{{ tanggalHariIni }}</div>
                <div class="mt-3 grid grid-cols-2 overflow-hidden rounded-xl">
                    <div class="bg-hijau-500/90 py-3 text-center text-white">
                        <div class="text-xs opacity-90">Jam Masuk</div>
                        <div class="mt-0.5 text-xl font-bold">{{ today?.waktu_masuk ? jam(today.waktu_masuk) : '--:--' }}</div>
                    </div>
                    <div class="bg-hijau-600 py-3 text-center text-white">
                        <div class="text-xs opacity-90">Jam Pulang</div>
                        <div class="mt-0.5 text-xl font-bold">{{ today?.waktu_pulang ? jam(today.waktu_pulang) : '--:--' }}</div>
                    </div>
                </div>
                <div v-if="durasiKerja" class="mt-3 flex items-center justify-center gap-2 rounded-lg py-2 text-sm"
                     :class="durasiKerja.live ? 'bg-hijau-50 text-hijau-700' : 'bg-slate-100 text-slate-600'">
                    <span class="relative flex h-2 w-2" v-if="durasiKerja.live">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-hijau-500 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-hijau-600"></span>
                    </span>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span class="font-semibold tabular-nums">{{ durasiKerja.live ? 'Sudah bekerja' : 'Total kerja' }} {{ durasiKerja.text }}</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <RouterLink to="/presensi" class="rounded-lg py-2.5 text-center text-sm font-medium active:opacity-80"
                                :class="today?.waktu_masuk ? 'bg-slate-200 text-slate-500' : 'bg-hijau-600 text-white'">Clock In</RouterLink>
                    <RouterLink to="/presensi" class="rounded-lg py-2.5 text-center text-sm font-medium active:opacity-80"
                                :class="!today?.waktu_masuk || today?.waktu_pulang ? 'bg-slate-200 text-slate-500' : 'bg-hijau-600 text-white'">Clock Out</RouterLink>
                </div>
            </div>
        </div>

        <!-- Saldo Cuti pill -->
        <RouterLink to="/cuti" class="mx-4 mt-5 flex items-center justify-between rounded-xl border border-hijau-100 bg-hijau-50 px-4 py-3 active:bg-hijau-100">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-hijau-600 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22V10m0 0c-2-2-6-2-8 0m8 0c2-2 6-2 8 0m-8 0c0-3-2-5-5-5m5 5c0-3 2-5 5-5"/></svg>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Sisa Cuti Tahun ini</div>
                    <div class="text-sm font-semibold text-slate-800">{{ sisaCuti != null ? `${sisaCuti} Hari` : '—' }}</div>
                </div>
            </div>
            <span class="text-slate-400">›</span>
        </RouterLink>

        <!-- Motivational quote -->
        <div class="mx-4 mt-3 flex items-center gap-3 rounded-xl bg-gradient-to-r from-amber-50 to-amber-100/60 px-4 py-3">
            <div class="text-xl">💡</div>
            <div class="text-xs italic leading-relaxed text-slate-700">{{ quoteHariIni }}</div>
        </div>

        <!-- Menu grid -->
        <div class="mt-4 grid grid-cols-4 gap-3 px-4">
            <RouterLink v-for="m in menus" :key="m.label" :to="m.to" class="flex flex-col items-center gap-1.5">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl border border-slate-200 bg-white shadow-sm">
                    <svg v-if="m.icon === 'clock'" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-hijau-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
                    <svg v-else-if="m.icon === 'check-list'" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-hijau-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h8a2 2 0 012 2v12a2 2 0 01-2 2H9m0 0H7a2 2 0 01-2-2V7a2 2 0 012-2h2m0 0V3m3 6h3M9 13h3m-3 4h3"/></svg>
                    <svg v-else-if="m.icon === 'check-circle'" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-hijau-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 12l3 3 5-6"/></svg>
                    <svg v-else-if="m.icon === 'palm'" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-hijau-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22V10m0 0c-2-2-6-2-8 0m8 0c2-2 6-2 8 0m-8 0c0-3-2-5-5-5m5 5c0-3 2-5 5-5"/></svg>
                    <svg v-else-if="m.icon === 'home-heart'" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-hijau-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
                    <svg v-else-if="m.icon === 'money'" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-hijau-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 10v.01M18 14v.01"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-hijau-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2m-9 0h10a2 2 0 012 2v9a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2z"/></svg>
                </div>
                <span class="text-xs text-slate-600">{{ m.label }}</span>
            </RouterLink>
        </div>

        <!-- Weekly attendance sheet -->
        <div class="mt-8 rounded-t-3xl bg-white px-4 pb-8 pt-3 shadow-inner">
            <div class="mx-auto h-1 w-10 rounded-full bg-slate-300"></div>
            <div class="mt-3 flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-800">Absensi Minggu ini</h3>
                <RouterLink to="/presensi/riwayat" class="text-sm text-slate-500">Lainnya</RouterLink>
            </div>
            <div class="mt-3 space-y-2">
                <div v-if="!week.length" class="rounded-xl bg-hijau-50 py-6 text-center text-sm text-slate-400">Belum ada data.</div>
                <div v-for="row in week" :key="row.id" class="flex items-center gap-3 rounded-xl bg-hijau-50 p-2.5">
                    <div class="flex h-12 w-12 flex-col items-center justify-center rounded-lg bg-hijau-500 text-white">
                        <div class="text-lg font-bold leading-none">{{ shortHari(row.tanggal).tgl }}</div>
                        <div class="text-[10px] leading-tight">{{ shortHari(row.tanggal).hari }}</div>
                    </div>
                    <div class="grid flex-1 grid-cols-3 gap-2 text-center text-xs">
                        <div>
                            <div class="text-slate-500">Jam Masuk</div>
                            <div class="font-semibold text-slate-700">{{ row.waktu_masuk ? jam(row.waktu_masuk) : '-' }}</div>
                        </div>
                        <div>
                            <div class="text-slate-500">Jam Pulang</div>
                            <div class="font-semibold text-slate-700">{{ row.waktu_pulang ? jam(row.waktu_pulang) : '-' }}</div>
                        </div>
                        <div>
                            <div class="text-slate-500">Jam Kerja</div>
                            <div class="font-semibold text-slate-700">{{ jamKerja(row) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== DESKTOP ==================== -->
    <div class="hidden lg:block">
        <PageHeader title="Dashboard" subtitle="Ringkasan kehadiran dan aktivitas Anda" />
        <div v-if="loading" class="py-12 text-center text-slate-400">Memuat...</div>
        <template v-else>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard icon="⏰" label="Status Hari Ini" :value="today?.status || 'Belum Absen'" :tone="today?.status === 'hadir' ? 'green' : 'amber'" />
                <StatCard icon="🏖️" label="Sisa Cuti" :value="stats.sisa_cuti ?? '-'" tone="brand" />
                <StatCard icon="📋" label="Pengajuan Aktif" :value="stats.pengajuan_aktif ?? 0" tone="amber" />
                <StatCard icon="📢" label="Pengumuman Baru" :value="stats.pengumuman_belum_dibaca ?? 0" tone="slate" />
            </div>
            <div v-if="today" class="card mt-6 p-5">
                <h3 class="mb-3 font-semibold text-slate-700">Presensi Hari Ini</h3>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div><div class="text-xs text-slate-500">Jam Masuk</div><div class="text-lg font-semibold text-slate-800">{{ jam(today.waktu_masuk) }}</div></div>
                    <div><div class="text-xs text-slate-500">Jam Pulang</div><div class="text-lg font-semibold text-slate-800">{{ today.waktu_pulang ? jam(today.waktu_pulang) : '-' }}</div></div>
                    <div><div class="text-xs text-slate-500">Tipe</div><div class="text-lg font-semibold text-slate-800">{{ today.tipe_kehadiran || '-' }}</div></div>
                </div>
            </div>
        </template>
    </div>
</template>
