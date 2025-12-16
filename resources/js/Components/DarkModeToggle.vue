<template>
    <button @click="toggleDarkMode" class="btn btn-sm btn-icon shadow-sm me-2" :class="isDark ? 'btn-warning' : 'btn-dark'" aria-label="Toggle Dark Mode" title="Toggle Dark Mode">
        <i v-if="isDark" class="fas fa-sun"></i>
        <i v-else class="fas fa-moon"></i>
    </button>
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
    name: 'DarkModeToggle',
    setup() {
        const isDark = ref(false);

        const toggleDarkMode = () => {
            isDark.value = !isDark.value;
            updateTheme();
        };

        const updateTheme = () => {
            const body = document.body;
            if (isDark.value) {
                body.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                body.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        };

        onMounted(() => {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                isDark.value = true;
                document.body.classList.add('dark');
            } else {
                isDark.value = false;
                document.body.classList.remove('dark');
            }
        });

        return {
            isDark,
            toggleDarkMode
        }
    }
}
</script>
