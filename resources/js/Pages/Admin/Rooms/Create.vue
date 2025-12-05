<template>
    <Head>
        <title>Tambah Ruangan - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link href="/admin/rooms" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fas fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-door-open"></i> Tambah Ruangan</h5>
                        <hr>
                        <form @submit.prevent="submit">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-4">
                                        <label>Nama Ruangan</label>
                                        <input type="text" class="form-control" placeholder="Contoh: C.210, Lab 1" v-model="form.name">
                                        <div v-if="errors.name" class="alert alert-danger mt-2">{{ errors.name }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label>Kapasitas</label>
                                        <input type="number" class="form-control" v-model="form.capacity" min="1">
                                        <div v-if="errors.capacity" class="alert alert-danger mt-2">{{ errors.capacity }}</div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary me-2">Simpan</button>
                            <button type="reset" class="btn btn-warning">Reset</button>
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
defineProps({ errors: Object });

const form = reactive({ name: '', capacity: 40 });

const submit = () => {
    router.post('/admin/rooms', form, {
        onSuccess: () => {
            Swal.fire({ title: 'Berhasil!', text: 'Ruangan berhasil ditambahkan.', icon: 'success', timer: 2000, showConfirmButton: false });
        }
    });
};
</script>
