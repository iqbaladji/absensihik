<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import AppLayout from './layouts/AppLayout.vue';
import Toaster from './components/Toaster.vue';

const route = useRoute();
const isPublic = computed(() => route.meta.public === true);
</script>

<template>
    <Toaster />
    <router-view v-if="isPublic" />
    <AppLayout v-else>
        <router-view />
    </AppLayout>
</template>
