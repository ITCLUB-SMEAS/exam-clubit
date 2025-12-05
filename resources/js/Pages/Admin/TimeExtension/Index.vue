<template>
    <Head>
        <title>Perpanjangan Waktu - Admin</title>
    </Head>

    <main class="content">
        <div class="py-4">
            <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                    <li class="breadcrumb-item">
                        <Link href="/admin/dashboard">
                            <i class="fas fa-home"></i>
                        </Link>
                    </li>
                    <li class="breadcrumb-item active">Perpanjangan Waktu</li>
                </ol>
            </nav>

            <div class="mb-3">
                <h1 class="h4">Perpanjangan Waktu Ujian</h1>
                <p class="mb-0">Perpanjang waktu ujian untuk siswa yang sedang mengerjakan</p>
            </div>
        </div>

        <!-- Session Filter -->
        <div class="card border-0 shadow mb-4">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Pilih Sesi Ujian Aktif</label>
                        <select v-model="selectedSession" @change="filterBySession" class="form-select">
                            <option value="">-- Pilih Sesi --</option>
                            <option v-for="session in sessions" :key="session.id" :value="session.id">
                                {{ session.exam?.title }} - {{ formatDate(session.start_time) }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Exams -->
        <div class="card border-0 shadow" v-if="activeExams.length > 0">
            <div class="card-header">
                <h5 class="mb-0">Siswa Sedang Mengerjakan ({{ activeExams.length }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Siswa</th>
                                <th>NISN</th>
                                <th>Ujian</th>
                                <th>Durasi Awal</th>
                                <th>Perpanjangan</th>
                                <th>Total Waktu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="exam in activeExams" :key="exam.id">
                                <td>{{ exam.student_name }}</td>
                                <td>{{ exam.student_nisn }}</td>
                                <td>{{ exam.exam_title }}</td>
                                <td>{{ formatDuration(exam.duration) }}</td>
                                <td>
                                    <span v-if="exam.time_extension > 0" class="badge bg-success">
                                        +{{ exam.time_extension }} menit
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>{{ formatDuration(exam.total_time) }}</td>
                                <td>
                                    <button @click="openExtendModal(exam)" class="btn btn-sm btn-primary">
                                        <i class="fas fa-clock me-1"></i> Perpanjang
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow" v-else-if="selectedSession">
            <div class="card-body text-center py-5">
                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                <p class="text-muted">Tidak ada siswa yang sedang mengerjakan ujian di sesi ini</p>
            </div>
        </div>

        <!-- Extend Modal -->
        <div class="modal fade" id="extendModal" tabindex="-1" ref="extendModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Perpanjang Waktu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="submitExtension">
                        <div class="modal-body">
                            <p v-if="selectedExam">
                                Perpanjang waktu untuk <strong>{{ selectedExam.student_name }}</strong>
                            </p>
                            <div class="mb-3">
                                <label class="form-label">Tambah Waktu (menit)</label>
                                <input type="number" v-model="extendForm.minutes" class="form-control" 
                                    min="1" max="120" required>
                                <div class="form-text">Maksimal 120 menit</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alasan</label>
                                <textarea v-model="extendForm.reason" class="form-control" rows="2" 
                                    required placeholder="Contoh: Kendala teknis, koneksi terputus"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" :disabled="extendForm.processing">
                                <span v-if="extendForm.processing">
                                    <i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...
                                </span>
                                <span v-else>Perpanjang</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Swal from 'sweetalert2';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    sessions: Array,
    activeExams: Array,
    selectedSession: [String, Number],
});

const selectedSession = ref(props.selectedSession || '');
const selectedExam = ref(null);
let modalInstance = null;

const extendForm = useForm({
    minutes: 15,
    reason: '',
});

onMounted(() => {
    const modalEl = document.getElementById('extendModal');
    if (modalEl && window.bootstrap) {
        modalInstance = new window.bootstrap.Modal(modalEl);
    }
});

const filterBySession = () => {
    router.get('/admin/time-extension', { session_id: selectedSession.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const formatDuration = (ms) => {
    const minutes = Math.floor(ms / 60000);
    return `${minutes} menit`;
};

const openExtendModal = (exam) => {
    selectedExam.value = exam;
    extendForm.reset();
    extendForm.minutes = 15;
    if (modalInstance) modalInstance.show();
};

const submitExtension = () => {
    extendForm.post(`/admin/time-extension/${selectedExam.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (modalInstance) modalInstance.hide();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: `Waktu berhasil diperpanjang ${extendForm.minutes} menit`,
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: errors.error || 'Terjadi kesalahan',
            });
        },
    });
};
</script>
