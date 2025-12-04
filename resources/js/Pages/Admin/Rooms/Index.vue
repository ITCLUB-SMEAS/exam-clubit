<template>
    <Head>
        <title>Ruangan - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-3 col-12 mb-2">
                        <Link href="/admin/rooms/create" class="btn btn-md btn-primary border-0 shadow w-100">
                            <i class="fa fa-plus-circle"></i> Tambah
                        </Link>
                    </div>
                    <div class="col-md-9 col-12 mb-2">
                        <form @submit.prevent="handleSearch">
                            <div class="input-group">
                                <input type="text" class="form-control border-0 shadow" v-model="search" placeholder="Cari ruangan...">
                                <span class="input-group-text border-0 shadow">
                                    <i class="fa fa-search"></i>
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
                            <table class="table table-bordered table-centered mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr>
                                        <th style="width:5%">No.</th>
                                        <th>Nama Ruangan</th>
                                        <th class="text-center" style="width:12%">Kapasitas</th>
                                        <th class="text-center" style="width:12%">Terisi</th>
                                        <th class="text-center" style="width:12%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(room, index) in rooms.data" :key="room.id">
                                        <td class="text-center">{{ ++index + (rooms.current_page - 1) * rooms.per_page }}</td>
                                        <td>{{ room.name }}</td>
                                        <td class="text-center">{{ room.capacity }}</td>
                                        <td class="text-center">
                                            <span :class="room.students_count >= room.capacity ? 'badge bg-danger' : 'badge bg-success'">
                                                {{ room.students_count }} / {{ room.capacity }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <Link :href="`/admin/rooms/${room.id}/edit`" class="btn btn-sm btn-info me-1">
                                                <i class="fa fa-pencil-alt"></i>
                                            </Link>
                                            <button @click="destroy(room.id)" class="btn btn-sm btn-danger">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="rooms.data.length === 0">
                                        <td colspan="5" class="text-center text-muted py-3">Belum ada ruangan</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="rooms.links" align="end" />
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

defineProps({ rooms: Object });

const search = ref(new URL(document.location).searchParams.get('q') || '');

const handleSearch = () => {
    router.get('/admin/rooms', { q: search.value });
};

const destroy = (id) => {
    Swal.fire({
        title: 'Hapus Ruangan?',
        text: 'Siswa di ruangan ini akan kehilangan assignment ruangan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/rooms/${id}`);
        }
    });
};
</script>
