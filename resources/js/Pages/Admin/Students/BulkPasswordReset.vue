<template>
    <Head>
        <title>Bulk Reset Password - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-8">
                <Link href="/admin/students" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-arrow-left me-2"></i> Kembali
                </Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-key me-2"></i>Bulk Reset Password Siswa</h5>
                        <hr>

                        <form @submit.prevent="submit">
                            <!-- Select by Classroom -->
                            <div class="mb-4">
                                <label class="form-label">Pilih Kelas</label>
                                <select class="form-select" v-model="form.classroom_id" @change="loadStudents">
                                    <option value="">-- Semua siswa di kelas --</option>
                                    <option v-for="c in classrooms" :key="c.id" :value="c.id">
                                        {{ c.title }} ({{ c.students_count }} siswa)
                                    </option>
                                </select>
                            </div>

                            <!-- Select specific students -->
                            <div class="mb-4" v-if="students.length > 0">
                                <label class="form-label">Atau pilih siswa tertentu:</label>
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="selectAll" @change="toggleSelectAll" :checked="allSelected">
                                        <label class="form-check-label fw-bold" for="selectAll">Pilih Semua</label>
                                    </div>
                                    <hr class="my-2">
                                    <div v-for="s in students" :key="s.id" class="form-check">
                                        <input class="form-check-input" type="checkbox" :id="'student' + s.id" :value="s.id" v-model="form.student_ids">
                                        <label class="form-check-label" :for="'student' + s.id">{{ s.nisn }} - {{ s.name }}</label>
                                    </div>
                                </div>
                                <small class="text-muted">{{ form.student_ids.length }} siswa dipilih</small>
                            </div>

                            <!-- Password Type -->
                            <div class="mb-4">
                                <label class="form-label">Tipe Password Baru</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="typeNisn" value="nisn" v-model="form.password_type">
                                    <label class="form-check-label" for="typeNisn">
                                        <strong>NISN</strong> - Password = NISN masing-masing siswa
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="typeCustom" value="custom" v-model="form.password_type">
                                    <label class="form-check-label" for="typeCustom">
                                        <strong>Custom</strong> - Password sama untuk semua
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="typeRandom" value="random" v-model="form.password_type">
                                    <label class="form-check-label" for="typeRandom">
                                        <strong>Random</strong> - Password acak (akan ditampilkan hasilnya)
                                    </label>
                                </div>
                            </div>

                            <!-- Custom Password Input -->
                            <div class="mb-4" v-if="form.password_type === 'custom'">
                                <label class="form-label">Password Custom</label>
                                <input type="text" class="form-control" v-model="form.custom_password" placeholder="Masukkan password...">
                            </div>

                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                <strong>Perhatian!</strong> Tindakan ini akan mereset password siswa yang dipilih dan tidak dapat dibatalkan.
                            </div>

                            <button type="submit" class="btn btn-danger" :disabled="processing">
                                <i class="fa fa-key me-2"></i>
                                {{ processing ? 'Memproses...' : 'Reset Password' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Results for Random Password -->
                <div class="card border-0 shadow mt-4" v-if="results.length > 0">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fa fa-check me-2"></i>Password Baru (Random)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>NISN</th>
                                        <th>Nama</th>
                                        <th>Password Baru</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="r in results" :key="r.nisn">
                                        <td>{{ r.nisn }}</td>
                                        <td>{{ r.name }}</td>
                                        <td><code>{{ r.password }}</code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button @click="copyResults" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-copy me-1"></i> Copy to Clipboard
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        classrooms: Array,
    },
    data() {
        return {
            form: {
                classroom_id: '',
                student_ids: [],
                password_type: 'nisn',
                custom_password: '',
            },
            students: [],
            results: [],
            processing: false,
        };
    },
    computed: {
        allSelected() {
            return this.students.length > 0 && this.form.student_ids.length === this.students.length;
        }
    },
    methods: {
        async loadStudents() {
            this.form.student_ids = [];
            this.students = [];
            if (this.form.classroom_id) {
                const res = await axios.get(`/admin/students-by-classroom/${this.form.classroom_id}`);
                this.students = res.data;
            }
        },
        toggleSelectAll(e) {
            this.form.student_ids = e.target.checked ? this.students.map(s => s.id) : [];
        },
        submit() {
            if (!this.form.classroom_id && this.form.student_ids.length === 0) {
                Swal.fire('Error', 'Pilih kelas atau siswa terlebih dahulu.', 'error');
                return;
            }

            Swal.fire({
                title: 'Konfirmasi Reset Password',
                text: 'Apakah Anda yakin ingin mereset password?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Reset',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    this.processing = true;
                    router.post('/admin/students-bulk-password-reset', this.form, {
                        preserveScroll: true,
                        onSuccess: (page) => {
                            this.processing = false;
                            if (page.props.flash?.results) {
                                this.results = page.props.flash.results;
                            }
                            Swal.fire('Berhasil', page.props.flash?.success || 'Password berhasil direset.', 'success');
                        },
                        onError: () => {
                            this.processing = false;
                        }
                    });
                }
            });
        },
        copyResults() {
            const text = this.results.map(r => `${r.nisn}\t${r.name}\t${r.password}`).join('\n');
            navigator.clipboard.writeText(text);
            Swal.fire('Copied!', 'Data berhasil dicopy ke clipboard.', 'success');
        }
    }
}
</script>
