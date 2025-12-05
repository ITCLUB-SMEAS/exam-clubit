<template>
    <Head>
        <title>Activity Logs - Admin</title>
    </Head>

    <main class="content">
        <div class="py-4">
            <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                    <li class="breadcrumb-item">
                        <Link href="/admin/dashboard">
                            <i class="fas fa-home"></i>
                        </Link>
                    </li>
                    <li class="breadcrumb-item active">Activity Logs</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between w-100 flex-wrap">
                <div class="mb-3 mb-lg-0">
                    <h1 class="h4">Activity Logs</h1>
                    <p class="mb-0">Riwayat aktivitas sistem</p>
                </div>
                <div>
                    <a :href="exportUrl" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow mb-4">
            <div class="card-body">
                <form @submit.prevent="applyFilters">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label small">Aksi</label>
                            <select v-model="filterForm.action" class="form-select form-select-sm">
                                <option value="">Semua Aksi</option>
                                <option v-for="action in actions" :key="action" :value="action">
                                    {{ formatAction(action) }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Modul</label>
                            <select v-model="filterForm.module" class="form-select form-select-sm">
                                <option value="">Semua Modul</option>
                                <option v-for="mod in modules" :key="mod" :value="mod">
                                    {{ mod }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Tipe User</label>
                            <select v-model="filterForm.user_type" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                <option value="admin">Admin</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Dari Tanggal</label>
                            <input type="date" v-model="filterForm.date_from" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Sampai Tanggal</label>
                            <input type="date" v-model="filterForm.date_to" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Cari</label>
                            <input type="text" v-model="filterForm.search" class="form-control form-control-sm"
                                placeholder="Nama, IP, Deskripsi...">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-primary me-2">
                                <i class="fas fa-search me-1"></i> Filter
                            </button>
                            <button type="button" @click="resetFilters" class="btn btn-sm btn-secondary">
                                <i class="fas fa-times me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Activity Logs Table -->
        <div class="card border-0 shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-centered table-nowrap mb-0 rounded">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0 rounded-start">#</th>
                                <th class="border-0">Waktu</th>
                                <th class="border-0">User</th>
                                <th class="border-0">Aksi</th>
                                <th class="border-0">Modul</th>
                                <th class="border-0">Deskripsi</th>
                                <th class="border-0">IP Address</th>
                                <th class="border-0 rounded-end">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                    <span class="text-muted">Tidak ada data activity log</span>
                                </td>
                            </tr>
                            <tr v-for="(log, index) in logs.data" :key="log.id">
                                <td>{{ logs.from + index }}</td>
                                <td>
                                    <small>{{ formatDate(log.created_at) }}</small>
                                </td>
                                <td>
                                    <span :class="getUserBadgeClass(log.user_type)">
                                        {{ log.user_type || 'system' }}
                                    </span>
                                    <br>
                                    <small class="text-muted">{{ log.user_name || '-' }}</small>
                                </td>
                                <td>
                                    <span :class="getActionBadgeClass(log.action)">
                                        {{ formatAction(log.action) }}
                                    </span>
                                </td>
                                <td>{{ log.module }}</td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 250px;"
                                        :title="log.description">
                                        {{ log.description }}
                                    </span>
                                </td>
                                <td>
                                    <code>{{ log.ip_address || '-' }}</code>
                                </td>
                                <td>
                                    <Link :href="`/admin/activity-logs/${log.id}`"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-4" v-if="logs.data.length > 0">
                    <div>
                        <small class="text-muted">
                            Menampilkan {{ logs.from }} - {{ logs.to }} dari {{ logs.total }} data
                        </small>
                    </div>
                    <Pagination :links="logs.links" />
                </div>
            </div>
        </div>
    </main>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, computed } from 'vue';
import Pagination from '../../../Components/Pagination.vue';

export default {
    layout: LayoutAdmin,

    components: {
        Head,
        Link,
        Pagination,
    },

    props: {
        logs: Object,
        actions: Array,
        modules: Array,
        filters: Object,
    },

    setup(props) {
        const filterForm = reactive({
            action: props.filters?.action || '',
            module: props.filters?.module || '',
            user_type: props.filters?.user_type || '',
            search: props.filters?.search || '',
            date_from: props.filters?.date_from || '',
            date_to: props.filters?.date_to || '',
        });

        const exportUrl = computed(() => {
            const params = new URLSearchParams();
            if (filterForm.action) params.append('action', filterForm.action);
            if (filterForm.module) params.append('module', filterForm.module);
            if (filterForm.user_type) params.append('user_type', filterForm.user_type);
            if (filterForm.date_from) params.append('date_from', filterForm.date_from);
            if (filterForm.date_to) params.append('date_to', filterForm.date_to);
            return `/admin/activity-logs-export?${params.toString()}`;
        });

        const applyFilters = () => {
            router.get('/admin/activity-logs', filterForm, {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const resetFilters = () => {
            filterForm.action = '';
            filterForm.module = '';
            filterForm.user_type = '';
            filterForm.search = '';
            filterForm.date_from = '';
            filterForm.date_to = '';
            router.get('/admin/activity-logs');
        };

        const formatDate = (dateString) => {
            const date = new Date(dateString);
            return date.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const formatAction = (action) => {
            const actionMap = {
                'login': 'Login',
                'login_failed': 'Login Gagal',
                'logout': 'Logout',
                'create': 'Buat',
                'update': 'Update',
                'delete': 'Hapus',
                'exam_start': 'Mulai Ujian',
                'exam_end': 'Selesai Ujian',
                'answer_submit': 'Jawab Soal',
            };
            return actionMap[action] || action;
        };

        const getActionBadgeClass = (action) => {
            const classMap = {
                'login': 'badge bg-success',
                'login_failed': 'badge bg-danger',
                'logout': 'badge bg-secondary',
                'create': 'badge bg-primary',
                'update': 'badge bg-info',
                'delete': 'badge bg-danger',
                'exam_start': 'badge bg-warning text-dark',
                'exam_end': 'badge bg-success',
                'answer_submit': 'badge bg-light text-dark',
            };
            return classMap[action] || 'badge bg-secondary';
        };

        const getUserBadgeClass = (userType) => {
            if (userType === 'admin') return 'badge bg-primary';
            if (userType === 'student') return 'badge bg-info';
            return 'badge bg-secondary';
        };

        return {
            filterForm,
            exportUrl,
            applyFilters,
            resetFilters,
            formatDate,
            formatAction,
            getActionBadgeClass,
            getUserBadgeClass,
        };
    },
};
</script>

<style scoped>
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

code {
    font-size: 0.85em;
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
}
</style>
