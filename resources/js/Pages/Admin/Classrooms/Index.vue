<template>
    <Head>
        <title>Kelas - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-3 col-12 mb-2">
                        <Link href="/admin/classrooms/create" class="btn btn-md btn-primary border-0 shadow w-100" type="button"><i
                            class="fas fa-plus-circle"></i>
                        Tambah</Link>
                    </div>
                    <div class="col-md-9 col-12 mb-2">
                        <form @submit.prevent="handleSearch">
                            <div class="input-group">
                                <input type="text" class="form-control border-0 shadow" v-model="search" placeholder="masukkan kata kunci dan enter...">
                                <span class="input-group-text border-0 shadow">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-1">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="border-0 rounded-start" style="width:5%">No.</th>
                                        <th class="border-0">Nama Kelas</th>
                                        <th class="border-0 rounded-end" style="width:15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(classroom, index) in classrooms.data" :key="index">
                                        <td class="fw-bold text-center">{{ ++index + (classrooms.current_page - 1) * classrooms.per_page }}</td>
                                        <td>{{ classroom.title }}</td>
                                        <td class="text-center">
                                            <Link :href="`/admin/classrooms/${classroom.id}/edit`" class="btn btn-sm btn-info border-0 shadow me-2" type="button"><i class="fas fa-pencil-alt"></i></Link>
                                            <button @click.prevent="destroy(classroom.id)" class="btn btn-sm btn-danger border-0"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="classrooms.links" align="end" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import Swal from 'sweetalert2';

defineOptions({ layout: LayoutAdmin });

defineProps({ classrooms: Object });

const search = ref(new URL(document.location).searchParams.get('q') || '');

const handleSearch = () => {
    router.get('/admin/classrooms', { q: search.value });
};

const destroy = (id) => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Kelas akan dihapus!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/classrooms/${id}`);
        }
    });
};
</script>
