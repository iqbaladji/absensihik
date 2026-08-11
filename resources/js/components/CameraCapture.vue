<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const emit = defineEmits(['captured']);
const video = ref(null);
const canvas = ref(null);
const stream = ref(null);
const captured = ref(null);
const error = ref('');
const ready = ref(false);

async function startCamera() {
    error.value = '';
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
            audio: false,
        });
        if (video.value) {
            video.value.srcObject = stream.value;
            video.value.onloadedmetadata = () => { ready.value = true; };
        }
    } catch (e) {
        error.value = 'Tidak dapat mengakses kamera. Pastikan izin kamera telah diberikan.';
    }
}

function capture() {
    if (!video.value || !canvas.value) return;
    const ctx = canvas.value.getContext('2d');
    canvas.value.width = video.value.videoWidth;
    canvas.value.height = video.value.videoHeight;
    ctx.drawImage(video.value, 0, 0);
    const dataUrl = canvas.value.toDataURL('image/jpeg', 0.8);
    captured.value = dataUrl;
    emit('captured', dataUrl);
    stopCamera();
}

function retake() {
    captured.value = null;
    startCamera();
}

function stopCamera() {
    if (stream.value) {
        stream.value.getTracks().forEach((t) => t.stop());
        stream.value = null;
    }
    ready.value = false;
}

onMounted(startCamera);
onUnmounted(stopCamera);
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-black">
        <div v-if="error" class="flex h-60 items-center justify-center p-4 text-center text-sm text-rose-400">
            {{ error }}
            <button class="ml-2 text-brand-100 underline" @click="startCamera">Coba lagi</button>
        </div>
        <template v-else-if="!captured">
            <video ref="video" autoplay playsinline muted class="h-60 w-full object-cover"></video>
            <div class="flex justify-center bg-slate-900 p-3">
                <button
                    class="flex h-14 w-14 items-center justify-center rounded-full border-4 border-white bg-white/20 transition hover:bg-white/30"
                    :disabled="!ready"
                    @click="capture"
                >
                    <div class="h-10 w-10 rounded-full bg-white"></div>
                </button>
            </div>
        </template>
        <template v-else>
            <img :src="captured" class="h-60 w-full object-cover" alt="Foto selfie" />
            <div class="flex justify-center bg-slate-900 p-3">
                <button class="btn-ghost btn-sm" @click="retake">Ambil Ulang</button>
            </div>
        </template>
        <canvas ref="canvas" class="hidden"></canvas>
    </div>
</template>
