<template>
    <Head>
        <title>Tambah Siswa - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link href="/admin/students" class="btn btn-md btn-primary border-0 shadow mb-3" type="button"><i class="fas fa-long-arrow-alt-left me-2"></i> Kembali</Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-user"></i> Tambah Siswa</h5>
                        <hr>
                        <form @submit.prevent="submit">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>NISN</label> 
                                        <input type="text" class="form-control" placeholder="Masukkan NISN Siswa" v-model="form.nisn">
                                        <div v-if="errors.nisn" class="alert alert-danger mt-2">{{ errors.nisn }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Nama Lengkap</label> 
                                        <input type="text" class="form-control" placeholder="Masukkan Nama Siswa" v-model="form.name">
                                        <div v-if="errors.name" class="alert alert-danger mt-2">{{ errors.name }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Kelas</label> 
                                        <select class="form-select" v-model="form.classroom_id">
                                            <option value="">-- Pilih Kelas --</option>
                                            <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
                                                {{ classroom.title }}
                                            </option>
                                        </select>
                                        <div v-if="errors.classroom_id" class="alert alert-danger mt-2">{{ errors.classroom_id }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Jenis Kelamin</label> 
                                        <select class="form-select" v-model="form.gender">
                                            <option value="L">Laki - Laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                        <div v-if="errors.gender" class="alert alert-danger mt-2">{{ errors.gender }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-4">
                                        <label>Ruangan Ujian</label>
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input" id="autoAssign" v-model="form.auto_assign_room">
                                            <label class="form-check-label" for="autoAssign">
                                                <i class="fas fa-random me-1"></i> Auto Random Ruangan
                                            </label>
                                        </div>
                                        <select class="form-select" v-model="form.room_id" :disabled="form.auto_assign_room">
                                            <option value="">-- Pilih Ruangan --</option>
                                            <option v-for="room in rooms" :key="room.id" :value="room.id" :disabled="room.students_count >= room.capacity">
                                                {{ room.name }} ({{ room.students_count || 0 }}/{{ room.capacity }})
                                                <span v-if="room.students_count >= room.capacity"> - PENUH</span>
                                            </option>
                                        </select>
                                        <div v-if="errors.room_id" class="alert alert-danger mt-2">{{ errors.room_id }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Password</label> 
                                        <input type="password" class="form-control" placeholder="Masukkan Password" v-model="form.password">
                                        <div v-if="errors.password" class="alert alert-danger mt-2">{{ errors.password }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Konfirmasi Password</label> 
                                        <input type="password" class="form-control" placeholder="Masukkan Konfirmasi Password" v-model="form.password_confirmation">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-md btn-primary border-0 shadow me-2">Simpan</button>
                            <button type="reset" class="btn btn-md btn-warning border-0 shadow">Reset</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Swal from 'sweetalert2';

defineOptions({ layout: LayoutAdmin });

defineProps({
    errors: Object,
    classrooms: Array,
    rooms: Array,
});

const form = reactive({
    nisn: '',
    name: '',
    classroom_id: '',
    room_id: '',
    gender: '',
    password: '',
    password_confirmation: '',
    auto_assign_room: true,
});

const submit = () => {
    router.post('/admin/students', form, {
        onSuccess: () => {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Siswa Berhasil Disimpan.',
                icon: 'success',
                showConfirmButton: false,
                timer: 2000
            });
        },
    });
};
</script>
