<template>
    <Head>
        <title>Dashboard - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-12 col-sm-6 col-xl-3 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-shape icon-shape-info rounded me-3">
                                <i class="fas fa-th-large fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Kelas</h6>
                                <h3 class="fw-bold mb-0">{{ classrooms }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-shape icon-shape-success rounded me-3">
                                <i class="fas fa-users fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Siswa</h6>
                                <h3 class="fw-bold mb-0">{{ students }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-shape icon-shape-tertiary rounded me-3">
                                <i class="fas fa-file-alt fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Ujian</h6>
                                <h3 class="fw-bold mb-0">{{ exams }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="icon-shape icon-shape-danger rounded me-3">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Sesi Aktif</h6>
                                <h3 class="fw-bold mb-0">{{ activeSessions }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <!-- Exam Trend Chart -->
            <div class="col-12 col-xl-8 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Ujian Selesai (7 Hari Terakhir)</h5>
                    </div>
                    <div class="card-body">
                        <Line :data="trendChartData" :options="lineOptions" style="height: 300px;" />
                    </div>
                </div>
            </div>

            <!-- Pass/Fail Pie Chart -->
            <div class="col-12 col-xl-4 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Rasio Lulus/Tidak Lulus</h5>
                    </div>
                    <div class="card-body">
                        <Doughnut :data="passFailData" :options="pieOptions" style="height: 300px;" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Charts Row -->
        <div class="row">
            <!-- Grade Distribution -->
            <div class="col-12 col-xl-4 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Distribusi Nilai</h5>
                    </div>
                    <div class="card-body">
                        <Bar :data="gradeDistData" :options="barOptions" style="height: 250px;" />
                    </div>
                </div>
            </div>

            <!-- At-Risk Students Widget -->
            <div class="col-12 col-xl-4 mb-4">
                <AtRiskWidget />
            </div>

            <!-- Top Exams -->
            <div class="col-12 col-xl-4 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Top 5 Ujian (Peserta Terbanyak)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Ujian</th>
                                        <th class="text-end">Peserta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(exam, index) in topExams" :key="exam.id">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ exam.title }}</td>
                                        <td class="text-end">
                                            <span class="badge bg-primary">{{ exam.participants }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="topExams.length === 0">
                                        <td colspan="3" class="text-center text-muted">Belum ada data</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Aktivitas Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Siswa</th>
                                        <th>Ujian</th>
                                        <th>Nilai</th>
                                        <th>Status</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="grade in recentGrades" :key="grade.id">
                                        <td>{{ grade.student?.name }}</td>
                                        <td>{{ grade.exam?.title }}</td>
                                        <td><strong>{{ grade.grade }}</strong></td>
                                        <td>
                                            <span :class="grade.status === 'passed' ? 'badge bg-success' : 'badge bg-danger'">
                                                {{ grade.status === 'passed' ? 'Lulus' : 'Tidak Lulus' }}
                                            </span>
                                        </td>
                                        <td>{{ formatDate(grade.end_time) }}</td>
                                    </tr>
                                    <tr v-if="recentGrades.length === 0">
                                        <td colspan="5" class="text-center text-muted">Belum ada aktivitas</td>
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
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';
import AtRiskWidget from '../../../Components/Dashboard/AtRiskWidget.vue';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(
    CategoryScale, LinearScale, PointElement, LineElement,
    BarElement, ArcElement, Title, Tooltip, Legend
);

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    students: Number,
    exams: Number,
    exam_sessions: Number,
    classrooms: Number,
    activeSessions: Number,
    gradeDistribution: Object,
    examTrend: Array,
    passFailRatio: Object,
    topExams: Array,
    recentGrades: Array,
});

// Line chart data
const trendChartData = computed(() => ({
    labels: props.examTrend?.map(d => d.date) || [],
    datasets: [{
        label: 'Ujian Selesai',
        data: props.examTrend?.map(d => d.count) || [],
        borderColor: '#4e73df',
        backgroundColor: 'rgba(78, 115, 223, 0.1)',
        fill: true,
        tension: 0.3,
    }],
}));

// Pass/Fail doughnut
const passFailData = computed(() => ({
    labels: ['Lulus', 'Tidak Lulus'],
    datasets: [{
        data: [props.passFailRatio?.passed || 0, props.passFailRatio?.failed || 0],
        backgroundColor: ['#1cc88a', '#e74a3b'],
    }],
}));

// Grade distribution bar
const gradeDistData = computed(() => ({
    labels: ['A', 'B', 'C', 'D', 'E'],
    datasets: [{
        label: 'Jumlah',
        data: [
            props.gradeDistribution?.A || 0,
            props.gradeDistribution?.B || 0,
            props.gradeDistribution?.C || 0,
            props.gradeDistribution?.D || 0,
            props.gradeDistribution?.E || 0,
        ],
        backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#fd7e14', '#e74a3b'],
    }],
}));

const lineOptions = { responsive: true, maintainAspectRatio: false };
const pieOptions = { responsive: true, maintainAspectRatio: false };
const barOptions = { responsive: true, maintainAspectRatio: false };

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
    });
};
</script>
