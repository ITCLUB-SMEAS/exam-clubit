<template>
    <Head>
        <title>Profil Saya - Ujian Online</title>
    </Head>
    <div class="row">
        <div class="col-md-12 mb-3">
            <Link href="/student/dashboard" class="btn btn-primary">
                <i class="fa fa-arrow-left"></i> Kembali
            </Link>
        </div>
    </div>

    <div class="row">
        <!-- Profile Info -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow">
                <div class="card-body">
                    <h5><i class="fa fa-user"></i> Informasi Profil</h5>
                    <hr>
                    
                    <div v-if="$page.props.session?.success" class="alert alert-success">
                        {{ $page.props.session.success }}
                    </div>

                    <form @submit.prevent="updateProfile">
                        <div class="mb-3">
                            <label class="form-label">NISN</label>
                            <input type="text" class="form-control" :value="student.nisn" disabled>
                            <small class="text-muted">NISN tidak dapat diubah</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <input type="text" class="form-control" :value="student.classroom?.title" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" v-model="profileForm.name" 
                                :class="{'is-invalid': errors.name}">
                            <div class="invalid-feedback">{{ errors.name }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select class="form-select" v-model="profileForm.gender"
                                :class="{'is-invalid': errors.gender}">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                            <div class="invalid-feedback">{{ errors.gender }}</div>
                        </div>
                        <button type="submit" class="btn btn-primary" :disabled="profileForm.processing">
                            <i class="fa fa-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow">
                <div class="card-body">
                    <h5><i class="fa fa-lock"></i> Ubah Password</h5>
                    <hr>

                    <form @submit.prevent="updatePassword">
                        <div class="mb-3">
                            <label class="form-label">Password Lama</label>
                            <input type="password" class="form-control" v-model="passwordForm.current_password"
                                :class="{'is-invalid': errors.current_password}">
                            <div class="invalid-feedback">{{ errors.current_password }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" v-model="passwordForm.password"
                                :class="{'is-invalid': errors.password}">
                            <div class="invalid-feedback">{{ errors.password }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" v-model="passwordForm.password_confirmation">
                        </div>
                        <button type="submit" class="btn btn-warning" :disabled="passwordForm.processing">
                            <i class="fa fa-key"></i> Ubah Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Info -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow">
                <div class="card-body">
                    <h5><i class="fa fa-info-circle"></i> Informasi Login</h5>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Login Terakhir:</strong> {{ formatDate(student.last_login_at) }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>IP Address:</strong> {{ student.last_login_ip || '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutStudent from '../../../Layouts/Student.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

export default {
    layout: LayoutStudent,
    components: { Head, Link },
    props: {
        student: Object,
        errors: Object,
    },
    setup(props) {
        const profileForm = useForm({
            name: props.student.name,
            gender: props.student.gender,
        });

        const passwordForm = useForm({
            current_password: '',
            password: '',
            password_confirmation: '',
        });

        const updateProfile = () => {
            profileForm.put('/student/profile');
        };

        const updatePassword = () => {
            passwordForm.put('/student/profile/password', {
                onSuccess: () => {
                    passwordForm.reset();
                }
            });
        };

        const formatDate = (date) => {
            if (!date) return '-';
            return new Date(date).toLocaleString('id-ID');
        };

        return { profileForm, passwordForm, updateProfile, updatePassword, formatDate };
    }
}
</script>
