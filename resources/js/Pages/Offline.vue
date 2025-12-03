<template>
    <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
        <div class="text-center">
            <div class="mb-6">
                <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                        d="M18.364 5.636a9 9 0 010 12.728m-3.536-3.536a4 4 0 010-5.656m-8.486 8.486a9 9 0 010-12.728m3.536 3.536a4 4 0 010 5.656M12 12v.01" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Tidak Ada Koneksi Internet</h1>
            <p class="text-gray-600 mb-6">Periksa koneksi internet Anda dan coba lagi.</p>
            <button @click="retry" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Coba Lagi
            </button>
        </div>
    </div>
</template>

<script setup>
const retry = () => window.location.reload();
</script>
