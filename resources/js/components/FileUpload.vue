<script setup>
import { ref } from 'vue';

const props = defineProps({
    accept: { type: String, default: '.pdf,.jpg,.jpeg,.png' },
    maxSizeMb: { type: Number, default: 5 },
    label: { type: String, default: 'Pilih File' },
});

const emit = defineEmits(['selected']);
const fileName = ref('');
const error = ref('');

function onFileChange(e) {
    error.value = '';
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > props.maxSizeMb * 1024 * 1024) {
        error.value = `Ukuran file maks ${props.maxSizeMb} MB`;
        return;
    }
    fileName.value = file.name;
    emit('selected', file);
}
</script>

<template>
    <div>
        <label class="btn-ghost cursor-pointer text-center">
            {{ label }}
            <input type="file" class="hidden" :accept="accept" @change="onFileChange" />
        </label>
        <span v-if="fileName" class="ml-2 text-sm text-slate-600">{{ fileName }}</span>
        <p v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</p>
    </div>
</template>
