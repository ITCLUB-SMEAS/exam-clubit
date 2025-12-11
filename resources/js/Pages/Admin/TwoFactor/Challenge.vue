<template>
    <Head>
        <title>Verifikasi 2FA - Aplikasi Ujian Online</title>
    </Head>
    <div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="card border-0 shadow" style="width: 100%; max-width: 400px;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="fas fa-shield-alt text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Verifikasi 2FA</h4>
                    <p class="text-muted small">Masukkan kode dari aplikasi authenticator</p>
                </div>

                <form @submit.prevent="verify">
                    <div class="mb-3" v-if="!useRecovery">
                        <input v-model="form.code" type="text" class="form-control form-control-lg text-center" maxlength="6" placeholder="000000" required autofocus style="letter-spacing: 0.5rem;">
                    </div>
                    <div class="mb-3" v-else>
                        <input v-model="form.code" type="text" class="form-control" placeholder="Recovery Code" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">
                        <i class="fas fa-check me-1"></i>Verifikasi
                    </button>
                </form>

                <div class="text-center mt-3">
                    <button @click="useRecovery = !useRecovery" class="btn btn-link btn-sm">
                        {{ useRecovery ? 'Gunakan kode authenticator' : 'Gunakan recovery code' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const useRecovery = ref(false);
const form = useForm({ code: '', recovery: false });

const verify = () => {
    form.recovery = useRecovery.value;
    form.post(route('admin.two-factor.verify'));
};
</script>
