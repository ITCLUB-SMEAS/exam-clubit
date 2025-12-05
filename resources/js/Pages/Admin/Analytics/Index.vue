<template>
    <Head>
        <title>Analytics - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow h-100">
                    <div class="card-body text-center">
                        <h3 class="text-primary">{{ stats.total_completed }}</h3>
                        <p class="mb-0">Ujian Selesai</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow h-100">
                    <div class="card-body text-center">
                        <h3 class="text-info">{{ stats.avg_grade }}</h3>
                        <p class="mb-0">Rata-rata Nilai</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow h-100">
                    <div class="card-body text-center">
                        <h3 class="text-success">{{ stats.pass_rate }}%</h3>
                        <p class="mb-0">Tingkat Kelulusan</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow h-100">
                    <div class="card-body text-center">
                        <h3><span class="text-success">{{ stats.passed_count }}</span> / <span class="text-danger">{{ stats.failed_count }}</span></h3>
                        <p class="mb-0">Lulus / Tidak Lulus</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Grade Distribution -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-chart-bar"></i> Distribusi Nilai</h5>
                        <hr>
                        <div v-for="item in gradeDistribution" :key="item.grade_range" class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>{{ item.grade_range }}</span>
                                <span>{{ item.count }}</span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" :class="getBarColor(item.grade_range)" 
                                    :style="{ width: getPercentage(item.count) + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Exams -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-list"></i> Ujian Terbaru</h5>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Ujian</th>
                                        <th>Peserta</th>
                                        <th>Rata-rata</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="exam in recentExams" :key="exam.id">
                                        <td>{{ exam.title }}</td>
                                        <td>{{ exam.completed_count || 0 }}</td>
                                        <td>{{ Number(exam.avg_grade || 0).toFixed(1) }}</td>
                                        <td>
                                            <Link :href="`/admin/analytics/exam/${exam.id}`" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Classroom Performance -->
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Performa per Kelas</h5>
                            <Link href="/admin/analytics/students" class="btn btn-sm btn-primary">
                                Lihat Detail Siswa
                            </Link>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kelas</th>
                                        <th>Jumlah Siswa</th>
                                        <th>Ujian Dikerjakan</th>
                                        <th>Rata-rata Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="classroom in classroomPerformance" :key="classroom.name">
                                        <td>{{ classroom.name }}</td>
                                        <td>{{ classroom.students_count }}</td>
                                        <td>{{ classroom.exams_taken }}</td>
                                        <td>
                                            <span :class="getGradeClass(classroom.avg_grade)">
                                                {{ classroom.avg_grade }}
                                            </span>
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

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        stats: Object,
        recentExams: Array,
        gradeDistribution: Array,
        classroomPerformance: Array,
    },
    setup(props) {
        const maxCount = Math.max(...props.gradeDistribution.map(i => i.count), 1);
        
        const getPercentage = (count) => (count / maxCount) * 100;
        
        const getBarColor = (range) => {
            if (range.includes('A')) return 'bg-success';
            if (range.includes('B')) return 'bg-info';
            if (range.includes('C')) return 'bg-warning';
            if (range.includes('D')) return 'bg-orange';
            return 'bg-danger';
        };

        const getGradeClass = (grade) => {
            if (grade >= 80) return 'text-success fw-bold';
            if (grade >= 60) return 'text-warning fw-bold';
            return 'text-danger fw-bold';
        };

        return { getPercentage, getBarColor, getGradeClass };
    }
}
</script>
