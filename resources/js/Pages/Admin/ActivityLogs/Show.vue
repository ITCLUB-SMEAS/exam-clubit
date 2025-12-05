<template>
    <Head>
        <title>Detail Activity Log - Admin</title>
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
                    <li class="breadcrumb-item">
                        <Link href="/admin/activity-logs">Activity Logs</Link>
                    </li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between w-100 flex-wrap">
                <div class="mb-3 mb-lg-0">
                    <h1 class="h4">Detail Activity Log</h1>
                    <p class="mb-0">ID: #{{ log.id }}</p>
                </div>
                <div>
                    <Link href="/admin/activity-logs" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </Link>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Info -->
            <div class="col-md-8">
                <div class="card border-0 shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Informasi Aktivitas
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th width="200">Waktu</th>
                                    <td>{{ formatDate(log.created_at) }}</td>
                                </tr>
                                <tr>
                                    <th>Aksi</th>
                                    <td>
                                        <span :class="getActionBadgeClass(log.action)">
                                            {{ formatAction(log.action) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Modul</th>
                                    <td>
                                        <span class="badge bg-secondary">{{ log.module }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Deskripsi</th>
                                    <td>{{ log.description }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Old Values -->
                <div class="card border-0 shadow mb-4" v-if="log.old_values && Object.keys(log.old_values).length > 0">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Data Sebelumnya
                        </h5>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0 bg-light p-3 rounded"><code>{{ formatJson(log.old_values) }}</code></pre>
                    </div>
                </div>

                <!-- New Values -->
                <div class="card border-0 shadow mb-4" v-if="log.new_values && Object.keys(log.new_values).length > 0">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Data Baru
                        </h5>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0 bg-light p-3 rounded"><code>{{ formatJson(log.new_values) }}</code></pre>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="card border-0 shadow mb-4" v-if="log.metadata && Object.keys(log.metadata).length > 0">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-database me-2"></i>
                            Metadata
                        </h5>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0 bg-light p-3 rounded"><code>{{ formatJson(log.metadata) }}</code></pre>
                    </div>
                </div>
            </div>

            <!-- Side Info -->
            <div class="col-md-4">
                <!-- User Info -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>
                            Informasi User
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <th>Tipe</th>
                                    <td>
                                        <span :class="getUserBadgeClass(log.user_type)">
                                            {{ log.user_type || 'system' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>ID</th>
                                    <td>{{ log.user_id || '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Nama</th>
                                    <td>{{ log.user_name || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Subject Info -->
                <div class="card border-0 shadow mb-4" v-if="log.subject_type">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-link me-2"></i>
                            Subject Terkait
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <th>Tipe</th>
                                    <td><code>{{ getShortClassName(log.subject_type) }}</code></td>
                                </tr>
                                <tr>
                                    <th>ID</th>
                                    <td>{{ log.subject_id }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Request Info -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-globe me-2"></i>
                            Informasi Request
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <th>IP Address</th>
                                    <td><code>{{ log.ip_address || '-' }}</code></td>
                                </tr>
                                <tr>
                                    <th>Method</th>
                                    <td>
                                        <span class="badge bg-secondary">{{ log.method || '-' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>URL</th>
                                    <td>
                                        <small class="text-break">{{ log.url || '-' }}</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- User Agent -->
                <div class="card border-0 shadow mb-4" v-if="log.user_agent">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-desktop me-2"></i>
                            User Agent
                        </h5>
                    </div>
                    <div class="card-body">
                        <small class="text-muted text-break">{{ log.user_agent }}</small>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,

    components: {
        Head,
        Link,
    },

    props: {
        log: Object,
    },

    setup() {
        const formatDate = (dateString) => {
            const date = new Date(dateString);
            return date.toLocaleString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        };

        const formatAction = (action) => {
            const actionMap = {
                'login': 'Login',
                'login_failed': 'Login Gagal',
                'logout': 'Logout',
                'create': 'Buat Data',
                'update': 'Update Data',
                'delete': 'Hapus Data',
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

        const formatJson = (obj) => {
            return JSON.stringify(obj, null, 2);
        };

        const getShortClassName = (fullClassName) => {
            if (!fullClassName) return '-';
            const parts = fullClassName.split('\\');
            return parts[parts.length - 1];
        };

        return {
            formatDate,
            formatAction,
            getActionBadgeClass,
            getUserBadgeClass,
            formatJson,
            getShortClassName,
        };
    },
};
</script>

<style scoped>
pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-size: 0.85em;
}

code {
    color: #333;
}

.text-break {
    word-break: break-all;
}

th {
    white-space: nowrap;
}
</style>
