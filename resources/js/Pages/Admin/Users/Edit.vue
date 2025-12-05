<template>
    <Head>
        <title>Edit User - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-user-edit"></i> Edit User</h5>
                        <hr>
                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="form-label">Nama</label>
                                <input type="text" class="form-control" v-model="form.name" :class="{'is-invalid': errors.name}">
                                <div class="invalid-feedback">{{ errors.name }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" v-model="form.email" :class="{'is-invalid': errors.email}">
                                <div class="invalid-feedback">{{ errors.email }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" v-model="form.role" :class="{'is-invalid': errors.role}">
                                    <option value="guru">Guru</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <div class="invalid-feedback">{{ errors.role }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password Baru <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                                <input type="password" class="form-control" v-model="form.password" :class="{'is-invalid': errors.password}">
                                <div class="invalid-feedback">{{ errors.password }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" v-model="form.password_confirmation">
                            </div>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save"></i> Update
                            </button>
                            <Link href="/admin/users" class="btn btn-secondary ms-2">Kembali</Link>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: { user: Object, errors: Object },
    setup(props) {
        const form = useForm({
            name: props.user.name,
            email: props.user.email,
            role: props.user.role,
            password: '',
            password_confirmation: '',
        });
        const submit = () => form.put(`/admin/users/${props.user.id}`);
        return { form, submit };
    }
}
</script>
