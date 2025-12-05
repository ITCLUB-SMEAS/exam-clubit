<template>
    <Head>
        <title>Riwayat Login - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mt-5">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body text-center">
                        <h3>{{ stats.total_today }}</h3>
                        <small>Login Hari Ini</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-success text-white">
                    <div class="card-body text-center">
                        <h3>{{ stats.success_today }}</h3>
                        <small>Berhasil</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-danger text-white">
                    <div class="card-body text-center">
                        <h3>{{ stats.failed_today }}</h3>
                        <small>Gagal</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-history me-2"></i>Riwayat Login</h5>
                <div class="d-flex gap-2">
                    <select v-model="filterType" @change="applyFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="">Semua Tipe</option>
                        <option value="admin">Admin</option>
                        <option value="student">Siswa</option>
                    </select>
                    <select v-model="filterStatus" @change="applyFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="">Semua Status</option>
                        <option value="success">Berhasil</option>
                        <option value="failed">Gagal</option>
                        <option value="blocked">Diblokir</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Tipe</th>
                                <th>User ID</th>
                                <th>Status</th>
                                <th>Device</th>
                                <th>Browser</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="histories.data.length === 0">
                                <td colspan="7" class="text-center py-4 text-muted">Tidak ada data</td>
                            </tr>
                            <tr v-for="h in histories.data" :key="h.id">
                                <td><small>{{ formatDate(h.created_at) }}</small></td>
                                <td>
                                    <span :class="h.user_type === 'admin' ? 'badge bg-primary' : 'badge bg-info'">
                                        {{ h.user_type }}
                                    </span>
                                </td>
                                <td>{{ h.user_id }}</td>
                                <td>
                                    <span :class="statusBadge(h.status)">{{ h.status }}</span>
                                </td>
                                <td><i :class="deviceIcon(h.device)"></i> {{ h.device }}</td>
                                <td>{{ h.browser }}</td>
                                <td><code>{{ h.ip_address }}</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white" v-if="histories.last_page > 1">
                <nav>
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <li v-for="link in histories.links" :key="link.label" 
                            :class="['page-item', { active: link.active, disabled: !link.url }]">
                            <Link v-if="link.url" :href="link.url" class="page-link" v-html="link.label"></Link>
                            <span v-else class="page-link" v-html="link.label"></span>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    histories: Object,
    filters: Object,
    stats: Object,
});

const filterType = ref(props.filters.user_type || '');
const filterStatus = ref(props.filters.status || '');

const applyFilter = () => {
    router.get(route('admin.login-history.index'), {
        user_type: filterType.value || undefined,
        status: filterStatus.value || undefined,
    }, { preserveState: true });
};

const formatDate = (date) => new Date(date).toLocaleString('id-ID');

const statusBadge = (status) => ({
    'success': 'badge bg-success',
    'failed': 'badge bg-danger',
    'blocked': 'badge bg-dark',
}[status] || 'badge bg-secondary');

const deviceIcon = (device) => ({
    'Desktop': 'fa fa-desktop',
    'Mobile': 'fa fa-mobile-alt',
    'Tablet': 'fa fa-tablet-alt',
}[device] || 'fa fa-question');
</script>
