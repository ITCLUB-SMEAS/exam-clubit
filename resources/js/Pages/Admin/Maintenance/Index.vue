<template>
    <Head>
        <title>Maintenance Mode - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Maintenance Mode</h5>
                    </div>
                    <div class="card-body">
                        <!-- Status -->
                        <div class="text-center mb-4">
                            <div v-if="isDown" class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                <h5>Sistem Sedang OFFLINE</h5>
                                <p class="mb-0">Siswa tidak dapat mengakses sistem.</p>
                            </div>
                            <div v-else class="alert alert-success">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <h5>Sistem ONLINE</h5>
                                <p class="mb-0">Semua pengguna dapat mengakses sistem.</p>
                            </div>
                        </div>

                        <form @submit.prevent="toggle">
                            <div v-if="!isDown">
                                <div class="mb-3">
                                    <label class="form-label">Pesan Maintenance (opsional)</label>
                                    <textarea v-model="form.message" class="form-control" rows="2" 
                                        placeholder="Sistem sedang dalam pemeliharaan..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Secret Bypass (opsional)</label>
                                    <input v-model="form.secret" type="text" class="form-control" 
                                        placeholder="secret-key-untuk-bypass">
                                    <small class="text-muted">Akses: /secret-key-untuk-bypass untuk bypass maintenance</small>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button v-if="isDown" type="submit" class="btn btn-success btn-lg" :disabled="form.processing">
                                    <i class="fas fa-power-off me-2"></i>Aktifkan Sistem
                                </button>
                                <button v-else type="submit" class="btn btn-danger btn-lg" :disabled="form.processing">
                                    <i class="fas fa-power-off me-2"></i>Nonaktifkan Sistem
                                </button>
                            </div>
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
    isDown: Boolean,
    settings: Object,
});

const form = useForm({
    message: props.settings?.message || '',
    secret: props.settings?.secret || '',
});

const toggle = () => {
    form.post(route('admin.maintenance.toggle'));
};
</script>
