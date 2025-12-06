<template>
    <Head>
        <title>Cetak Kartu Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link :href="`/admin/exam_sessions/${examSession.id}`" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fas fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <!-- Info Ujian -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-id-card me-2"></i> Cetak Kartu Peserta Ujian</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td width="150">Nama Ujian</td>
                                        <td>: <strong>{{ examSession.exam?.title || '-' }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Mata Pelajaran</td>
                                        <td>: {{ examSession.exam.lesson?.title || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td>Sesi</td>
                                        <td>: {{ examSession.title }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td width="150">Waktu Mulai</td>
                                        <td>: {{ formatDate(examSession.start_time) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Waktu Selesai</td>
                                        <td>: {{ formatDate(examSession.end_time) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Jumlah Peserta</td>
                                        <td>: <span class="badge bg-success">{{ students.length }} siswa</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex gap-2">
                            <a :href="`/admin/exam_sessions/${examSession.id}/cards/print`" 
                               class="btn btn-success" 
                               target="_blank">
                                <i class="fas fa-print me-1"></i> Cetak Semua Kartu (PDF)
                            </a>
                            <button @click="selectAll" class="btn btn-outline-primary">
                                <i class="fas fa-check-square me-1"></i> Pilih Semua
                            </button>
                            <button @click="deselectAll" class="btn btn-outline-secondary">
                                <i class="fas fa-square me-1"></i> Batal Pilih
                            </button>
                            <button @click="printSelected" class="btn btn-info" :disabled="selectedStudents.length === 0">
                                <i class="fas fa-print me-1"></i> Cetak Terpilih ({{ selectedStudents.length }})
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Daftar Siswa -->
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="fas fa-users me-2"></i> Daftar Peserta</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="50" class="text-center">
                                            <input type="checkbox" 
                                                   :checked="selectedStudents.length === students.length && students.length > 0"
                                                   @change="toggleAll">
                                        </th>
                                        <th width="50">No</th>
                                        <th>NISN</th>
                                        <th>Nama Siswa</th>
                                        <th>Kelas</th>
                                        <th>L/P</th>
                                        <th width="120" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(student, index) in students" :key="student.id">
                                        <td class="text-center">
                                            <input type="checkbox" 
                                                   :value="student.id" 
                                                   v-model="selectedStudents">
                                        </td>
                                        <td class="text-center">{{ index + 1 }}</td>
                                        <td>{{ student.nisn }}</td>
                                        <td>{{ student.name }}</td>
                                        <td>{{ student.classroom }}</td>
                                        <td class="text-center">{{ student.gender }}</td>
                                        <td class="text-center">
                                            <a :href="`/admin/exam_sessions/${examSession.id}/cards/print/${student.id}`" 
                                               class="btn btn-sm btn-outline-success"
                                               target="_blank"
                                               title="Cetak Kartu">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr v-if="students.length === 0">
                                        <td colspan="7" class="text-center text-muted py-4">
                                            Belum ada siswa terdaftar di sesi ini
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
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    examSession: Object,
    students: Array,
});

const selectedStudents = ref([]);

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const selectAll = () => {
    selectedStudents.value = props.students.map(s => s.id);
};

const deselectAll = () => {
    selectedStudents.value = [];
};

const toggleAll = (e) => {
    if (e.target.checked) {
        selectAll();
    } else {
        deselectAll();
    }
};

const printSelected = () => {
    if (selectedStudents.value.length === 0) return;
    const ids = selectedStudents.value.join(',');
    window.open(`/admin/exam_sessions/${props.examSession.id}/cards/print?students=${ids}`, '_blank');
};
</script>
