<template>
    <Head>
        <title>Log Pelanggaran Anti-Cheat - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-header">
                        <h5><i class="fa fa-shield-alt me-2"></i> Log Pelanggaran Anti-Cheat</h5>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Filter Tipe Pelanggaran</label>
                                <select class="form-select" v-model="filterType" @change="applyFilter">
                                    <option value="">Semua Tipe</option>
                                    <option v-for="(label, type) in violationTypes" :key="type" :value="type">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Siswa</th>
                                        <th>Ujian</th>
                                        <th>Tipe Pelanggaran</th>
                                        <th>Deskripsi</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="violations.data.length === 0">
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fa fa-check-circle fa-2x mb-2"></i>
                                            <p>Tidak ada pelanggaran tercatat</p>
                                        </td>
                                    </tr>
                                    <tr v-for="v in violations.data" :key="v.id">
                                        <td>
                                            <small>{{ formatDate(v.created_at) }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ v.student?.name }}</strong>
                                            <br><small class="text-muted">{{ v.student?.nisn }}</small>
                                        </td>
                                        <td>
                                            {{ v.exam?.title }}
                                            <br><small class="text-muted">Sesi: {{ v.exam_session?.title }}</small>
                                        </td>
                                        <td>
                                            <span :class="getBadgeClass(v.violation_type)" class="badge">
                                                {{ violationTypes[v.violation_type] || v.violation_type }}
                                            </span>
                                        </td>
                                        <td>{{ v.description }}</td>
                                        <td><small>{{ v.ip_address }}</small></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav v-if="violations.last_page > 1">
                            <ul class="pagination justify-content-center">
                                <li v-for="link in violations.links" :key="link.label" 
                                    :class="['page-item', { active: link.active, disabled: !link.url }]">
                                    <Link v-if="link.url" :href="link.url" class="page-link" v-html="link.label"></Link>
                                    <span v-else class="page-link" v-html="link.label"></span>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        violations: Object,
        filters: Object,
        violationTypes: Object,
    },
    setup(props) {
        const filterType = ref(props.filters?.violation_type || '');

        const applyFilter = () => {
            router.get('/admin/violation-logs', {
                violation_type: filterType.value || undefined,
            }, { preserveState: true });
        };

        const formatDate = (date) => {
            return new Date(date).toLocaleString('id-ID', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        };

        const getBadgeClass = (type) => {
            const classes = {
                'tab_switch': 'bg-warning text-dark',
                'fullscreen_exit': 'bg-warning text-dark',
                'copy_paste': 'bg-info',
                'right_click': 'bg-secondary',
                'devtools': 'bg-danger',
                'blur': 'bg-warning text-dark',
                'screenshot': 'bg-danger',
                'multiple_monitors': 'bg-danger',
                'virtual_machine': 'bg-danger',
                'remote_desktop': 'bg-danger',
            };
            return classes[type] || 'bg-secondary';
        };

        return { filterType, applyFilter, formatDate, getBadgeClass };
    }
}
</script>
