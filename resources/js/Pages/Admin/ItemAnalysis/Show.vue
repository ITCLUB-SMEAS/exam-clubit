<template>
    <Head>
        <title>Analisis Soal - {{ exam.title }}</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link href="/admin/exams" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <!-- Exam Info -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-chart-bar me-2"></i> Analisis Soal: {{ exam.title }}</h5>
                        <p class="text-muted mb-0">
                            {{ exam.lesson?.title }} | {{ exam.classroom?.title }} | 
                            Total Peserta: <strong>{{ total_students }}</strong>
                        </p>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div v-if="summary" class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow">
                            <div class="card-body text-center">
                                <h3 class="text-primary">{{ summary.total_questions }}</h3>
                                <small class="text-muted">Total Soal</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow">
                            <div class="card-body text-center">
                                <h3 class="text-success">{{ summary.good_questions }}</h3>
                                <small class="text-muted">Soal Berkualitas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow">
                            <div class="card-body text-center">
                                <h3 class="text-danger">{{ summary.needs_revision }}</h3>
                                <small class="text-muted">Perlu Revisi</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow">
                            <div class="card-body text-center">
                                <h3 :class="qualityColor">{{ summary.quality_percentage }}%</h3>
                                <small class="text-muted">Kualitas Ujian</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Data -->
                <div v-if="!analysis.length" class="card border-0 shadow">
                    <div class="card-body text-center py-5">
                        <i class="fa fa-chart-bar fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Belum ada data ujian yang selesai untuk dianalisis.</p>
                    </div>
                </div>

                <!-- Questions Analysis -->
                <div v-else class="card border-0 shadow">
                    <div class="card-header">
                        <h6 class="mb-0">Detail Analisis Per Soal</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Soal</th>
                                        <th class="text-center">Dijawab</th>
                                        <th class="text-center">Benar</th>
                                        <th class="text-center">Tingkat Kesulitan</th>
                                        <th class="text-center">Daya Pembeda</th>
                                        <th>Rekomendasi</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in analysis" :key="item.question_id">
                                        <td>{{ index + 1 }}</td>
                                        <td style="max-width: 200px;">
                                            <span class="text-truncate d-block">{{ item.question_text }}</span>
                                        </td>
                                        <td class="text-center">{{ item.total_answers }}</td>
                                        <td class="text-center">{{ item.correct_answers }}</td>
                                        <td class="text-center">
                                            <span :class="getDifficultyBadge(item.difficulty_index)">
                                                {{ item.difficulty_label }} ({{ item.difficulty_index }})
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span :class="getDiscriminationBadge(item.discrimination_index)">
                                                {{ item.discrimination_label }} ({{ item.discrimination_index }})
                                            </span>
                                        </td>
                                        <td>
                                            <span :class="getRecommendationBadge(item.recommendation)">
                                                {{ item.recommendation }}
                                            </span>
                                        </td>
                                        <td>
                                            <button v-if="item.distractors.length" 
                                                    @click="showDistractors(item)" 
                                                    class="btn btn-sm btn-outline-info">
                                                <i class="fa fa-chart-pie"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="card border-0 shadow mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">Keterangan</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Tingkat Kesulitan (P)</h6>
                                <ul class="small">
                                    <li><strong>0.8 - 1.0:</strong> Sangat Mudah</li>
                                    <li><strong>0.6 - 0.8:</strong> Mudah</li>
                                    <li><strong>0.4 - 0.6:</strong> Sedang (Ideal)</li>
                                    <li><strong>0.2 - 0.4:</strong> Sulit</li>
                                    <li><strong>0.0 - 0.2:</strong> Sangat Sulit</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Daya Pembeda (D)</h6>
                                <ul class="small">
                                    <li><strong>≥ 0.4:</strong> Sangat Baik</li>
                                    <li><strong>0.3 - 0.4:</strong> Baik</li>
                                    <li><strong>0.2 - 0.3:</strong> Cukup</li>
                                    <li><strong>0.1 - 0.2:</strong> Kurang</li>
                                    <li><strong>&lt; 0.1:</strong> Buruk (Perlu Revisi)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Distractor Modal -->
    <div v-if="selectedQuestion" class="modal fade show" style="display:block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Distribusi Jawaban</h5>
                    <button @click="selectedQuestion = null" class="btn-close"></button>
                </div>
                <div class="modal-body">
                    <div v-for="d in selectedQuestion.distractors" :key="d.option" class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>
                                <strong>{{ d.option }}.</strong> {{ d.text }}
                                <span v-if="d.is_correct" class="badge bg-success ms-1">Benar</span>
                            </span>
                            <span>{{ d.count }} ({{ d.percentage }}%)</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar" 
                                 :class="d.is_correct ? 'bg-success' : 'bg-secondary'"
                                 :style="{ width: d.percentage + '%' }">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    exam: Object,
    analysis: Array,
    summary: Object,
    total_students: Number,
});

const selectedQuestion = ref(null);

const qualityColor = computed(() => {
    if (!props.summary) return 'text-muted';
    if (props.summary.quality_percentage >= 70) return 'text-success';
    if (props.summary.quality_percentage >= 50) return 'text-warning';
    return 'text-danger';
});

const getDifficultyBadge = (index) => {
    if (index >= 0.4 && index <= 0.6) return 'badge bg-success';
    if (index >= 0.3 && index <= 0.7) return 'badge bg-info';
    return 'badge bg-warning text-dark';
};

const getDiscriminationBadge = (index) => {
    if (index >= 0.3) return 'badge bg-success';
    if (index >= 0.2) return 'badge bg-info';
    if (index >= 0.1) return 'badge bg-warning text-dark';
    return 'badge bg-danger';
};

const getRecommendationBadge = (rec) => {
    if (rec.includes('Pertahankan')) return 'badge bg-success';
    if (rec.includes('Revisi')) return 'badge bg-danger';
    return 'badge bg-warning text-dark';
};

const showDistractors = (item) => {
    selectedQuestion.value = item;
};
</script>
