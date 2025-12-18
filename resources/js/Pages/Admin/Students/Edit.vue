<template>
    <Head>
        <title>Edit Siswa - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link href="/admin/students" class="btn btn-md btn-primary border-0 shadow mb-3" type="button"><i class="fas fa-long-arrow-alt-left me-2"></i> Kembali</Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-user"></i> Edit Siswa</h5>
                        <hr>
                        
                        <div class="row">
                            <!-- Left Column: Photo -->
                            <div class="col-md-3 text-center mb-4">
                                <div class="photo-container mb-3">
                                    <img 
                                        :src="photoPreview || currentPhotoUrl || '/images/default-avatar.svg'" 
                                        class="img-fluid rounded-circle shadow"
                                        style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #dee2e6;"
                                        alt="Foto Siswa"
                                    >
                                </div>
                                
                                <div class="d-flex flex-column gap-2">
                                    <label class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-camera me-1"></i> Ganti Foto
                                        <input 
                                            type="file" 
                                            class="d-none" 
                                            accept="image/jpeg,image/png,image/webp"
                                            @change="handlePhotoChange"
                                        >
                                    </label>
                                    
                                    <button 
                                        v-if="photoPreview || student.photo" 
                                        type="button" 
                                        class="btn btn-sm btn-outline-danger"
                                        @click="removePhoto"
                                    >
                                        <i class="fas fa-trash me-1"></i> Hapus Foto
                                    </button>
                                </div>
                                
                                <small class="text-muted d-block mt-2">
                                    Format: JPG, PNG, WEBP<br>Max: 2MB
                                </small>
                                
                                <div v-if="errors.photo" class="alert alert-danger mt-2 small">
                                    {{ errors.photo }}
                                </div>
                            </div>
                            
                            <!-- Right Column: Form -->
                            <div class="col-md-9">
                                <form @submit.prevent="submit">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label">NISN</label> 
                                                <input type="text" class="form-control" placeholder="Masukkan Nisn Siswa" v-model="form.nisn">
                                                <div v-if="errors.nisn" class="alert alert-danger mt-2">
                                                    {{ errors.nisn }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label">Nama Lengkap</label> 
                                                <input type="text" class="form-control" placeholder="Masukkan Nama Siswa" v-model="form.name">
                                                <div v-if="errors.name" class="alert alert-danger mt-2">
                                                    {{ errors.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label">Kelas</label> 
                                                <select class="form-select" v-model="form.classroom_id">
                                                    <option v-for="(classroom, index) in classrooms" :key="index" :value="classroom.id">{{ classroom.title }}</option>
                                                </select>
                                                <div v-if="errors.classroom_id" class="alert alert-danger mt-2">
                                                    {{ errors.classroom_id }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label">Jenis Kelamin</label> 
                                                <select class="form-select" v-model="form.gender">
                                                    <option value="L">Laki - Laki</option>
                                                    <option value="P">Perempuan</option>
                                                </select>
                                                <div v-if="errors.gender" class="alert alert-danger mt-2">
                                                    {{ errors.gender }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label">Password <small class="text-muted">(kosongkan jika tidak diubah)</small></label> 
                                                <input type="password" class="form-control" placeholder="Masukkan Password" v-model="form.password">
                                                <div v-if="errors.password" class="alert alert-danger mt-2">
                                                    {{ errors.password }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label">Konfirmasi Password</label> 
                                                <input type="password" class="form-control" placeholder="Masukkan Konfirmasi Password" v-model="form.password_confirmation">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    <button type="submit" class="btn btn-md btn-primary border-0 shadow me-2" :disabled="isSubmitting">
                                        <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...</span>
                                        <span v-else><i class="fas fa-save me-1"></i> Update</span>
                                    </button>
                                    <button type="reset" class="btn btn-md btn-warning border-0 shadow" @click="resetForm">Reset</button>
                                </form>
                            </div>
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
    import { reactive, ref, computed } from 'vue';
    import Swal from 'sweetalert2';

    export default {
        layout: LayoutAdmin,
        components: { Head, Link },
        props: {
            errors: Object,
            classrooms: Array,
            student: Object
        },

        setup(props) {
            const form = reactive({
                nisn: props.student.nisn,
                name: props.student.name,
                classroom_id: props.student.classroom_id,
                gender: props.student.gender,
                password: '',
                password_confirmation: ''
            });

            const photoFile = ref(null);
            const photoPreview = ref(null);
            const removePhotoFlag = ref(false);
            const isSubmitting = ref(false);

            const currentPhotoUrl = computed(() => {
                if (props.student.photo) {
                    return `/storage/${props.student.photo}`;
                }
                return null;
            });

            const handlePhotoChange = (event) => {
                const file = event.target.files[0];
                if (!file) return;

                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Ukuran file maksimal 2MB.',
                        icon: 'error',
                    });
                    event.target.value = '';
                    return;
                }

                // Validate file type
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Format file harus JPG, PNG, atau WEBP.',
                        icon: 'error',
                    });
                    event.target.value = '';
                    return;
                }

                photoFile.value = file;
                removePhotoFlag.value = false;

                // Create preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    photoPreview.value = e.target.result;
                };
                reader.readAsDataURL(file);
            };

            const removePhoto = () => {
                Swal.fire({
                    title: 'Hapus Foto?',
                    text: 'Foto siswa akan dihapus.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        photoFile.value = null;
                        photoPreview.value = null;
                        removePhotoFlag.value = true;
                    }
                });
            };

            const resetForm = () => {
                form.nisn = props.student.nisn;
                form.name = props.student.name;
                form.classroom_id = props.student.classroom_id;
                form.gender = props.student.gender;
                form.password = '';
                form.password_confirmation = '';
                photoFile.value = null;
                photoPreview.value = null;
                removePhotoFlag.value = false;
            };

            const submit = () => {
                isSubmitting.value = true;

                // Use FormData for file upload
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('nisn', form.nisn);
                formData.append('name', form.name);
                formData.append('classroom_id', form.classroom_id);
                formData.append('gender', form.gender);
                
                if (form.password) {
                    formData.append('password', form.password);
                    formData.append('password_confirmation', form.password_confirmation);
                }

                if (photoFile.value) {
                    formData.append('photo', photoFile.value);
                }

                if (removePhotoFlag.value) {
                    formData.append('remove_photo', '1');
                }

                router.post(`/admin/students/${props.student.id}`, formData, {
                    forceFormData: true,
                    onSuccess: () => {
                        isSubmitting.value = false;
                        Swal.fire({
                            title: 'Success!',
                            text: 'Siswa Berhasil Diupdate!',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    },
                    onError: () => {
                        isSubmitting.value = false;
                    },
                    onFinish: () => {
                        isSubmitting.value = false;
                    }
                });
            };

            return {
                form,
                photoPreview,
                currentPhotoUrl,
                isSubmitting,
                handlePhotoChange,
                removePhoto,
                resetForm,
                submit,
            };
        }
    }
</script>

<style scoped>
.photo-container {
    position: relative;
    display: inline-block;
}

.photo-container img {
    transition: opacity 0.2s;
}

.photo-container:hover img {
    opacity: 0.8;
}
</style>