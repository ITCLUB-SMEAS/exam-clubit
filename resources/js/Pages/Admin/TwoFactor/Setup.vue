<template>
    <Head>
        <title>Setup 2FA - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fa fa-qrcode me-2"></i>Setup Two-Factor Authentication</h5>
                    </div>
                    <div class="card-body text-center">
                        <p class="text-muted">Scan QR code dengan aplikasi authenticator (Google Authenticator, Authy, dll)</p>
                        
                        <div class="my-4">
                            <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(qrCodeUrl)" alt="QR Code" class="img-fluid">
                        </div>

                        <div class="mb-4">
                            <p class="small text-muted">Atau masukkan kode manual:</p>
                            <code class="bg-light p-2 rounded">{{ secret }}</code>
                        </div>

                        <form @submit.prevent="enable">
                            <div class="mb-3">
                                <label class="form-label">Masukkan kode 6 digit dari authenticator:</label>
                                <input v-model="form.code" type="text" class="form-control text-center" maxlength="6" placeholder="000000" required style="font-size: 1.5rem; letter-spacing: 0.5rem;">
                            </div>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fa fa-check me-1"></i>Verifikasi & Aktifkan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    secret: String,
    qrCodeUrl: String,
});

const form = useForm({ code: '' });

const enable = () => {
    form.post(route('admin.two-factor.enable'));
};
</script>
