<template>
    <Head>
        <title>Absensi Ujian - {{ examSession.title }}</title>
    </Head>
    <main class="content">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1">{{ examSession.title }}</h4>
                                <p class="text-muted mb-0">{{ examSession.exam?.title }}</p>
                            </div>
                            <div class="text-end">
                                <span class="badge" :class="examSession.require_attendance ? 'bg-success' : 'bg-secondary'">
                                    Absensi {{ examSession.require_attendance ? 'Wajib' : 'Tidak Wajib' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- QR Code Section -->
                    <div class="col-lg-5 mb-4">
                        <div class="card border-0 shadow">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>QR Code Absensi</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="qr-container mb-3">
                                    <canvas ref="qrCanvas" width="250" height="250"></canvas>
                                </div>
                                <p class="text-muted small mb-2">QR Code berubah setiap 30 detik</p>
                                <div class="d-flex justify-content-center gap-2 mb-3">
                                    <span class="badge bg-info">Refresh: {{ countdown }}s</span>
                                </div>
                                
                                <!-- Token Backup -->
                                <hr>
                                <p class="mb-2"><strong>Token Backup:</strong></p>
                                <h2 class="text-primary font-monospace">{{ examSession.access_token }}</h2>
                                <button @click="regenerateToken" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fas fa-refresh me-1"></i>Generate Token Baru
                                </button>
                            </div>
                        </div>

                        <!-- Settings -->
                        <div class="card border-0 shadow mt-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Pengaturan</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           :checked="examSession.require_attendance"
                                           @change="toggleRequirement"
                                           id="requireAttendance">
                                    <label class="form-check-label" for="requireAttendance">
                                        Wajibkan absensi sebelum ujian
                                    </label>
                                </div>
                                <small class="text-muted">Jika aktif, siswa harus scan QR/input token sebelum bisa memulai ujian</small>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance List -->
                    <div class="col-lg-7 mb-4">
                        <div class="card border-0 shadow">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Daftar Hadir</h5>
                                <span class="badge bg-success">{{ checkedInCount }}/{{ totalCount }} Hadir</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>NISN</th>
                                                <th>Nama</th>
                                                <th>Kelas</th>
                                                <th>Status</th>
                                                <th>Waktu</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in attendances" :key="item.id">
                                                <td>{{ item.nisn }}</td>
                                                <td>{{ item.student_name }}</td>
                                                <td>{{ item.classroom }}</td>
                                                <td>
                                                    <span v-if="item.checked_in" class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Hadir
                                                    </span>
                                                    <span v-else class="badge bg-secondary">Belum</span>
                                                </td>
                                                <td>
                                                    <span v-if="item.checked_in">
                                                        {{ formatDateTime(item.checked_in_at) }}
                                                        <small class="text-muted">({{ item.checkin_method }})</small>
                                                    </span>
                                                    <span v-else>-</span>
                                                </td>
                                                <td>
                                                    <button v-if="!item.checked_in" 
                                                            @click="manualCheckin(item.student_id)"
                                                            class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-check"></i>
                                                    </button>
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
        </div>
    </main>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

//import date format composable
import { formatDateTime } from '../../../composables/useDateFormat';

export default {
    layout: LayoutAdmin,
    components: { Head },
    props: {
        examSession: Object,
    },
    setup(props) {
        const qrCanvas = ref(null);
        const qrCode = ref('');
        const countdown = ref(30);
        const attendances = ref([]);
        let qrInterval = null;
        let countdownInterval = null;
        let attendanceInterval = null;

        const totalCount = computed(() => attendances.value.length);
        const checkedInCount = computed(() => attendances.value.filter(a => a.checked_in).length);

        const drawQrCode = async (data) => {
            if (!qrCanvas.value) return;
            const QRCode = (await import('qrcode')).default;
            const qrData = JSON.stringify({
                session_id: props.examSession.id,
                qr_code: data
            });
            QRCode.toCanvas(qrCanvas.value, qrData, { width: 250, margin: 2 });
        };

        const fetchQrCode = async () => {
            try {
                const res = await axios.get(`/admin/exam_sessions/${props.examSession.id}/attendance/qr`);
                qrCode.value = res.data.qr_code;
                drawQrCode(res.data.qr_code);
                countdown.value = 30;
            } catch (e) {
                console.error('Failed to fetch QR code', e);
            }
        };

        const fetchAttendances = async () => {
            try {
                const res = await axios.get(`/admin/exam_sessions/${props.examSession.id}/attendance/list`);
                attendances.value = res.data.attendances;
            } catch (e) {
                console.error('Failed to fetch attendances', e);
            }
        };

        const regenerateToken = () => {
            router.post(`/admin/exam_sessions/${props.examSession.id}/attendance/regenerate-token`);
        };

        const toggleRequirement = () => {
            router.post(`/admin/exam_sessions/${props.examSession.id}/attendance/toggle`);
        };

        const manualCheckin = (studentId) => {
            router.post(`/admin/exam_sessions/${props.examSession.id}/attendance/manual-checkin`, {
                student_id: studentId
            }, {
                onSuccess: () => fetchAttendances()
            });
        };

        onMounted(() => {
            fetchQrCode();
            fetchAttendances();
            
            // Refresh QR every 30 seconds
            qrInterval = setInterval(fetchQrCode, 30000);
            
            // Countdown timer
            countdownInterval = setInterval(() => {
                countdown.value = countdown.value > 0 ? countdown.value - 1 : 30;
            }, 1000);
            
            // Refresh attendance list every 5 seconds
            attendanceInterval = setInterval(fetchAttendances, 5000);
        });

        onUnmounted(() => {
            clearInterval(qrInterval);
            clearInterval(countdownInterval);
            clearInterval(attendanceInterval);
        });

        return {
            qrCanvas,
            countdown,
            attendances,
            totalCount,
            checkedInCount,
            regenerateToken,
            toggleRequirement,
            manualCheckin,
            formatDateTime,
        };
    }
};
</script>

<style scoped>
.qr-container {
    background: white;
    padding: 20px;
    border-radius: 10px;
    display: inline-block;
}
</style>
