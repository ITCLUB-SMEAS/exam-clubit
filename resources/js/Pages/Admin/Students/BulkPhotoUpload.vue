<template>
    <Head>
        <title>Upload Foto Massal - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-images me-2"></i>Upload Foto Siswa Massal</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Petunjuk:</h6>
                            <ol class="mb-0">
                                <li>Siapkan foto siswa dalam format <strong>JPG, JPEG, PNG, atau WEBP</strong></li>
                                <li>Rename setiap foto dengan <strong>NISN siswa</strong> (contoh: <code>1234567890.jpg</code>)</li>
                                <li>Kompres semua foto ke dalam <strong>file ZIP</strong></li>
                                <li>Upload file ZIP (maksimal 50MB)</li>
                            </ol>
                        </div>

                        <form @submit.prevent="upload">
                            <div class="mb-4">
                                <label class="form-label">File ZIP</label>
                                <input type="file" @change="handleFile" accept=".zip" class="form-control" required>
                                <small class="text-muted">Contoh struktur ZIP: 1234567890.jpg, 0987654321.png, ...</small>
                            </div>

                            <div v-if="form.progress" class="mb-3">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         :style="{width: form.progress.percentage + '%'}">
                                        {{ form.progress.percentage }}%
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <Link href="/admin/students" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Kembali
                                </Link>
                                <button type="submit" class="btn btn-primary" :disabled="form.processing || !form.file">
                                    <i class="fas fa-upload me-1"></i>Upload
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
import { Head, Link, useForm } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';

defineOptions({ layout: LayoutAdmin });

const form = useForm({
    file: null,
});

const handleFile = (e) => {
    form.file = e.target.files[0];
};

const upload = () => {
    form.post('/admin/students/bulk-photo', {
        forceFormData: true,
    });
};
</script>
