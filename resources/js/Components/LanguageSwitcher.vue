<template>
    <div class="dropdown" ref="dropdownRef">
        <button
            @click="isOpen = !isOpen"
            class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1"
            type="button"
            :aria-expanded="isOpen"
        >
            <span>{{ currentLanguage.flag }}</span>
            <span class="d-none d-sm-inline">{{ currentLanguage.name }}</span>
        </button>

        <ul 
            class="dropdown-menu dropdown-menu-end" 
            :class="{ show: isOpen }"
            :style="isOpen ? 'display: block' : ''"
        >
            <li v-for="(lang, code) in languages" :key="code">
                <button
                    @click="switchLanguage(code)"
                    class="dropdown-item d-flex align-items-center gap-2"
                    :class="{ active: currentLocale === code }"
                >
                    <span>{{ lang.flag }}</span>
                    <span>{{ lang.name }}</span>
                </button>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const isOpen = ref(false);
const dropdownRef = ref(null);

const languages = {
    id: { name: 'Indonesia', flag: '🇮🇩' },
    en: { name: 'English', flag: '🇬🇧' },
    zh: { name: '中文', flag: '🇨🇳' },
    ja: { name: '日本語', flag: '🇯🇵' },
};

const currentLocale = computed(() => page.props.locale || 'id');
const currentLanguage = computed(() => languages[currentLocale.value] || languages.id);

const switchLanguage = (locale) => {
    isOpen.value = false;
    router.post('/language/switch', { locale }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => window.location.reload(),
    });
};

const handleClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>
