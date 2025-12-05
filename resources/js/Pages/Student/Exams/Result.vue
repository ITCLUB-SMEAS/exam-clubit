<template>
    <Head>
        <title>Hasil Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <div class="card border-0 shadow">
                <div class="card-body">
                    <h5> <i class="fas fa-check-circle"></i> Ujian Selesai</h5>
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
                                    <td class="fw-bold">Mulai Mengerjakan</td>
                                    <td>{{ grade.start_time }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Selesai Mengerjakan</td>
                                    <td>{{ grade.end_time }}</td>
                                </tr>
                            </thead>
                            <tbody v-if="exam_group.exam.show_answer == 'Y'">
                                <tr>
                                    <td class="fw-bold">Jumlah Benar</td>
                                    <td>{{ grade.total_correct }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Nilai</td>
                                    <td>{{ grade.grade }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Violation Summary Card -->
            <div v-if="violation_summary && violation_summary.total > 0" class="card border-0 shadow mt-4">
                <div class="card-header" :class="violation_summary.is_flagged ? 'bg-danger text-white' : 'bg-warning'">
                    <h6 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Ringkasan Pelanggaran Anti-Cheat
                        <span v-if="violation_summary.is_flagged" class="badge bg-light text-danger ms-2">
                            <i class="fas fa-flag me-1"></i> Ditandai
                        </span>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 text-center">
                            <div class="border rounded p-3">
                                <h3 :class="getTotalViolationClass">{{ violation_summary.total }}</h3>
                                <small class="text-muted">Total Pelanggaran</small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        <tr v-if="violation_summary.tab_switch > 0">
                                            <td><i class="fas fa-window-restore text-warning me-2"></i> Pindah Tab/Window</td>
                                            <td class="text-end"><span class="badge bg-warning text-dark">{{ violation_summary.tab_switch }}</span></td>
                                        </tr>
                                        <tr v-if="violation_summary.fullscreen_exit > 0">
                                            <td><i class="fas fa-compress text-info me-2"></i> Keluar Fullscreen</td>
                                            <td class="text-end"><span class="badge bg-info">{{ violation_summary.fullscreen_exit }}</span></td>
                                        </tr>
                                        <tr v-if="violation_summary.copy_paste > 0">
                                            <td><i class="fas fa-copy text-primary me-2"></i> Copy/Paste</td>
                                            <td class="text-end"><span class="badge bg-primary">{{ violation_summary.copy_paste }}</span></td>
                                        </tr>
                                        <tr v-if="violation_summary.right_click > 0">
                                            <td><i class="fas fa-mouse-pointer text-secondary me-2"></i> Klik Kanan</td>
                                            <td class="text-end"><span class="badge bg-secondary">{{ violation_summary.right_click }}</span></td>
                                        </tr>
                                        <tr v-if="violation_summary.blur > 0">
                                            <td><i class="fas fa-eye-slash text-dark me-2"></i> Window Blur</td>
                                            <td class="text-end"><span class="badge bg-dark">{{ violation_summary.blur }}</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div v-if="violation_summary.is_flagged && violation_summary.flag_reason" class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Alasan Ditandai:</strong> {{ violation_summary.flag_reason }}
                    </div>

                    <div v-else-if="violation_summary.total > 0 && !violation_summary.is_flagged" class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Terdapat beberapa pelanggaran namun masih dalam batas wajar. Pastikan untuk lebih fokus pada ujian berikutnya.
                    </div>
                </div>
            </div>

            <!-- No Violations Message -->
            <div v-else-if="violation_summary && violation_summary.total === 0" class="card border-0 shadow mt-4 border-success">
                <div class="card-body text-center py-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="text-success">Tidak Ada Pelanggaran</h5>
                    <p class="text-muted mb-0">Selamat! Anda menyelesaikan ujian tanpa pelanggaran anti-cheat.</p>
                </div>
            </div>

            <!-- Back to Dashboard Button -->
            <div class="text-center mt-4">
                <Link href="/student/dashboard" class="btn btn-primary btn-lg">
                    <i class="fas fa-home me-2"></i> Kembali ke Dashboard
                </Link>
            </div>
        </div>
    </div>
</template>

<script>
    //import layout student
    import LayoutStudent from '../../../Layouts/Student.vue';

    //import Head and Link from Inertia
    import {
        Head,
        Link
    } from '@inertiajs/vue3';

    //import computed from vue
    import { computed } from 'vue';

    export default {
        //layout
        layout: LayoutStudent,

        //register components
        components: {
            Head,
            Link
        },

        //props
        props: {
            exam_group: Object,
            grade: Object,
            violation_summary: {
                type: Object,
                default: null
            }
        },

        setup(props) {
            // Computed class for total violations
            const getTotalViolationClass = computed(() => {
                if (!props.violation_summary) return 'text-success';

                const total = props.violation_summary.total;
                if (total === 0) return 'text-success';
                if (total <= 3) return 'text-warning';
                if (total <= 7) return 'text-orange';
                return 'text-danger';
            });

            return {
                getTotalViolationClass
            };
        }
    }
</script>

<style scoped>
.text-orange {
    color: #fd7e14;
}

.border-success {
    border-left: 4px solid #198754 !important;
}
</style>
