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
                        <Link :href="`/student/exam-start/${exam_group.id}`"
                            class="btn btn-md btn-success border-0 shadow w-100 mt-2 text-white">Mulai</Link>
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
    import { computed } from 'vue';

    export default {
        layout: LayoutStudent,
        components: { Head, Link },
        props: {
            exam_group: Object,
            grade: Object
        },
        setup(props) {
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

            return { canRetry, statusClass };
        }
    }
</script>
