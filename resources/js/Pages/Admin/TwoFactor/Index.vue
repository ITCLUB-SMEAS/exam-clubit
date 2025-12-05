<template>
    <Head>
        <title>Two-Factor Authentication - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fa fa-shield-alt me-2"></i>Two-Factor Authentication</h5>
                    </div>
                    <div class="card-body">
                        <div v-if="enabled" class="text-center">
                            <div class="mb-4">
                                <i class="fa fa-check-circle text-success" style="font-size: 4rem;"></i>
                            </div>
                            <h4 class="text-success">2FA Aktif</h4>
                            <p class="text-muted">Akun Anda dilindungi dengan Two-Factor Authentication.</p>
                            
                            <div class="mt-4">
                                <h6>Recovery Codes</h6>
                                <p class="text-muted small">Simpan kode ini di tempat aman. Gunakan jika kehilangan akses ke authenticator.</p>
                                <div class="bg-light p-3 rounded mb-3">
                                    <code v-for="code in recoveryCodes" :key="code" class="d-block">{{ code }}</code>
                                </div>
                                <button @click="regenerateCodes" class="btn btn-outline-warning btn-sm me-2">
                                    <i class="fa fa-sync me-1"></i>Generate Ulang
                                </button>
                                <button @click="showDisableModal = true" class="btn btn-outline-danger btn-sm">
                                    <i class="fa fa-times me-1"></i>Nonaktifkan 2FA
                                </button>
                            </div>
                        </div>
                        
                        <div v-else class="text-center">
                            <div class="mb-4">
                                <i class="fa fa-shield-alt text-warning" style="font-size: 4rem;"></i>
                            </div>
                            <h4>2FA Belum Aktif</h4>
                            <p class="text-muted">Aktifkan Two-Factor Authentication untuk keamanan tambahan.</p>
                            <Link :href="route('admin.two-factor.setup')" class="btn btn-primary">
                                <i class="fa fa-lock me-1"></i>Aktifkan 2FA
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disable Modal -->
        <div v-if="showDisableModal" class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nonaktifkan 2FA</h5>
                        <button @click="showDisableModal = false" class="btn-close"></button>
                    </div>
                    <form @submit.prevent="disable2FA">
                        <div class="modal-body">
                            <p>Masukkan password untuk mengkonfirmasi:</p>
                            <input v-model="disableForm.password" type="password" class="form-control" placeholder="Password" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" @click="showDisableModal = false" class="btn btn-secondary">Batal</button>
                            <button type="submit" class="btn btn-danger" :disabled="disableForm.processing">Nonaktifkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    enabled: Boolean,
    recoveryCodes: Array,
});

const showDisableModal = ref(false);
const disableForm = useForm({ password: '' });

const disable2FA = () => {
    disableForm.post(route('admin.two-factor.disable'), {
        onSuccess: () => showDisableModal.value = false,
    });
};

const regenerateCodes = () => {
    if (confirm('Kode lama akan tidak berlaku. Lanjutkan?')) {
        useForm({ password: prompt('Masukkan password:') }).post(route('admin.two-factor.regenerate'));
    }
};
</script>
