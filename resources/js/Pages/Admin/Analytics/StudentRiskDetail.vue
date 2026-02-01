<template>
    <Head>
        <title>Detail Risiko Siswa - {{ prediction.student?.name }}</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <Link href="/admin/analytics/at-risk" class="btn btn-primary mb-3">
            <i class="fas fa-arrow-left"></i> Kembali
        </Link>

        <div class="row">
            <!-- Left Column: Student Info & Risk Score -->
            <div class="col-md-4">
                <!-- Student Card -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div 
                                class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                :class="getRiskBgClass(prediction.risk_level)"
                                style="width: 100px; height: 100px;"
                            >
                                <span class="text-white fs-2 fw-bold">{{ Math.round(prediction.risk_score) }}%</span>
                            </div>
                        </div>
                        <h4 class="mb-1">{{ prediction.student?.name || 'N/A' }}</h4>
                        <p class="text-muted mb-2">{{ prediction.student?.classroom?.title || '-' }}</p>
                        <span class="badge fs-6" :class="getRiskBadgeClass(prediction.risk_level)">
                            Risiko {{ getRiskLabel(prediction.risk_level) }}
                        </span>
                    </div>
                </div>

                <!-- Prediction Summary -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-chart-line"></i> Ringkasan Prediksi</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h4 class="mb-0">{{ prediction.predicted_score || '-' }}</h4>
                                <small class="text-muted">Prediksi Nilai</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h4 class="mb-0">{{ prediction.historical_average || '-' }}</h4>
                                <small class="text-muted">Rata-rata Historis</small>
                            </div>
                            <div class="col-6">
                                <h4 class="mb-0">{{ prediction.total_exams_taken || 0 }}</h4>
                                <small class="text-muted">Total Ujian</small>
                            </div>
                            <div class="col-6">
                                <h4 class="mb-0 text-success">{{ prediction.total_passed || 0 }}</h4>
                                <small class="text-muted">Lulus</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Intervention Status -->
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-hand-holding-heart"></i> Status Intervensi</h6>
                    </div>
                    <div class="card-body">
                        <p>
                            <strong>Status:</strong>
                            <span class="badge" :class="getStatusBadgeClass(prediction.intervention_status)">
                                {{ getStatusLabel(prediction.intervention_status) }}
                            </span>
                        </p>
                        <p v-if="prediction.notified_at">
                            <strong>Dinotifikasi:</strong> {{ formatDate(prediction.notified_at) }}
                        </p>
                        <p v-if="prediction.intervened_by">
                            <strong>Diintervensi oleh:</strong> {{ prediction.intervened_by?.name || '-' }}
                        </p>
                        <p v-if="prediction.intervened_at">
                            <strong>Tanggal Intervensi:</strong> {{ formatDate(prediction.intervened_at) }}
                        </p>
                        <div v-if="prediction.intervention_notes" class="mt-3">
                            <strong>Catatan:</strong>
                            <p class="bg-light p-2 rounded mt-1">{{ prediction.intervention_notes }}</p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-3">
                            <button 
                                v-if="prediction.intervention_status === 'pending'"
                                class="btn btn-success btn-sm w-100 mb-2"
                                @click="acknowledge"
                            >
                                <i class="fas fa-check"></i> Tandai Dilihat
                            </button>
                            <button 
                                v-if="['acknowledged', 'notified'].includes(prediction.intervention_status)"
                                class="btn btn-warning btn-sm w-100 mb-2"
                                @click="showInterveneForm = !showInterveneForm"
                            >
                                <i class="fas fa-hand-holding-heart"></i> Catat Intervensi
                            </button>
                            <button 
                                v-if="prediction.intervention_status === 'intervened'"
                                class="btn btn-outline-success btn-sm w-100"
                                @click="resolve"
                            >
                                <i class="fas fa-check-double"></i> Tandai Selesai
                            </button>

                            <!-- Intervene Form -->
                            <div v-if="showInterveneForm" class="mt-3">
                                <textarea 
                                    class="form-control mb-2" 
                                    v-model="interveneNotes"
                                    rows="3"
                                    placeholder="Catatan intervensi..."
                                ></textarea>
                                <button class="btn btn-primary btn-sm w-100" @click="intervene">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Risk Factors & History -->
            <div class="col-md-8">
                <!-- Risk Factors Breakdown -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Faktor Risiko</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-4" v-for="(factor, key) in prediction.risk_factors" :key="key">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-capitalize fw-bold">{{ getCategoryLabel(key) }}</span>
                                    <span>{{ factor.score }}% (bobot: {{ Math.round(factor.weight * 100) }}%)</span>
                                </div>
                                <div class="progress mb-2" style="height: 10px;">
                                    <div 
                                        class="progress-bar" 
                                        :class="getProgressBarClass(factor.score)"
                                        :style="{ width: factor.score + '%' }"
                                    ></div>
                                </div>
                                <div v-if="factor.factors && factor.factors.length">
                                    <span 
                                        v-for="f in factor.factors" 
                                        :key="f" 
                                        class="badge bg-light text-dark me-1 mb-1"
                                    >
                                        {{ getFactorLabel(f) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recommendations -->
                <div class="card border-0 shadow mb-4" v-if="prediction.recommended_actions?.length">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-lightbulb"></i> Rekomendasi Tindakan</h6>
                    </div>
                    <div class="card-body">
                        <div 
                            v-for="(action, idx) in prediction.recommended_actions" 
                            :key="idx"
                            class="d-flex align-items-start mb-3"
                        >
                            <span 
                                class="badge me-3 mt-1"
                                :class="getPriorityBadgeClass(action.priority)"
                            >
                                {{ idx + 1 }}
                            </span>
                            <div>
                                <strong>{{ action.action?.replace(/_/g, ' ') }}</strong>
                                <p class="text-muted mb-0 small">{{ action.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weak Topics -->
                <div class="card border-0 shadow mb-4" v-if="prediction.weak_topics?.length">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-book-open"></i> Area yang Perlu Ditingkatkan</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div v-for="topic in prediction.weak_topics" :key="topic.type" class="col-md-4">
                                <div class="text-center p-3 bg-light rounded">
                                    <h4 class="text-danger mb-1">{{ topic.percentage }}%</h4>
                                    <small class="text-muted">{{ topic.type }} ({{ topic.count }} salah)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historical Grades -->
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-history"></i> Riwayat Nilai</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Ujian</th>
                                        <th class="text-center">Nilai</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Pelanggaran</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="grade in historicalGrades" :key="grade.id">
                                        <td>{{ grade.exam_title }}</td>
                                        <td class="text-center">
                                            <span :class="getGradeClass(grade.grade)">{{ grade.grade }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span 
                                                class="badge"
                                                :class="grade.status === 'passed' ? 'bg-success' : 'bg-danger'"
                                            >
                                                {{ grade.status === 'passed' ? 'Lulus' : 'Tidak Lulus' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span v-if="grade.violations > 0" class="text-warning">
                                                <i class="fas fa-exclamation-triangle"></i> {{ grade.violations }}
                                            </span>
                                            <span v-else class="text-success">-</span>
                                        </td>
                                        <td>{{ grade.date }}</td>
                                    </tr>
                                    <tr v-if="historicalGrades.length === 0">
                                        <td colspan="5" class="text-center text-muted">
                                            Belum ada riwayat nilai
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
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        prediction: {
            type: Object,
            required: true
        },
        historicalGrades: {
            type: Array,
            default: () => []
        }
    },
    setup(props) {
        const showInterveneForm = ref(false);
        const interveneNotes = ref('');

        const getRiskBgClass = (level) => {
            const classes = {
                critical: 'bg-danger',
                high: 'bg-warning',
                medium: 'bg-info',
                low: 'bg-success'
            };
            return classes[level] || 'bg-secondary';
        };

        const getRiskBadgeClass = (level) => {
            const classes = {
                critical: 'bg-danger',
                high: 'bg-warning text-dark',
                medium: 'bg-info',
                low: 'bg-success'
            };
            return classes[level] || 'bg-secondary';
        };

        const getRiskLabel = (level) => {
            const labels = {
                critical: 'Kritis',
                high: 'Tinggi',
                medium: 'Sedang',
                low: 'Rendah'
            };
            return labels[level] || level;
        };

        const getStatusBadgeClass = (status) => {
            const classes = {
                pending: 'bg-secondary',
                notified: 'bg-info',
                acknowledged: 'bg-primary',
                intervened: 'bg-warning text-dark',
                resolved: 'bg-success'
            };
            return classes[status] || 'bg-secondary';
        };

        const getStatusLabel = (status) => {
            const labels = {
                pending: 'Pending',
                notified: 'Dinotifikasi',
                acknowledged: 'Dilihat',
                intervened: 'Diintervensi',
                resolved: 'Selesai'
            };
            return labels[status] || status;
        };

        const getCategoryLabel = (key) => {
            const labels = {
                academic: 'Akademik',
                behavioral: 'Perilaku',
                engagement: 'Keterlibatan',
                contextual: 'Kontekstual'
            };
            return labels[key] || key;
        };

        const getFactorLabel = (factor) => {
            const labels = {
                low_average: 'Rata-rata rendah',
                declining_trend: 'Trend menurun',
                high_fail_rate: 'Banyak tidak lulus',
                below_passing: 'Di bawah KKM',
                no_history: 'Belum ada riwayat',
                high_violations: 'Banyak pelanggaran',
                frequent_violations: 'Sering melanggar',
                previously_flagged: 'Pernah ditandai',
                limited_history: 'Riwayat terbatas',
                rushing_exams: 'Terburu-buru',
                inconsistent_timing: 'Waktu tidak konsisten',
                difficult_subject: 'Mapel sulit',
                below_class_average: 'Di bawah rata-rata kelas',
                first_attempt_subject: 'Ujian pertama'
            };
            return labels[factor] || factor.replace(/_/g, ' ');
        };

        const getProgressBarClass = (score) => {
            if (score >= 70) return 'bg-danger';
            if (score >= 50) return 'bg-warning';
            if (score >= 30) return 'bg-info';
            return 'bg-success';
        };

        const getPriorityBadgeClass = (priority) => {
            const classes = {
                high: 'bg-danger',
                medium: 'bg-warning text-dark',
                low: 'bg-info'
            };
            return classes[priority] || 'bg-secondary';
        };

        const getGradeClass = (grade) => {
            if (grade >= 80) return 'text-success fw-bold';
            if (grade >= 60) return 'text-warning fw-bold';
            return 'text-danger fw-bold';
        };

        const formatDate = (dateStr) => {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        };

        const acknowledge = () => {
            router.post(`/admin/analytics/at-risk/${props.prediction.id}/acknowledge`, {}, {
                preserveScroll: true
            });
        };

        const intervene = () => {
            router.post(`/admin/analytics/at-risk/${props.prediction.id}/intervene`, {
                notes: interveneNotes.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    showInterveneForm.value = false;
                    interveneNotes.value = '';
                }
            });
        };

        const resolve = () => {
            router.post(`/admin/analytics/at-risk/${props.prediction.id}/resolve`, {}, {
                preserveScroll: true
            });
        };

        return {
            showInterveneForm,
            interveneNotes,
            getRiskBgClass,
            getRiskBadgeClass,
            getRiskLabel,
            getStatusBadgeClass,
            getStatusLabel,
            getCategoryLabel,
            getFactorLabel,
            getProgressBarClass,
            getPriorityBadgeClass,
            getGradeClass,
            formatDate,
            acknowledge,
            intervene,
            resolve
        };
    }
}
</script>
