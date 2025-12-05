<template>
    <Head>
        <title>Backup Database - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa fa-database me-2"></i>Backup Database</h5>
                        <div>
                            <button @click="cleanup" class="btn btn-outline-warning btn-sm me-2">
                                <i class="fa fa-broom me-1"></i>Hapus Backup Lama
                            </button>
                            <button @click="createBackup" class="btn btn-primary btn-sm" :disabled="creating">
                                <i class="fa fa-plus me-1"></i>Buat Backup
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div v-if="backups.length === 0" class="text-center py-5 text-muted">
                            <i class="fa fa-inbox fa-3x mb-3"></i>
                            <p>Belum ada backup.</p>
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama File</th>
                                        <th>Ukuran</th>
                                        <th>Tanggal</th>
                                        <th width="150">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="backup in backups" :key="backup.name">
                                        <td><i class="fa fa-file-archive text-warning me-2"></i>{{ backup.name }}</td>
                                        <td>{{ formatSize(backup.size) }}</td>
                                        <td>{{ formatDate(backup.created_at) }}</td>
                                        <td>
                                            <a :href="route('admin.backup.download', backup.name)" class="btn btn-sm btn-outline-primary me-1">
                                                <i class="fa fa-download"></i>
                                            </a>
                                            <button @click="deleteBackup(backup.name)" class="btn btn-sm btn-outline-danger">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    backups: Array,
});

const creating = ref(false);

const createBackup = () => {
    creating.value = true;
    router.post(route('admin.backup.create'), {}, {
        onFinish: () => creating.value = false,
    });
};

const deleteBackup = (filename) => {
    if (confirm('Hapus backup ini?')) {
        router.delete(route('admin.backup.destroy', filename));
    }
};

const cleanup = () => {
    if (confirm('Hapus semua backup lebih dari 7 hari?')) {
        router.post(route('admin.backup.cleanup'));
    }
};

const formatSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / 1048576).toFixed(2) + ' MB';
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('id-ID');
};
</script>
