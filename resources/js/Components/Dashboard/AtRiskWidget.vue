<template>
    <div class="card border-0 shadow h-100">
        <div class="card-header bg-warning bg-opacity-25 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                Siswa Berisiko
            </h6>
            <Link href="/admin/analytics/at-risk" class="btn btn-sm btn-outline-warning">
                Lihat Semua
            </Link>
        </div>
        <div class="card-body">
            <!-- Loading State -->
            <div v-if="loading" class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            </div>

            <!-- Data Loaded -->
            <div v-else>
                <!-- Summary Stats -->
                <div class="row text-center mb-3" v-if="summary">
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <h4 class="text-danger mb-0">{{ summary.critical_count || 0 }}</h4>
                            <small class="text-muted">Kritis</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <h4 class="text-warning mb-0">{{ summary.high_risk_count || 0 }}</h4>
                            <small class="text-muted">Tinggi</small>
                        </div>
                    </div>
                </div>

                <!-- Recent High Risk List -->
                <div v-if="recentHighRisk.length > 0">
                    <small class="text-muted d-block mb-2">Siswa dengan risiko tertinggi:</small>
                    <div 
                        v-for="student in recentHighRisk" 
                        :key="student.id"
                        class="d-flex justify-content-between align-items-center py-2 border-bottom"
                    >
                        <div>
                            <span class="fw-semibold">{{ student.student_name }}</span>
                        </div>
                        <span 
                            class="badge" 
                            :class="student.risk_level === 'critical' ? 'bg-danger' : 'bg-warning text-dark'"
                        >
                            {{ student.risk_score }}%
                        </span>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-3">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <p class="text-muted mb-0 small">Tidak ada siswa berisiko tinggi</p>
                </div>

                <!-- Accuracy Badge -->
                <div v-if="summary?.accuracy_rate" class="mt-3 text-center">
                    <small class="text-muted">
                        Akurasi prediksi: 
                        <span class="badge bg-success">{{ summary.accuracy_rate }}%</span>
                    </small>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

export default {
    name: 'AtRiskWidget',
    components: { Link },
    setup() {
        const loading = ref(true);
        const summary = ref(null);
        const recentHighRisk = ref([]);

        const fetchData = async () => {
            try {
                const response = await axios.get('/admin/analytics/at-risk-widget');
                summary.value = response.data.summary;
                recentHighRisk.value = response.data.recent_high_risk || [];
            } catch (error) {
                console.error('Failed to fetch at-risk widget data:', error);
            } finally {
                loading.value = false;
            }
        };

        onMounted(() => {
            fetchData();
        });

        return {
            loading,
            summary,
            recentHighRisk
        };
    }
}
</script>
