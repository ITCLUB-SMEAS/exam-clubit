<template>
    <Head>
        <title>Kategori Soal - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12 mb-3">
                <Link href="/admin/question-categories/create" class="btn btn-md btn-primary border-0 shadow">
                    <i class="fa fa-plus-circle"></i> Tambah Kategori
                </Link>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th style="width:5%">No.</th>
                                        <th>Nama Kategori</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Jumlah Soal</th>
                                        <th style="width:15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(cat, index) in categories.data" :key="cat.id">
                                        <td class="text-center">{{ ++index + (categories.current_page - 1) * categories.per_page }}</td>
                                        <td>{{ cat.name }}</td>
                                        <td>{{ cat.lesson?.title || '-' }}</td>
                                        <td class="text-center">{{ cat.question_banks_count }}</td>
                                        <td class="text-center">
                                            <Link :href="`/admin/question-categories/${cat.id}/edit`" class="btn btn-sm btn-info me-2">
                                                <i class="fa fa-pencil-alt"></i>
                                            </Link>
                                            <button @click="destroy(cat.id)" class="btn btn-sm btn-danger">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="categories.links" align="end" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: { categories: Object },
    setup() {
        const destroy = (id) => {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Kategori akan dihapus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    router.delete(`/admin/question-categories/${id}`);
                }
            });
        };
        return { destroy };
    }
}
</script>
