<template>
    <Head>
        <title>Analisis Ujian - {{ exam.title }}</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <Link href="/admin/analytics" class="btn btn-primary mb-3">
            <i class="fas fa-arrow-left"></i> Kembali
        </Link>

        <div class="card border-0 shadow mb-4">
            <div class="card-body">
                <h5>{{ exam.title }}</h5>
                <p class="text-muted mb-0">{{ exam.lesson?.title }} | {{ exam.classroom?.title }}</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-2 mb-2">
                <div class="card border-0 shadow text-center">
                    <div class="card-body py-3">
                        <h4 class="mb-0">{{ stats.total_participants }}</h4>
                        <small>Peserta</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <div class="card border-0 shadow text-center">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-info">{{ stats.avg_grade }}</h4>
                        <small>Rata-rata</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <div class="card border-0 shadow text-center">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-success">{{ stats.max_grade }}</h4>
                        <small>Tertinggi</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <div class="card border-0 shadow text-center">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-danger">{{ stats.min_grade }}</h4>
                        <small>Terendah</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <div class="card border-0 shadow text-center">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-success">{{ stats.passed }}</h4>
                        <small>Lulus</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <div class="card border-0 shadow text-center">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-danger">{{ stats.failed }}</h4>
                        <small>Tidak Lulus</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Question Analysis -->
            <div class="col-md-8 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-question-circle"></i> Analisis Soal</h5>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Soal</th>
                                        <th>Tipe</th>
                                        <th>Benar</th>
                                        <th>Tingkat Kesulitan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(q, idx) in questionStats" :key="q.id">
                                        <td>{{ idx + 1 }}</td>
                                        <td>{{ q.question }}</td>
                                        <td><span class="badge bg-secondary">{{ typeLabel(q.type) }}</span></td>
                                        <td>{{ q.correct_answers }}/{{ q.total_answers }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <div class="progress-bar" :class="difficultyColor(q.difficulty_percent)"
                                                        :style="{ width: q.difficulty_percent + '%' }"></div>
                                                </div>
                                                <span :class="difficultyTextColor(q.difficulty_level)">
                                                    {{ q.difficulty_level }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-trophy"></i> Top 10 Siswa</h5>
                        <hr>
                        <ol class="list-group list-group-numbered">
                            <li v-for="(student, idx) in topPerformers" :key="idx" 
                                class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ student.name }}</span>
                                <span class="badge" :class="student.status === 'passed' ? 'bg-success' : 'bg-danger'">
                                    {{ student.grade }}
                                </span>
                            </li>
                        </ol>
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
        exam: Object,
        stats: Object,
        questionStats: Array,
        topPerformers: Array,
    },
    setup() {
        const typeLabel = (type) => ({
            multiple_choice_single: 'PG',
            multiple_choice_multiple: 'PG Multi',
            true_false: 'B/S',
            short_answer: 'Singkat',
            essay: 'Essay',
            matching: 'Jodoh'
        }[type] || type);

        const difficultyColor = (percent) => {
            if (percent >= 70) return 'bg-success';
            if (percent >= 40) return 'bg-warning';
            return 'bg-danger';
        };

        const difficultyTextColor = (level) => {
            if (level === 'Mudah') return 'text-success';
            if (level === 'Sedang') return 'text-warning';
            return 'text-danger';
        };

        return { typeLabel, difficultyColor, difficultyTextColor };
    }
}
</script>
