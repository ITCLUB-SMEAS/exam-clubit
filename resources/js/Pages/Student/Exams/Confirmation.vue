<template>
    <Head>
        <title>Konfirmasi Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="row">
        <div class="col-md-12">
            <Link href="/student/dashboard" class="btn btn-md btn-primary border-0 shadow mb-3" type="button"><i
                class="fa fa-long-arrow-alt-left me-2"></i> Kembali</Link>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow">
                <div class="card-body">
                    <h5> <i class="fa fa-file"></i> Deskripsi Ujian</h5>
                    <hr>
                    <div v-html="exam_group.exam.description"></div>
                </div>
            </div>

            <!-- Attendance Section -->
            <div v-if="attendance.required" class="card border-0 shadow mt-3">
                <div class="card-header" :class="attendance.checked_in ? 'bg-success text-white' : 'bg-warning'">
                    <h5 class="mb-0">
                        <i class="fa fa-user-check me-2"></i>
                        {{ attendance.checked_in ? 'Absensi Berhasil' : 'Absensi Diperlukan' }}
                    </h5>
                </div>
                <div class="card-body">
                    <div v-if="attendance.checked_in">
                        <p class="text-success mb-0">
                            <i class="fa fa-check-circle me-2"></i>
                            Anda sudah melakukan absensi pada {{ attendance.checked_in_at }}
                        </p>
                    </div>
                    <div v-else>
                        <p class="text-muted mb-3">Scan QR Code atau masukkan token dari pengawas untuk absensi.</p>
                        
                        <!-- QR Scanner -->
                        <div class="mb-3">
                            <button @click="showScanner = !showScanner" class="btn btn-outline-primary w-100">
                                <i class="fa fa-qrcode me-2"></i>
                                {{ showScanner ? 'Tutup Scanner' : 'Scan QR Code' }}
                            </button>
                            <div v-if="showScanner" class="mt-3">
                                <div ref="qrReader" id="qr-reader" style="width: 100%"></div>
                            </div>
                        </div>

                        <hr>

                        <!-- Token Input -->
                        <div class="mb-3">
                            <label class="form-label">Atau masukkan Token:</label>
                            <div class="input-group">
                                <input type="text" v-model="tokenInput" class="form-control text-uppercase" 
                                       maxlength="6" placeholder="XXXXXX" :disabled="checkinLoading">
                                <button @click="submitToken" class="btn btn-primary" :disabled="tokenInput.length !== 6 || checkinLoading">
                                    <i v-if="checkinLoading" class="fa fa-spinner fa-spin"></i>
                                    <span v-else>Submit</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="checkinError" class="alert alert-danger">{{ checkinError }}</div>
                        <div v-if="checkinSuccess" class="alert alert-success">{{ checkinSuccess }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow">
                <div class="card-body">
                    <h5> <i class="fa fa-list-ul"></i> Detail Peserta</h5>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0 rounded">
                            <thead>
                                <tr>
                                    <td class="fw-bold">Nisn</td>
                                    <td>{{ exam_group.student.nisn }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Nama Lengkap</td>
                                    <td>{{ exam_group.student.name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Kelas</td>
                                    <td>{{ exam_group.student.classroom.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Ujian</td>
                                    <td>{{ exam_group.exam.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Mata Pelajaran</td>
                                    <td>{{ exam_group.exam.lesson.title }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Durasi</td>
                                    <td>{{ exam_group.exam.duration }} Menit</td>
                                </tr>
                                <tr v-if="exam_group.exam.passing_grade > 0">
                                    <td class="fw-bold">KKM</td>
                                    <td>{{ exam_group.exam.passing_grade }}</td>
                                </tr>
                                <tr v-if="exam_group.exam.max_attempts > 1">
                                    <td class="fw-bold">Maks. Percobaan</td>
                                    <td>{{ exam_group.exam.max_attempts }}x</td>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    <!-- Belum mengerjakan -->
                    <div v-if="!grade || grade.end_time == null">
                        <Link v-if="canStartExam" :href="`/student/exam-start/${exam_group.id}`"
                            class="btn btn-md btn-success border-0 shadow w-100 mt-2 text-white">Mulai</Link>
                        <button v-else class="btn btn-md btn-secondary border-0 shadow w-100 mt-2" disabled>
                            <i class="fa fa-lock me-2"></i>Lakukan absensi terlebih dahulu
                        </button>
                    </div>

                    <!-- Sudah selesai -->
                    <div v-else>
                        <!-- Show result -->
                        <div class="alert mt-3" :class="statusClass">
                            <strong>Nilai: {{ grade.grade }}</strong>
                            <span v-if="exam_group.exam.passing_grade > 0">
                                - {{ grade.status === 'passed' ? 'LULUS' : 'TIDAK LULUS' }}
                            </span>
                        </div>

                        <!-- Remedial button if failed and attempts remaining -->
                        <div v-if="canRetry">
                            <p class="text-muted small">Percobaan ke-{{ grade.attempt_number || 1 }} dari {{ exam_group.exam.max_attempts }}</p>
                            <Link :href="`/student/exam-retry/${exam_group.id}`"
                                class="btn btn-md btn-warning border-0 shadow w-100 mt-2">
                                <i class="fa fa-redo"></i> Remedial (Percobaan {{ (grade.attempt_number || 1) + 1 }})
                            </Link>
                        </div>
                        <div v-else>
                            <button class="btn btn-md btn-primary border-0 shadow w-100 mt-2" disabled>Sudah Mengerjakan</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import LayoutStudent from '../../../Layouts/Student.vue';
    import { Head, Link } from '@inertiajs/vue3';
    import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
    import axios from 'axios';

    export default {
        layout: LayoutStudent,
        components: { Head, Link },
        props: {
            exam_group: Object,
            grade: Object,
            attendance: {
                type: Object,
                default: () => ({ required: false, checked_in: false })
            }
        },
        setup(props) {
            const showScanner = ref(false);
            const tokenInput = ref('');
            const checkinLoading = ref(false);
            const checkinError = ref('');
            const checkinSuccess = ref('');
            const isCheckedIn = ref(props.attendance.checked_in);
            let html5QrCode = null;

            const canRetry = computed(() => {
                if (!props.grade || !props.grade.end_time) return false;
                if (props.grade.status !== 'failed') return false;
                const maxAttempts = props.exam_group.exam.max_attempts || 1;
                const currentAttempt = props.grade.attempt_number || 1;
                return currentAttempt < maxAttempts;
            });

            const statusClass = computed(() => {
                if (!props.grade) return 'alert-secondary';
                if (props.grade.status === 'passed') return 'alert-success';
                if (props.grade.status === 'failed') return 'alert-danger';
                return 'alert-info';
            });

            const canStartExam = computed(() => {
                if (!props.attendance.required) return true;
                return isCheckedIn.value;
            });

            const submitToken = async () => {
                checkinLoading.value = true;
                checkinError.value = '';
                checkinSuccess.value = '';

                try {
                    const res = await axios.post('/student/checkin/token', {
                        token: tokenInput.value.toUpperCase(),
                        session_id: props.attendance.session_id
                    });
                    checkinSuccess.value = res.data.message;
                    isCheckedIn.value = true;
                    setTimeout(() => window.location.reload(), 1000);
                } catch (e) {
                    checkinError.value = e.response?.data?.message || 'Gagal melakukan absensi';
                } finally {
                    checkinLoading.value = false;
                }
            };

            const handleQrScan = async (decodedText) => {
                try {
                    const data = JSON.parse(decodedText);
                    if (data.session_id && data.qr_code) {
                        checkinLoading.value = true;
                        const res = await axios.post('/student/checkin/qr', {
                            qr_code: data.qr_code,
                            session_id: data.session_id
                        });
                        checkinSuccess.value = res.data.message;
                        isCheckedIn.value = true;
                        if (html5QrCode) html5QrCode.stop();
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } catch (e) {
                    checkinError.value = e.response?.data?.message || 'QR Code tidak valid';
                } finally {
                    checkinLoading.value = false;
                }
            };

            watch(showScanner, async (val) => {
                if (val) {
                    const { Html5Qrcode } = await import('html5-qrcode');
                    html5QrCode = new Html5Qrcode('qr-reader');
                    html5QrCode.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: 250 },
                        handleQrScan
                    ).catch(err => console.error('QR Scanner error:', err));
                } else if (html5QrCode) {
                    html5QrCode.stop().catch(() => {});
                }
            });

            onUnmounted(() => {
                if (html5QrCode) html5QrCode.stop().catch(() => {});
            });

            return { 
                canRetry, 
                statusClass, 
                canStartExam,
                showScanner,
                tokenInput,
                checkinLoading,
                checkinError,
                checkinSuccess,
                submitToken
            };
        }
    }
</script>
