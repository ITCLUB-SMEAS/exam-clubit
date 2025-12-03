<template>
    <Head>
        <title>Detail Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <Link href="/admin/exams" class="btn btn-md btn-primary border-0 shadow mb-3" type="button"><i class="fa fa-long-arrow-alt-left me-2"></i> Kembali</Link>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5> <i class="fa fa-edit"></i> Detail Ujian</h5>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                <tbody>
                                    <tr>
                                        <td style="width:30%" class="fw-bold">Nama Ujian</td>
                                        <td>{{ exam.title }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Mata Pelajaran</td>
                                        <td>{{ exam.lesson.title }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Kelas</td>
                                        <td>{{ exam.classroom.title }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Jumlah Soal</td>
                                        <td>{{ exam.questions.data.length }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Durasi (Menit)</td>
                                        <td>{{ exam.duration }} Menit</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                    </div>
                </div>

                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fa fa-question-circle"></i> Soal Ujian</h5>
                            <div>
                                <Link :href="`/admin/exams/${exam.id}/analysis`" class="btn btn-outline-success btn-sm me-2">
                                    <i class="fa fa-chart-bar me-1"></i> Analisis Soal
                                </Link>
                                <Link :href="`/admin/exams/${exam.id}/preview`" class="btn btn-outline-primary btn-sm">
                                    <i class="fa fa-eye me-1"></i> Preview Ujian
                                </Link>
                            </div>
                        </div>
                        <hr>
                        
                        <Link :href="`/admin/exams/${exam.id}/questions/create`" class="btn btn-md btn-primary border-0 shadow me-2" type="button"><i class="fa fa-plus-circle"></i> Tambah</Link>
                        <Link :href="`/admin/exams/${exam.id}/questions/import`" class="btn btn-md btn-success border-0 shadow text-white me-2" type="button"><i class="fa fa-file-excel"></i> Import Excel</Link>
                        <button @click="showBankModal = true" class="btn btn-md btn-info border-0 shadow text-white" type="button"><i class="fa fa-database"></i> Import dari Bank Soal</button>
                        
                        <!-- Modal Import dari Bank Soal -->
                        <div v-if="showBankModal" class="modal fade show d-block" style="background: rgba(0,0,0,0.5)">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Import dari Bank Soal</h5>
                                        <button type="button" class="btn-close" @click="showBankModal = false"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <select class="form-select" v-model="selectedCategory" @change="filterBankQuestions">
                                                <option value="">Semua Kategori</option>
                                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                            </select>
                                        </div>
                                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                            <table class="table table-bordered table-sm">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 40px"><input type="checkbox" @change="toggleAll" :checked="allSelected"></th>
                                                        <th>Soal</th>
                                                        <th>Tipe</th>
                                                        <th>Poin</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="q in filteredBankQuestions" :key="q.id">
                                                        <td><input type="checkbox" :value="q.id" v-model="selectedQuestions"></td>
                                                        <td><div v-html="truncate(q.question, 80)"></div></td>
                                                        <td>{{ typeLabel(q.question_type) }}</td>
                                                        <td>{{ q.points }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <p class="mt-2">Dipilih: {{ selectedQuestions.length }} soal</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" @click="showBankModal = false">Batal</button>
                                        <button type="button" class="btn btn-primary" @click="importFromBank" :disabled="selectedQuestions.length === 0">Import</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="border-0 rounded-start" style="width:5%">No.</th>
                                        <th class="border-0">Soal</th>
                                        <th class="border-0 rounded-end" style="width:15%">Aksi</th>
                                    </tr>
                                </thead>
                                <div class="mt-2"></div>
                                <tbody>
                                    <tr v-for="(question, index) in exam.questions.data" :key="index">
                                        <td class="fw-bold text-center">{{ ++index + (exam.questions.current_page - 1) * exam.questions.per_page }}</td>
                                        <td>
                                            <div class="fw-bold" v-html="question.question"></div>
                                            <hr>
                                            <ol type="A">
                                                <li v-text="question.option_1" :class="{ 'text-success fw-bold': question.answer == '1' }"></li>
                                                <li v-text="question.option_2" :class="{ 'text-success fw-bold': question.answer == '2' }"></li>
                                                <li v-text="question.option_3" :class="{ 'text-success fw-bold': question.answer == '3' }"></li>
                                                <li v-text="question.option_4" :class="{ 'text-success fw-bold': question.answer == '4' }"></li>
                                                <li v-text="question.option_5" :class="{ 'text-success fw-bold': question.answer == '5' }"></li>
                                            </ol>
                                        </td>
                                        <td class="text-center">
                                            <Link :href="`/admin/exams/${exam.id}/questions/${question.id}/edit`" class="btn btn-sm btn-info border-0 shadow me-2"
                                                type="button"><i class="fa fa-pencil-alt"></i></Link>
                                            <button @click.prevent="destroy(exam.id, question.id)" class="btn btn-sm btn-danger border-0"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="exam.questions.links" align="end" />
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
    import { ref, computed } from 'vue';
    import Swal from 'sweetalert2';

    export default {
        layout: LayoutAdmin,
        components: { Head, Link, Pagination },
        props: {
            errors: Object,
            exam: Object,
            bankQuestions: { type: Array, default: () => [] },
            categories: { type: Array, default: () => [] }
        },

        setup(props) {
            const showBankModal = ref(false);
            const selectedQuestions = ref([]);
            const selectedCategory = ref('');

            const filteredBankQuestions = computed(() => {
                if (!selectedCategory.value) return props.bankQuestions;
                return props.bankQuestions.filter(q => q.category_id == selectedCategory.value);
            });

            const allSelected = computed(() => 
                filteredBankQuestions.value.length > 0 && 
                filteredBankQuestions.value.every(q => selectedQuestions.value.includes(q.id))
            );

            const toggleAll = (e) => {
                if (e.target.checked) {
                    selectedQuestions.value = filteredBankQuestions.value.map(q => q.id);
                } else {
                    selectedQuestions.value = [];
                }
            };

            const filterBankQuestions = () => selectedQuestions.value = [];

            const typeLabel = (type) => ({
                multiple_choice_single: 'PG', multiple_choice_multiple: 'PG Multi',
                true_false: 'B/S', short_answer: 'Singkat', essay: 'Essay', matching: 'Jodoh'
            }[type] || type);

            const truncate = (str, len) => {
                const text = str.replace(/<[^>]*>/g, '');
                return text.length > len ? text.substring(0, len) + '...' : text;
            };

            const importFromBank = () => {
                router.post(`/admin/exams/${props.exam.id}/import-from-bank`, {
                    question_ids: selectedQuestions.value
                }, {
                    onSuccess: () => {
                        showBankModal.value = false;
                        selectedQuestions.value = [];
                    }
                });
            };

            const destroy = (exam_id, question_id) => {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Anda tidak akan dapat mengembalikan ini!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        router.delete(`/admin/exams/${exam_id}/questions/${question_id}/destroy`);
                    }
                });
            };

            return {
                destroy, showBankModal, selectedQuestions, selectedCategory,
                filteredBankQuestions, allSelected, toggleAll, filterBankQuestions,
                typeLabel, truncate, importFromBank
            };
        }
    }
</script>

<style>

</style>