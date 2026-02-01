<template>
    <!-- Violation Warning Modal -->
    <div v-if="showViolationWarning" class="modal fade show" tabindex="-1" style="display:block; background: rgba(0,0,0,0.7);" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-warning">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Peringatan Pelanggaran!
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-shield-alt fa-4x text-warning"></i>
                    </div>
                    <h5 class="text-danger">{{ lastViolationMessage }}</h5>
                    <p class="mb-2">
                        Total Pelanggaran: <strong>{{ violationCount }}</strong> / {{ maxViolations }}
                    </p>
                    <div class="progress mb-3" style="height: 20px;">
                        <div 
                            class="progress-bar"
                            :class="violationProgressClass"
                            role="progressbar"
                            :style="{ width: violationProgressWidth }"
                            :aria-valuenow="violationCount"
                            aria-valuemin="0"
                            :aria-valuemax="maxViolations"
                        >
                            {{ violationCount }} / {{ maxViolations }}
                        </div>
                    </div>
                    <p class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        Jika Anda mencapai batas maksimal pelanggaran, ujian dapat diakhiri secara otomatis.
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button @click="$emit('dismiss')" type="button" class="btn btn-warning">
                        <i class="fas fa-check me-1"></i> Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Auto Submit Modal (Max Violations) -->
    <div v-if="showAutoSubmitModal" class="modal fade show" tabindex="-1" style="display:block; background: rgba(0,0,0,0.8);" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-ban me-2"></i>
                        Batas Pelanggaran Tercapai!
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-times-circle fa-4x text-danger"></i>
                    </div>
                    <h5>Ujian akan diakhiri secara otomatis</h5>
                    <p>Anda telah mencapai batas maksimal pelanggaran ({{ maxViolations }}).</p>
                    <p class="text-muted">Ujian akan diakhiri dalam <strong>{{ autoSubmitCountdown }}</strong> detik...</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button @click="$emit('end-exam')" type="button" class="btn btn-danger">
                        <i class="fas fa-stop me-1"></i> Akhiri Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fullscreen Required Modal -->
    <div v-if="showFullscreenModal" class="modal fade show" tabindex="-1" style="display:block; background: rgba(0,0,0,0.9);" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-expand me-2"></i>
                        Mode Fullscreen Diperlukan
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-desktop fa-4x text-primary"></i>
                    </div>
                    <h5>Ujian ini memerlukan mode fullscreen</h5>
                    <p class="text-muted">Klik tombol di bawah untuk masuk ke mode fullscreen dan melanjutkan ujian.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button @click="$emit('request-fullscreen')" type="button" class="btn btn-primary btn-lg">
                        <i class="fas fa-expand me-1"></i> Masuk Fullscreen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Blocked Environment Modal -->
    <div v-if="showBlockedModal" class="modal fade show" tabindex="-1" style="display:block; background: rgba(0,0,0,0.95);" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-ban me-2"></i>
                        Lingkungan Tidak Diizinkan
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-circle fa-4x text-danger"></i>
                    </div>
                    <h5 class="text-danger">{{ blockedMessage }}</h5>
                    <p class="text-muted">
                        Ujian ini tidak dapat dilanjutkan dengan konfigurasi perangkat Anda saat ini.
                        Silakan gunakan satu monitor dan pastikan tidak menggunakan Virtual Machine.
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <a href="/student/dashboard" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Time End Modal -->
    <div v-if="showTimeEndModal" class="modal fade" :class="{ 'show': showTimeEndModal }" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" style="display:block;" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Waktu Habis !</h5>
                </div>
                <div class="modal-body">
                    Waktu ujian sudah berakhir!. Klik <strong class="fw-bold">Ya</strong> untuk mengakhiri ujian.
                </div>
                <div class="modal-footer">
                    <button @click="$emit('end-exam')" type="button" class="btn btn-primary">Ya</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Liveness Verification Modal -->
    <div v-if="showLivenessModal" class="modal fade show" tabindex="-1" style="display:block; background: rgba(0,0,0,0.9);" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-warning">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-user-check me-2"></i>
                        Verifikasi Kehadiran
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-camera fa-4x text-warning"></i>
                    </div>
                    <h5>{{ livenessChallenge?.instruction || 'Ikuti instruksi berikut' }}</h5>
                    <p class="text-muted">Pastikan wajah Anda terlihat jelas di kamera</p>
                    <div class="mt-3">
                        <div class="progress" style="height: 25px;">
                            <div 
                                class="progress-bar bg-warning progress-bar-striped progress-bar-animated" 
                                :style="{ width: (livenessCountdown / 15 * 100) + '%' }"
                            >
                                {{ livenessCountdown }} detik
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    showViolationWarning: {
        type: Boolean,
        default: false
    },
    showAutoSubmitModal: {
        type: Boolean,
        default: false
    },
    showFullscreenModal: {
        type: Boolean,
        default: false
    },
    showBlockedModal: {
        type: Boolean,
        default: false
    },
    showTimeEndModal: {
        type: Boolean,
        default: false
    },
    showLivenessModal: {
        type: Boolean,
        default: false
    },
    lastViolationMessage: {
        type: String,
        default: ''
    },
    violationCount: {
        type: Number,
        default: 0
    },
    maxViolations: {
        type: Number,
        default: 3
    },
    autoSubmitCountdown: {
        type: Number,
        default: 5
    },
    blockedMessage: {
        type: String,
        default: ''
    },
    livenessChallenge: {
        type: Object,
        default: null
    },
    livenessCountdown: {
        type: Number,
        default: 0
    }
});

defineEmits(['dismiss', 'end-exam', 'request-fullscreen']);

const violationProgressWidth = computed(() => {
    return `${Math.min(100, (props.violationCount / props.maxViolations) * 100)}%`;
});

const violationProgressClass = computed(() => {
    const ratio = props.violationCount / props.maxViolations;
    if (ratio >= 0.8) return 'bg-danger';
    if (ratio >= 0.5) return 'bg-warning';
    return 'bg-success';
});
</script>

<style scoped>
.modal.show {
    display: block;
}
</style>
