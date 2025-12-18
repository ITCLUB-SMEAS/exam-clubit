<template>
    <Head>
        <title>Import Siswa - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link href="/admin/students" class="btn btn-md btn-primary border-0 shadow mb-3 me-3" type="button"><i
                    class="fas fa-long-arrow-alt-left me-2"></i> Kembali</Link>
                <a href="/admin/students/template"
                    class="btn btn-md btn-success border-0 shadow mb-3 text-white" type="button"><i
                        class="fas fa-file-excel me-2"></i> Download Template</a>
                
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-user"></i> Import Siswa</h5>
                        <hr>

                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs mb-4" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link" :class="{ active: importMode === 'zip' }" @click="importMode = 'zip'">
                                    <i class="fas fa-file-archive me-1"></i> ZIP + Foto
                                    <span class="badge bg-success ms-1">Recommended</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" :class="{ active: importMode === 'excel' }" @click="importMode = 'excel'">
                                    <i class="fas fa-file-excel me-1"></i> Excel Saja
                                </button>
                            </li>
                        </ul>

                        <!-- ZIP Mode -->
                        <div v-if="importMode === 'zip'">
                            <div class="alert alert-info mb-4">
                                <h6><i class="fas fa-info-circle me-2"></i>Format File ZIP</h6>
                                <p class="mb-2">Upload file ZIP yang berisi:</p>
                                <div class="bg-dark text-light p-3 rounded mb-2" style="font-family: monospace; font-size: 13px;">
                                    <span class="text-warning">import_siswa.zip</span><br>
                                    ├── <span class="text-success">data.xlsx</span> atau <span class="text-success">data.csv</span><br>
                                    └── <span class="text-info">photos/</span><br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;├── <span class="text-light">1234567890.jpg</span> <small class="text-muted">(nama file = NISN)</small><br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;├── <span class="text-light">1234567891.png</span><br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;└── ...
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-lightbulb me-1"></i> 
                                    Foto harus diberi nama sesuai NISN siswa. Format: jpg, jpeg, png, webp
                                </small>
                            </div>

                            <form @submit.prevent="submitZip">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">File ZIP</label>
                                    <input type="file" class="form-control" accept=".zip" @input="form.file = $event.target.files[0]">
                                    <div v-if="errors.file" class="alert alert-danger mt-2">
                                        {{ errors.file }}
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-md btn-primary border-0 shadow me-2" :disabled="isLoading">
                                    <span v-if="isLoading"><i class="fas fa-spinner fa-spin me-1"></i> Memproses...</span>
                                    <span v-else><i class="fas fa-upload me-1"></i> Upload ZIP</span>
                                </button>
                                <button type="reset" class="btn btn-md btn-warning border-0 shadow" @click="form.file = ''">Reset</button>
                            </form>
                        </div>

                        <!-- Excel Only Mode -->
                        <div v-if="importMode === 'excel'">
                            <div class="alert alert-info mb-4">
                                <h6><i class="fas fa-info-circle me-2"></i>Format Kolom Excel</h6>
                                <ul class="mb-0 small">
                                    <li><strong>nisn</strong> - NISN siswa (wajib)</li>
                                    <li><strong>name</strong> - Nama lengkap siswa (wajib)</li>
                                    <li><strong>password</strong> - Password (default: 123456)</li>
                                    <li><strong>gender</strong> - Jenis kelamin: L/P (default: L)</li>
                                    <li><strong>classroom_id</strong> - ID Kelas (default: 1)</li>
                                    <li><strong>room_id</strong> - ID Ruangan (kosong/auto = otomatis)</li>
                                    <li><strong>photo_url</strong> - URL foto siswa (opsional)</li>
                                </ul>
                            </div>

                            <form @submit.prevent="submitExcel">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">File Excel</label>
                                    <input type="file" class="form-control" accept=".xlsx,.xls,.csv" @input="form.file = $event.target.files[0]">
                                    <div v-if="errors.file" class="alert alert-danger mt-2">
                                        {{ errors.file }}
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-md btn-primary border-0 shadow me-2" :disabled="isLoading">
                                    <span v-if="isLoading"><i class="fas fa-spinner fa-spin me-1"></i> Memproses...</span>
                                    <span v-else><i class="fas fa-upload me-1"></i> Upload Excel</span>
                                </button>
                                <button type="reset" class="btn btn-md btn-warning border-0 shadow" @click="form.file = ''">Reset</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import LayoutAdmin from '../../../Layouts/Admin.vue';
    import { Head, Link, router } from '@inertiajs/vue3';
    import { reactive, ref } from 'vue';
    import Swal from 'sweetalert2';

    export default {
        layout: LayoutAdmin,
        components: { Head, Link },
        props: {
            errors: Object,
        },

        setup() {
            const form = reactive({
                file: '',
            });

            const importMode = ref('zip');
            const isLoading = ref(false);

            const submitZip = () => {
                if (!form.file) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Pilih file ZIP terlebih dahulu.',
                        icon: 'error',
                    });
                    return;
                }

                isLoading.value = true;
                router.post('/admin/students/import-zip', {
                    file: form.file,
                }, {
                    onSuccess: () => {
                        isLoading.value = false;
                        Swal.fire({
                            title: 'Success!',
                            text: 'Import Siswa Berhasil!',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    },
                    onError: () => {
                        isLoading.value = false;
                    },
                    onFinish: () => {
                        isLoading.value = false;
                    }
                });
            };

            const submitExcel = () => {
                if (!form.file) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Pilih file Excel terlebih dahulu.',
                        icon: 'error',
                    });
                    return;
                }

                isLoading.value = true;
                router.post('/admin/students/import', {
                    file: form.file,
                }, {
                    onSuccess: () => {
                        isLoading.value = false;
                        Swal.fire({
                            title: 'Success!',
                            text: 'Import Siswa Berhasil!',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    },
                    onError: () => {
                        isLoading.value = false;
                    },
                    onFinish: () => {
                        isLoading.value = false;
                    }
                });
            };

            return {
                form,
                importMode,
                isLoading,
                submitZip,
                submitExcel
            };
        }
    }
</script>

<style scoped>
.nav-tabs .nav-link {
    cursor: pointer;
}
.nav-tabs .nav-link:not(.active) {
    color: #6c757d;
}
</style>