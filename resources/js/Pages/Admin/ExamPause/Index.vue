<template>
    <Head><title>Pause/Resume Ujian - Admin</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa fa-pause-circle me-2"></i>Pause/Resume Ujian</h5>
                    </div>
                    <div class="card-body">
                        <!-- Filter -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Pilih Sesi Ujian</label>
                                <select class="form-select" v-model="selectedSession" @change="filterSession">
                                    <option value="">-- Pilih Sesi --</option>
                                    <option v-for="s in sessions" :key="s.id" :value="s.id">
                                        {{ s.title }} - {{ s.exam?.title }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end gap-2" v-if="selectedSession">
                                <button @click="pauseAll" class="btn btn-warning">
                                    <i class="fa fa-pause me-1"></i> Pause Semua
                                </button>
                                <button @click="resumeAll" class="btn btn-success">
                                    <i class="fa fa-play me-1"></i> Resume Semua
                                </button>
                            </div>
                        </div>

                        <!-- Active Exams Table -->
                        <div class="table-responsive" v-if="activeExams.length > 0">
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Siswa</th>
                                        <th>Kelas</th>
                                        <th>Ujian</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="exam in activeExams" :key="exam.id">
                                        <td>{{ exam.student?.name }}</td>
                                        <td>{{ exam.student?.classroom?.title }}</td>
                                        <td>{{ exam.exam?.title }}</td>
                                        <td>
                                            <span v-if="exam.is_paused" class="badge bg-warning">
                                                <i class="fa fa-pause me-1"></i> Paused
                                            </span>
                                            <span v-else class="badge bg-success">
                                                <i class="fa fa-play me-1"></i> Berjalan
                                            </span>
                                        </td>
                                        <td>
                                            <button v-if="!exam.is_paused" @click="pauseExam(exam)" class="btn btn-sm btn-warning">
                                                <i class="fa fa-pause"></i> Pause
                                            </button>
                                            <button v-else @click="resumeExam(exam)" class="btn btn-sm btn-success">
                                                <i class="fa fa-play"></i> Resume
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else-if="selectedSession" class="alert alert-info">
                            Tidak ada ujian aktif di sesi ini.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head },
    props: {
        sessions: Array,
        activeExams: Array,
        filters: Object,
    },
    setup(props) {
        const selectedSession = ref(props.filters?.session_id || '');

        const filterSession = () => {
            router.get('/admin/exam-pause', { session_id: selectedSession.value });
        };

        const pauseExam = async (exam) => {
            const { value: reason } = await Swal.fire({
                title: 'Pause Ujian',
                input: 'text',
                inputLabel: 'Alasan pause (wajib)',
                inputPlaceholder: 'Contoh: Listrik mati',
                showCancelButton: true,
                inputValidator: (value) => !value && 'Alasan harus diisi!'
            });
            if (reason) {
                router.post(`/admin/exam-pause/${exam.id}`, { reason });
            }
        };

        const resumeExam = (exam) => {
            router.post(`/admin/exam-resume/${exam.id}`);
        };

        const pauseAll = async () => {
            const { value: reason } = await Swal.fire({
                title: 'Pause Semua Ujian',
                input: 'text',
                inputLabel: 'Alasan pause (wajib)',
                showCancelButton: true,
                inputValidator: (value) => !value && 'Alasan harus diisi!'
            });
            if (reason) {
                router.post(`/admin/exam-pause-all/${selectedSession.value}`, { reason });
            }
        };

        const resumeAll = () => {
            Swal.fire({
                title: 'Resume Semua?',
                icon: 'question',
                showCancelButton: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    router.post(`/admin/exam-resume-all/${selectedSession.value}`);
                }
            });
        };

        return { selectedSession, filterSession, pauseExam, resumeExam, pauseAll, resumeAll };
    }
};
</script>
