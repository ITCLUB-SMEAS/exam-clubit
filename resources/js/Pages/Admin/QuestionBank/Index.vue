<template>
    <Head>
        <title>Bank Soal - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row mb-3">
            <div class="col-md-2">
                <Link href="/admin/question-bank/create" class="btn btn-primary w-100">
                    <i class="fa fa-plus-circle"></i> Tambah Soal
                </Link>
            </div>
            <div class="col-md-3">
                <select class="form-select" v-model="filters.category_id" @change="applyFilter">
                    <option value="">Semua Kategori</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" v-model="filters.question_type" @change="applyFilter">
                    <option value="">Semua Tipe</option>
                    <option value="multiple_choice_single">Pilihan Ganda (Single)</option>
                    <option value="multiple_choice_multiple">Pilihan Ganda (Multiple)</option>
                    <option value="true_false">Benar/Salah</option>
                    <option value="short_answer">Jawaban Singkat</option>
                    <option value="essay">Essay</option>
                    <option value="matching">Menjodohkan</option>
                </select>
            </div>
            <div class="col-md-4">
                <form @submit.prevent="applyFilter">
                    <div class="input-group">
                        <input type="text" class="form-control" v-model="filters.search" placeholder="Cari soal...">
                        <button class="btn btn-outline-secondary"><i class="fa fa-search"></i></button>
                    </div>
                </form>
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
                                        <th>Soal</th>
                                        <th>Kategori</th>
                                        <th>Tipe</th>
                                        <th>Poin</th>
                                        <th style="width:12%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(q, index) in questions.data" :key="q.id">
                                        <td class="text-center">{{ ++index + (questions.current_page - 1) * questions.per_page }}</td>
                                        <td><div v-html="truncate(q.question, 100)"></div></td>
                                        <td>{{ q.category?.name || '-' }}</td>
                                        <td>{{ typeLabel(q.question_type) }}</td>
                                        <td class="text-center">{{ q.points }}</td>
                                        <td class="text-center">
                                            <Link :href="`/admin/question-bank/${q.id}/edit`" class="btn btn-sm btn-info me-1">
                                                <i class="fa fa-pencil-alt"></i>
                                            </Link>
                                            <button @click="destroy(q.id)" class="btn btn-sm btn-danger">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="questions.links" align="end" />
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
import { ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: { questions: Object, categories: Array },
    setup() {
        const params = new URLSearchParams(window.location.search);
        const filters = ref({
            category_id: params.get('category_id') || '',
            question_type: params.get('question_type') || '',
            search: params.get('search') || ''
        });

        const applyFilter = () => {
            router.get('/admin/question-bank', {
                category_id: filters.value.category_id || undefined,
                question_type: filters.value.question_type || undefined,
                search: filters.value.search || undefined
            });
        };

        const typeLabel = (type) => {
            const labels = {
                multiple_choice_single: 'Pilihan Ganda',
                multiple_choice_multiple: 'Pilihan Ganda (Multi)',
                true_false: 'Benar/Salah',
                short_answer: 'Jawaban Singkat',
                essay: 'Essay',
                matching: 'Menjodohkan'
            };
            return labels[type] || type;
        };

        const truncate = (str, len) => {
            const text = str.replace(/<[^>]*>/g, '');
            return text.length > len ? text.substring(0, len) + '...' : text;
        };

        const destroy = (id) => {
            Swal.fire({
                title: 'Hapus soal?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!'
            }).then((result) => {
                if (result.isConfirmed) router.delete(`/admin/question-bank/${id}`);
            });
        };

        return { filters, applyFilter, typeLabel, truncate, destroy };
    }
}
</script>
