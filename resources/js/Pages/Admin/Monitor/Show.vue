<template>
    <Head>
        <title>Monitor: {{ examSession.exam.title }} - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1">{{ examSession.exam.title }}</h4>
                                <p class="text-muted mb-0">{{ examSession.exam.lesson?.name }} | {{ examSession.exam.classroom?.name }}</p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success fs-6"><i class="fa fa-circle me-1"></i>Live</span>
                                <p class="text-muted small mb-0 mt-1">Auto-refresh: {{ refreshInterval }}s</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-6 col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <h3 class="mb-0">{{ stats.total }}</h3>
                        <small class="text-muted">Total</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card border-0 shadow-sm text-center bg-secondary text-white">
                    <div class="card-body py-3">
                        <h3 class="mb-0">{{ stats.not_started }}</h3>
                        <small>Belum Mulai</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card border-0 shadow-sm text-center bg-primary text-white">
                    <div class="card-body py-3">
                        <h3 class="mb-0">{{ stats.in_progress }}</h3>
                        <small>Mengerjakan</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card border-0 shadow-sm text-center bg-warning">
                    <div class="card-body py-3">
                        <h3 class="mb-0">{{ stats.paused }}</h3>
                        <small>Pause</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card border-0 shadow-sm text-center bg-success text-white">
                    <div class="card-body py-3">
                        <h3 class="mb-0">{{ stats.completed }}</h3>
                        <small>Selesai</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card border-0 shadow-sm text-center bg-danger text-white">
                    <div class="card-body py-3">
                        <h3 class="mb-0">{{ stats.flagged }}</h3>
                        <small>Flagged</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Participants Table -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fa fa-users me-2"></i>Peserta ({{ participantList.length }})</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover mb-0">
                                <thead class="sticky-top bg-light">
                                    <tr>
                                        <th>Siswa</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Sisa Waktu</th>
                                        <th>Pelanggaran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in participantList" :key="p.id" :class="{'table-danger': p.is_flagged, 'table-warning': p.is_paused}">
                                        <td>
                                            <strong>{{ p.student.name }}</strong>
                                            <br><small class="text-muted">{{ p.student.nisn }} | {{ p.student.classroom }}</small>
                                        </td>
                                        <td>
                                            <span :class="statusBadge(p.status)">{{ statusLabel(p.status) }}</span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" :style="{width: p.progress + '%'}">{{ p.progress }}%</div>
                                            </div>
                                        </td>
                                        <td>{{ formatDuration(p.duration_remaining) }}</td>
                                        <td>
                                            <span v-if="p.violation_count > 0" class="badge bg-danger">{{ p.violation_count }}</span>
                                            <span v-else class="badge bg-secondary">0</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Violations -->
            <div class="col-lg-4">
                <div class="card border-0 shadow">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0"><i class="fa fa-exclamation-triangle me-2"></i>Pelanggaran Terbaru</h6>
                    </div>
                    <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                        <div v-if="violations.length === 0" class="text-center py-4 text-muted">
                            <i class="fa fa-check-circle fa-2x mb-2"></i>
                            <p class="mb-0">Tidak ada pelanggaran</p>
                        </div>
                        <ul v-else class="list-group list-group-flush">
                            <li v-for="v in violations" :key="v.id" class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ v.student_name }}</strong>
                                    <small class="text-muted">{{ v.created_at }}</small>
                                </div>
                                <small class="text-danger">{{ v.type }}: {{ v.description }}</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';
import axios from 'axios';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    examSession: Object,
    participants: Array,
});

const refreshInterval = 5;
const participantList = ref(props.participants);
const violations = ref([]);
const stats = ref({ total: 0, not_started: 0, in_progress: 0, paused: 0, completed: 0, flagged: 0 });
let interval = null;

const fetchData = async () => {
    try {
        const [pRes, vRes] = await Promise.all([
            axios.get(route('admin.monitor.participants', props.examSession.id)),
            axios.get(route('admin.monitor.violations', props.examSession.id)),
        ]);
        participantList.value = pRes.data.participants;
        stats.value = pRes.data.stats;
        violations.value = vRes.data.violations;
    } catch (e) {
        console.error('Failed to fetch monitor data', e);
    }
};

onMounted(() => {
    fetchData();
    interval = setInterval(fetchData, refreshInterval * 1000);
});

onUnmounted(() => {
    if (interval) clearInterval(interval);
});

const statusBadge = (status) => ({
    'not_started': 'badge bg-secondary',
    'in_progress': 'badge bg-primary',
    'paused': 'badge bg-warning',
    'completed': 'badge bg-success',
}[status] || 'badge bg-secondary');

const statusLabel = (status) => ({
    'not_started': 'Belum Mulai',
    'in_progress': 'Mengerjakan',
    'paused': 'Pause',
    'completed': 'Selesai',
}[status] || status);

const formatDuration = (ms) => {
    if (!ms || ms <= 0) return '-';
    const minutes = Math.floor(ms / 60000);
    const seconds = Math.floor((ms % 60000) / 1000);
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
};
</script>
