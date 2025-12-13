<template>
    <Head>
        <title>Detail Sesi Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <Link href="/admin/exam_sessions" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fas fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <!-- Session Info -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-stopwatch"></i> Detail Sesi Ujian</h5>
                            <div class="d-flex gap-2">
                                <Link :href="`/admin/exam_sessions/${exam_session.id}/cards`" class="btn btn-success btn-sm">
                                    <i class="fas fa-id-card me-1"></i> Cetak Kartu
                                </Link>
                                <Link :href="`/admin/exam_sessions/${exam_session.id}/attendance`" class="btn btn-info btn-sm">
                                    <i class="fas fa-user-check me-1"></i> Absensi
                                </Link>
                                <button 
                                    v-if="!allPaused"
                                    @click="pauseAll" 
                                    class="btn btn-warning btn-sm"
                                    :disabled="pauseForm.processing"
                                >
                                    <i class="fas fa-pause me-1"></i> Pause Semua Ujian
                                </button>
                                <button 
                                    v-else
                                    @click="resumeAll" 
                                    class="btn btn-success btn-sm"
                                    :disabled="pauseForm.processing"
                                >
                                    <i class="fas fa-play me-1"></i> Resume Semua Ujian
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <td style="width:30%" class="fw-bold">Nama Ujian</td>
                                        <td>{{ exam_session.exam?.title || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Mata Pelajaran</td>
                                        <td>{{ exam_session.exam?.lesson?.title || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Kelas</td>
                                        <td>{{ exam_session.exam?.classroom?.title || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Sesi</td>
                                        <td>{{ exam_session.title }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Mulai</td>
                                        <td>{{ exam_session.start_time }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Selesai</td>
                                        <td>{{ exam_session.end_time }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Bulk Enrollment Card -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i> Bulk Enrollment (Per Kelas)</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Daftarkan seluruh siswa dalam satu kelas sekaligus</p>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Kelas</th>
                                                <th class="text-center">Terdaftar</th>
                                                <th class="text-center">Belum Terdaftar</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="classroom in classrooms" :key="classroom.id">
                                                <td>{{ classroom.title }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-success">{{ enrolledByClass[classroom.id] || 0 }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary">{{ classroom.students_count }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <button 
                                                        v-if="classroom.students_count > 0"
                                                        @click="bulkEnroll(classroom.id, classroom.title)"
                                                        class="btn btn-sm btn-success me-1"
                                                        :disabled="bulkForm.processing"
                                                    >
                                                        <i class="fas fa-plus"></i> Daftarkan
                                                    </button>
                                                    <button 
                                                        v-if="enrolledByClass[classroom.id] > 0"
                                                        @click="bulkUnenroll(classroom.id, classroom.title)"
                                                        class="btn btn-sm btn-outline-danger"
                                                        :disabled="bulkForm.processing"
                                                    >
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <span v-if="classroom.students_count === 0 && !enrolledByClass[classroom.id]" class="text-muted">
                                                        -
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrolled Students -->
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-user-plus"></i> Enrolled Siswa ({{ exam_session.exam_groups.total || 0 }})</h5>
                        <hr>
                        
                        <Link :href="`/admin/exam_sessions/${exam_session.id}/enrolle/create`" class="btn btn-md btn-primary border-0 shadow me-2">
                            <i class="fas fa-user-plus"></i> Enrolle Siswa Manual
                        </Link>
                        
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-centered mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr>
                                        <th style="width:5%">No.</th>
                                        <th>Nama Siswa</th>
                                        <th>Kelas</th>
                                        <th>Jenis Kelamin</th>
                                        <th style="width:10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(data, index) in exam_session.exam_groups.data" :key="data.id">
                                        <td class="text-center">{{ ++index + (exam_session.exam_groups.current_page - 1) * exam_session.exam_groups.per_page }}</td>
                                        <td>{{ data.student?.name || '-' }}</td>
                                        <td class="text-center">{{ data.student.classroom?.title }}</td>
                                        <td class="text-center">{{ data.student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                                        <td class="text-center">
                                            <button @click="destroy(exam_session.id, data.id)" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="exam_session.exam_groups.data.length === 0">
                                        <td colspan="5" class="text-center text-muted">Belum ada siswa terdaftar</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="exam_session.exam_groups.links" align="end" />
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import Swal from 'sweetalert2';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    exam_session: Object,
    classrooms: Array,
    enrolledByClass: Object,
    allPaused: Boolean,
});

const bulkForm = useForm({
    classroom_id: null,
});

const pauseForm = useForm({
    reason: 'Ujian di-pause oleh admin',
});

const pauseAll = () => {
    Swal.fire({
        title: 'Pause Semua Ujian?',
        text: 'Semua siswa yang sedang mengerjakan ujian akan di-pause.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Pause!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            pauseForm.post(`/admin/exam-pause-all/${props.exam_session.id}`, {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Semua ujian telah di-pause.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
            });
        }
    });
};

const resumeAll = () => {
    Swal.fire({
        title: 'Resume Semua Ujian?',
        text: 'Semua siswa dapat melanjutkan ujian.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Resume!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            pauseForm.post(`/admin/exam-resume-all/${props.exam_session.id}`, {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Semua ujian telah dilanjutkan.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
            });
        }
    });
};

const bulkEnroll = (classroomId, classroomTitle) => {
    Swal.fire({
        title: 'Daftarkan Seluruh Kelas?',
        text: `Semua siswa di ${classroomTitle} akan didaftarkan ke sesi ini.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Daftarkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            bulkForm.classroom_id = classroomId;
            bulkForm.post(`/admin/exam_sessions/${props.exam_session.id}/bulk-enroll`, {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Siswa berhasil didaftarkan.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
            });
        }
    });
};

const bulkUnenroll = (classroomId, classroomTitle) => {
    Swal.fire({
        title: 'Hapus Seluruh Kelas?',
        text: `Semua siswa di ${classroomTitle} akan dihapus dari sesi ini.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/exam_sessions/${props.exam_session.id}/bulk-unenroll`, {
                data: { classroom_id: classroomId },
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Siswa berhasil dihapus.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
            });
        }
    });
};

const destroy = (examSessionId, examGroupId) => {
    Swal.fire({
        title: 'Hapus Siswa?',
        text: "Siswa akan dihapus dari sesi ujian ini.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/exam_sessions/${examSessionId}/enrolle/${examGroupId}/destroy`);
        }
    });
};
</script>
