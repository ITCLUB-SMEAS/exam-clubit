<template>
    <Head>
        <title>Siswa Berisiko - Predictive Analytics</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <Link href="/admin/analytics" class="btn btn-primary mb-3">
            <i class="fas fa-arrow-left"></i> Kembali ke Analytics
        </Link>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-danger text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0">{{ summary.critical_count || 0 }}</h3>
                        <small>Risiko Kritis</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-warning text-dark">
                    <div class="card-body text-center">
                        <h3 class="mb-0">{{ summary.high_risk_count || 0 }}</h3>
                        <small>Risiko Tinggi</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-info text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0">{{ summary.pending_interventions || 0 }}</h3>
                        <small>Butuh Intervensi</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-success text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-0">{{ summary.accuracy_rate ? summary.accuracy_rate + '%' : 'N/A' }}</h3>
                        <small>Akurasi Prediksi</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters & Actions -->
        <div class="card border-0 shadow mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Level Risiko</label>
                        <select class="form-select" v-model="filterForm.risk_level" @change="applyFilters">
                            <option value="">Tinggi & Kritis</option>
                            <option value="critical">Kritis Saja</option>
                            <option value="high">Tinggi Saja</option>
                            <option value="medium">Sedang</option>
                            <option value="low">Rendah</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ujian</label>
                        <select class="form-select" v-model="filterForm.exam_id" @change="applyFilters">
                            <option value="">Semua Ujian</option>
                            <option v-for="exam in upcomingExams" :key="exam.id" :value="exam.id">
                                {{ exam.title }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status Intervensi</label>
                        <select class="form-select" v-model="filterForm.status" @change="applyFilters">
                            <option value="">Semua Status</option>
                            <option value="pending">Pending</option>
                            <option value="notified">Sudah Dinotifikasi</option>
                            <option value="acknowledged">Sudah Dilihat</option>
                            <option value="intervened">Sudah Diintervensi</option>
                            <option value="resolved">Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button 
                            class="btn btn-outline-secondary w-100" 
                            @click="showCalculateModal = true"
                        >
                            <i class="fas fa-calculator"></i> Hitung Prediksi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Predictions Table -->
        <div class="card border-0 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle text-warning"></i> 
                        Daftar Siswa Berisiko
                    </h5>
                    <div v-if="selectedIds.length > 0">
                        <button class="btn btn-sm btn-outline-primary" @click="bulkAcknowledge">
                            <i class="fas fa-check"></i> Tandai {{ selectedIds.length }} Dilihat
                        </button>
                    </div>
                </div>
                <hr>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <input 
                                        type="checkbox" 
                                        class="form-check-input"
                                        @change="toggleSelectAll"
                                        :checked="isAllSelected"
                                    >
                                </th>
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th>Ujian</th>
                                <th class="text-center">Skor Risiko</th>
                                <th class="text-center">Level</th>
                                <th class="text-center">Prediksi Nilai</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="prediction in predictionsData" :key="prediction.id">
                                <td>
                                    <input 
                                        type="checkbox" 
                                        class="form-check-input"
                                        :value="prediction.id"
                                        v-model="selectedIds"
                                    >
                                </td>
                                <td>
                                    <strong>{{ prediction.student?.name || 'N/A' }}</strong>
                                </td>
                                <td>{{ prediction.student?.classroom?.title || '-' }}</td>
                                <td>{{ prediction.exam?.title || 'Umum' }}</td>
                                <td class="text-center">
                                    <span 
                                        class="badge fs-6" 
                                        :class="getRiskBadgeClass(prediction.risk_level)"
                                    >
                                        {{ prediction.risk_score }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span 
                                        class="badge" 
                                        :class="getRiskBadgeClass(prediction.risk_level)"
                                    >
                                        {{ getRiskLabel(prediction.risk_level) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span v-if="prediction.predicted_score" class="fw-bold">
                                        {{ prediction.predicted_score }}
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <span class="badge" :class="getStatusBadgeClass(prediction.intervention_status)">
                                        {{ getStatusLabel(prediction.intervention_status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <Link 
                                            :href="`/admin/analytics/at-risk/${prediction.id}`"
                                            class="btn btn-outline-primary"
                                            title="Detail"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </Link>
                                        <button 
                                            v-if="prediction.intervention_status === 'pending'"
                                            class="btn btn-outline-success"
                                            @click="acknowledge(prediction.id)"
                                            title="Tandai Dilihat"
                                        >
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button 
                                            v-if="['acknowledged', 'notified'].includes(prediction.intervention_status)"
                                            class="btn btn-outline-warning"
                                            @click="openInterveneModal(prediction)"
                                            title="Catat Intervensi"
                                        >
                                            <i class="fas fa-hand-holding-heart"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="predictionsData.length === 0">
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <p class="mb-0">Tidak ada siswa berisiko tinggi saat ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <Pagination v-if="predictions.links" :links="predictions.links" align="end" />
            </div>
        </div>

        <!-- Intervene Modal -->
        <div class="modal fade" id="interveneModal" tabindex="-1" ref="interveneModalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Catat Intervensi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p v-if="selectedPrediction">
                            <strong>Siswa:</strong> {{ selectedPrediction.student?.name }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Catatan Intervensi</label>
                            <textarea 
                                class="form-control" 
                                v-model="interveneForm.notes" 
                                rows="4"
                                placeholder="Jelaskan tindakan yang telah dilakukan..."
                            ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button 
                            type="button" 
                            class="btn btn-primary" 
                            @click="submitIntervention"
                            :disabled="isSubmitting"
                        >
                            <span v-if="isSubmitting">
                                <i class="fas fa-spinner fa-spin"></i> Menyimpan...
                            </span>
                            <span v-else>
                                <i class="fas fa-save"></i> Simpan
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calculate Modal -->
        <div class="modal fade" id="calculateModal" tabindex="-1" v-if="showCalculateModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Hitung Prediksi Risiko</h5>
                        <button type="button" class="btn-close" @click="showCalculateModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pilih Ujian</label>
                            <select class="form-select" v-model="calculateForm.exam_id">
                                <option value="">-- Pilih Ujian --</option>
                                <option v-for="exam in upcomingExams" :key="exam.id" :value="exam.id">
                                    {{ exam.title }}
                                </option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Sistem akan menghitung prediksi risiko untuk semua siswa yang terdaftar di ujian ini.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showCalculateModal = false">Batal</button>
                        <button 
                            type="button" 
                            class="btn btn-primary" 
                            @click="calculateRisks"
                            :disabled="!calculateForm.exam_id || isCalculating"
                        >
                            <span v-if="isCalculating">
                                <i class="fas fa-spinner fa-spin"></i> Menghitung...
                            </span>
                            <span v-else>
                                <i class="fas fa-calculator"></i> Hitung
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: {
        predictions: {
            type: [Array, Object],
            default: () => []
        },
        summary: {
            type: Object,
            default: () => ({})
        },
        upcomingExams: {
            type: Array,
            default: () => []
        },
        filters: {
            type: Object,
            default: () => ({})
        }
    },
    setup(props) {
        const filterForm = ref({
            risk_level: props.filters.risk_level || '',
            exam_id: props.filters.exam_id || '',
            status: props.filters.status || ''
        });

        const selectedIds = ref([]);
        const selectedPrediction = ref(null);
        const interveneForm = ref({ notes: '' });
        const calculateForm = ref({ exam_id: '' });
        const showCalculateModal = ref(false);
        const isSubmitting = ref(false);
        const isCalculating = ref(false);
        const interveneModalRef = ref(null);
        let interveneModal = null;

        const predictionsData = computed(() => {
            if (Array.isArray(props.predictions)) {
                return props.predictions;
            }
            return props.predictions.data || [];
        });

        const isAllSelected = computed(() => {
            return predictionsData.value.length > 0 && 
                   selectedIds.value.length === predictionsData.value.length;
        });

        onMounted(() => {
            if (typeof bootstrap !== 'undefined' && interveneModalRef.value) {
                interveneModal = new bootstrap.Modal(interveneModalRef.value);
            }
        });

        const applyFilters = () => {
            const params = {};
            if (filterForm.value.risk_level) params.risk_level = filterForm.value.risk_level;
            if (filterForm.value.exam_id) params.exam_id = filterForm.value.exam_id;
            if (filterForm.value.status) params.status = filterForm.value.status;

            router.get('/admin/analytics/at-risk', params, {
                preserveState: true,
                preserveScroll: true
            });
        };

        const toggleSelectAll = (e) => {
            if (e.target.checked) {
                selectedIds.value = predictionsData.value.map(p => p.id);
            } else {
                selectedIds.value = [];
            }
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

        const acknowledge = (id) => {
            router.post(`/admin/analytics/at-risk/${id}/acknowledge`, {}, {
                preserveScroll: true
            });
        };

        const bulkAcknowledge = () => {
            router.post('/admin/analytics/at-risk/bulk-acknowledge', {
                prediction_ids: selectedIds.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedIds.value = [];
                }
            });
        };

        const openInterveneModal = (prediction) => {
            selectedPrediction.value = prediction;
            interveneForm.value.notes = '';
            if (interveneModal) {
                interveneModal.show();
            }
        };

        const submitIntervention = () => {
            if (!selectedPrediction.value) return;

            isSubmitting.value = true;
            router.post(`/admin/analytics/at-risk/${selectedPrediction.value.id}/intervene`, {
                notes: interveneForm.value.notes
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    if (interveneModal) interveneModal.hide();
                    selectedPrediction.value = null;
                },
                onFinish: () => {
                    isSubmitting.value = false;
                }
            });
        };

        const calculateRisks = () => {
            isCalculating.value = true;
            router.post('/admin/analytics/calculate-risks', {
                exam_id: calculateForm.value.exam_id
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    showCalculateModal.value = false;
                    calculateForm.value.exam_id = '';
                },
                onFinish: () => {
                    isCalculating.value = false;
                }
            });
        };

        return {
            filterForm,
            selectedIds,
            selectedPrediction,
            interveneForm,
            calculateForm,
            showCalculateModal,
            isSubmitting,
            isCalculating,
            interveneModalRef,
            predictionsData,
            isAllSelected,
            applyFilters,
            toggleSelectAll,
            getRiskBadgeClass,
            getRiskLabel,
            getStatusBadgeClass,
            getStatusLabel,
            acknowledge,
            bulkAcknowledge,
            openInterveneModal,
            submitIntervention,
            calculateRisks
        };
    }
}
</script>
