<template>
    <Head>
        <title>Edit Ruangan - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link href="/admin/rooms" class="btn btn-md btn-primary border-0 shadow mb-3">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-door-open"></i> Edit Ruangan</h5>
                        <hr>
                        <form @submit.prevent="submit">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-4">
                                        <label>Nama Ruangan</label>
                                        <input type="text" class="form-control" v-model="form.name">
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
                            <button type="submit" class="btn btn-primary me-2">Update</button>
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

const props = defineProps({ errors: Object, room: Object });

const form = reactive({ name: props.room.name, capacity: props.room.capacity });

const submit = () => {
    router.put(`/admin/rooms/${props.room.id}`, form, {
        onSuccess: () => {
            Swal.fire({ title: 'Berhasil!', text: 'Ruangan berhasil diupdate.', icon: 'success', timer: 2000, showConfirmButton: false });
        }
    });
};
</script>
