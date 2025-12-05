<template>
    <Head>
        <title>Cleanup Data - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mt-5">
        <div class="row">
            <div class="col-md-8">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-broom me-2"></i>Cleanup Data Lama</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Perhatian!</strong> Data yang dihapus tidak dapat dikembalikan.
                        </div>

                        <form @submit.prevent="cleanup">
                            <div class="mb-4">
                                <label class="form-label">Hapus data lebih lama dari:</label>
                                <select v-model="form.days" class="form-select">
                                    <option value="30">30 hari</option>
                                    <option value="60">60 hari</option>
                                    <option value="90">90 hari</option>
                                    <option value="180">180 hari</option>
                                    <option value="365">365 hari</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-danger" :disabled="form.processing">
                                <i class="fas fa-trash me-1"></i>Jalankan Cleanup
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Statistik Data</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Activity Logs</span>
                                <span>
                                    <span class="badge bg-primary">{{ stats.activity_logs.total }}</span>
                                    <span class="badge bg-warning" v-if="stats.activity_logs.old">{{ stats.activity_logs.old }} lama</span>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Login History</span>
                                <span>
                                    <span class="badge bg-primary">{{ stats.login_history.total }}</span>
                                    <span class="badge bg-warning" v-if="stats.login_history.old">{{ stats.login_history.old }} lama</span>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Violations</span>
                                <span>
                                    <span class="badge bg-primary">{{ stats.violations.total }}</span>
                                    <span class="badge bg-warning" v-if="stats.violations.old">{{ stats.violations.old }} lama</span>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Backups</span>
                                <span class="badge bg-primary">{{ stats.backups.total }}</span>
                            </li>
                        </ul>
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
    stats: Object,
});

const form = useForm({
    days: 90,
});

const cleanup = () => {
    if (confirm('Yakin ingin menghapus data lama? Tindakan ini tidak dapat dibatalkan.')) {
        form.post(route('admin.cleanup.run'));
    }
};
</script>
