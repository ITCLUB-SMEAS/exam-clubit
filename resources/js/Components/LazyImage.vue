<template>
    <img
        :src="isLoaded ? src : placeholder"
        :alt="alt"
        :class="[className, { 'lazy-loading': !isLoaded }]"
        @load="onLoad"
        loading="lazy"
    />
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    src: { type: String, required: true },
    alt: { type: String, default: '' },
    placeholder: { type: String, default: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"%3E%3Crect fill="%23f0f0f0" width="400" height="300"/%3E%3C/svg%3E' },
    className: { type: String, default: '' },
});

const isLoaded = ref(false);

const onLoad = () => {
    isLoaded.value = true;
};

onMounted(() => {
    // Preload image
    const img = new Image();
    img.src = props.src;
});
</script>

<style scoped>
.lazy-loading {
    filter: blur(5px);
    transition: filter 0.3s;
}
</style>
