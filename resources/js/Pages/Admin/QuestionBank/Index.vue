<template>
    <Head>
        <title>Bank Soal - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <!-- Action Buttons -->
        <div class="row mb-3">
            <div class="col-md-2">
                <Link href="/admin/question-bank/create" class="btn btn-primary w-100">
                    <i class="fas fa-plus-circle"></i> Tambah Soal
                </Link>
            </div>
            <div class="col-md-2">
                <button @click="showImportFromExamModal = true" class="btn btn-success w-100">
                    <i class="fas fa-file-import"></i> Import dari Ujian
                </button>
            </div>
            <div class="col-md-2" v-if="selectedIds.length > 0">
                <div class="dropdown">
                    <button class="btn btn-secondary w-100 dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-check-square"></i> {{ selectedIds.length }} dipilih
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" @click.prevent="showBulkTagsModal = true"><i class="fas fa-tags me-2"></i>Update Tags</a></li>
                        <li><a class="dropdown-item text-danger" href="#" @click.prevent="bulkDelete"><i class="fas fa-trash me-2"></i>Hapus</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-3">
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
            <div class="col-md-2">
                <select class="form-select" v-model="filters.difficulty" @change="applyFilter">
                    <option value="">Semua Difficulty</option>
                    <option value="easy">Mudah</option>
                    <option value="medium">Sedang</option>
                    <option value="hard">Sulit</option>
                </select>
            </div>
            <div class="col-md-4">
                <form @submit.prevent="applyFilter">
                    <div class="input-group">
                        <input type="text" class="form-control" v-model="filters.search" placeholder="Cari soal...">
                        <button class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th style="width:3%">
                                            <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected">
                                        </th>
                                        <th style="width:4%">No.</th>
                                        <th>Soal</th>
                                        <th>Kategori</th>
                                        <th>Tipe</th>
                                        <th>Difficulty</th>
                                        <th>Poin</th>
                                        <th>Dipakai</th>
                                        <th style="width:14%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(q, index) in questions.data" :key="q.id">
                                        <td class="text-center">
                                            <input type="checkbox" :value="q.id" v-model="selectedIds">
                                        </td>
                                        <td class="text-center">{{ ++index + (questions.current_page - 1) * questions.per_page }}</td>
                                        <td><div v-html="truncate(q.question, 80)"></div></td>
                                        <td>{{ q.category?.name || '-' }}</td>
                                        <td><span class="badge bg-secondary">{{ typeLabel(q.question_type) }}</span></td>
                                        <td><span :class="difficultyBadge(q.difficulty)">{{ difficultyLabel(q.difficulty) }}</span></td>
                                        <td class="text-center">{{ q.points }}</td>
                                        <td class="text-center">{{ q.usage_count || 0 }}x</td>
                                        <td class="text-center">
                                            <button @click="showPreview(q)" class="btn btn-sm btn-outline-primary me-1" title="Preview">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button @click="showStats(q)" class="btn btn-sm btn-outline-info me-1" title="Statistik">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                            <Link :href="`/admin/question-bank/${q.id}/edit`" class="btn btn-sm btn-info me-1">
                                                <i class="fas fa-pencil-alt"></i>
                                            </Link>
                                            <button @click="destroy(q.id)" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
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

    <!-- Preview Modal -->
    <div class="modal fade" :class="{ show: showPreviewModal }" :style="{ display: showPreviewModal ? 'block' : 'none' }" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Soal</h5>
                    <button type="button" class="btn-close" @click="showPreviewModal = false"></button>
                </div>
                <div class="modal-body" v-if="previewQuestion">
                    <div class="mb-3">
                        <strong>Soal:</strong>
                        <div v-html="previewQuestion.question" class="mt-2 p-3 bg-light rounded"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6" v-if="previewQuestion.option_1">
                            <p><strong>A.</strong> {{ previewQuestion.option_1 }}</p>
                            <p v-if="previewQuestion.option_2"><strong>B.</strong> {{ previewQuestion.option_2 }}</p>
                            <p v-if="previewQuestion.option_3"><strong>C.</strong> {{ previewQuestion.option_3 }}</p>
                            <p v-if="previewQuestion.option_4"><strong>D.</strong> {{ previewQuestion.option_4 }}</p>
                            <p v-if="previewQuestion.option_5"><strong>E.</strong> {{ previewQuestion.option_5 }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Jawaban:</strong> {{ previewQuestion.answer }}</p>
                            <p><strong>Tipe:</strong> {{ typeLabel(previewQuestion.question_type) }}</p>
                            <p><strong>Poin:</strong> {{ previewQuestion.points }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showPreviewModal" @click="showPreviewModal = false"></div>

    <!-- Statistics Modal -->
    <div class="modal fade" :class="{ show: showStatsModal }" :style="{ display: showStatsModal ? 'block' : 'none' }" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Statistik Soal</h5>
                    <button type="button" class="btn-close" @click="showStatsModal = false"></button>
                </div>
                <div class="modal-body" v-if="statsData">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h3>{{ statsData.usage_count }}</h3>
                                    <small>Kali Dipakai</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h3>{{ statsData.success_rate || 0 }}%</h3>
                                    <small>Success Rate</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p><strong>Difficulty:</strong> {{ difficultyLabel(statsData.difficulty) }}</p>
                    <p><strong>Terakhir Dipakai:</strong> {{ statsData.last_used_at || 'Belum pernah' }}</p>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showStatsModal" @click="showStatsModal = false"></div>

    <!-- Import from Exam Modal -->
    <div class="modal fade" :class="{ show: showImportFromExamModal }" :style="{ display: showImportFromExamModal ? 'block' : 'none' }" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Soal dari Ujian</h5>
                    <button type="button" class="btn-close" @click="showImportFromExamModal = false"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Ujian</label>
                        <select class="form-select" v-model="importForm.exam_id" @change="loadExamQuestions">
                            <option value="">-- Pilih Ujian --</option>
                            <option v-for="exam in exams" :key="exam.id" :value="exam.id">
                                {{ exam.title }} ({{ exam.questions_count }} soal)
                            </option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori Tujuan (Opsional)</label>
                        <select class="form-select" v-model="importForm.category_id">
                            <option value="">Tanpa Kategori</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div v-if="examQuestions.length > 0">
                        <label class="form-label">Pilih Soal (kosongkan untuk import semua)</label>
                        <div class="border rounded p-2" style="max-height: 300px; overflow-y: auto;">
                            <div v-for="q in examQuestions" :key="q.id" class="form-check">
                                <input type="checkbox" class="form-check-input" :value="q.id" v-model="importForm.question_ids">
                                <label class="form-check-label">{{ truncate(q.question, 60) }} ({{ q.points }} poin)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="showImportFromExamModal = false">Batal</button>
                    <button class="btn btn-primary" @click="importFromExam" :disabled="!importForm.exam_id">
                        <i class="fas fa-file-import me-1"></i> Import
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showImportFromExamModal" @click="showImportFromExamModal = false"></div>

    <!-- Bulk Tags Modal -->
    <div class="modal fade" :class="{ show: showBulkTagsModal }" :style="{ display: showBulkTagsModal ? 'block' : 'none' }" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Tags ({{ selectedIds.length }} soal)</h5>
                    <button type="button" class="btn-close" @click="showBulkTagsModal = false"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tags (pisahkan dengan koma)</label>
                        <input type="text" class="form-control" v-model="bulkTagsInput" placeholder="tag1, tag2, tag3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mode</label>
                        <select class="form-select" v-model="bulkTagsMode">
                            <option value="replace">Ganti semua tags</option>
                            <option value="append">Tambahkan ke tags existing</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="showBulkTagsModal = false">Batal</button>
                    <button class="btn btn-primary" @click="bulkUpdateTags">Update Tags</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show" v-if="showBulkTagsModal" @click="showBulkTagsModal = false"></div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: { questions: Object, categories: Array },
    setup(props) {
        const params = new URLSearchParams(window.location.search);
        const filters = ref({
            category_id: params.get('category_id') || '',
            question_type: params.get('question_type') || '',
            difficulty: params.get('difficulty') || '',
            search: params.get('search') || ''
        });

        const selectedIds = ref([]);
        const showPreviewModal = ref(false);
        const showStatsModal = ref(false);
        const showImportFromExamModal = ref(false);
        const showBulkTagsModal = ref(false);
        const previewQuestion = ref(null);
        const statsData = ref(null);
        const exams = ref([]);
        const examQuestions = ref([]);
        const importForm = ref({ exam_id: '', category_id: '', question_ids: [] });
        const bulkTagsInput = ref('');
        const bulkTagsMode = ref('replace');

        const isAllSelected = computed(() => 
            props.questions.data.length > 0 && selectedIds.value.length === props.questions.data.length
        );

        const applyFilter = () => {
            router.get('/admin/question-bank', Object.fromEntries(
                Object.entries(filters.value).filter(([_, v]) => v)
            ));
        };

        const toggleSelectAll = (e) => {
            selectedIds.value = e.target.checked ? props.questions.data.map(q => q.id) : [];
        };

        const typeLabel = (type) => ({
            multiple_choice_single: 'PG Single',
            multiple_choice_multiple: 'PG Multi',
            true_false: 'Benar/Salah',
            short_answer: 'Singkat',
            essay: 'Essay',
            matching: 'Menjodohkan'
        }[type] || type);

        const difficultyLabel = (d) => ({ easy: 'Mudah', medium: 'Sedang', hard: 'Sulit' }[d] || d);
        const difficultyBadge = (d) => ({ easy: 'badge bg-success', medium: 'badge bg-warning', hard: 'badge bg-danger' }[d] || 'badge bg-secondary');

        const truncate = (str, len) => {
            const text = str?.replace(/<[^>]*>/g, '') || '';
            return text.length > len ? text.substring(0, len) + '...' : text;
        };

        const showPreview = (q) => { previewQuestion.value = q; showPreviewModal.value = true; };

        const showStats = async (q) => {
            const res = await fetch(`/admin/question-bank/${q.id}/statistics`);
            statsData.value = await res.json();
            showStatsModal.value = true;
        };

        const destroy = (id) => {
            Swal.fire({ title: 'Hapus soal?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus!' })
                .then((r) => { if (r.isConfirmed) router.delete(`/admin/question-bank/${id}`); });
        };

        const bulkDelete = () => {
            Swal.fire({ title: `Hapus ${selectedIds.value.length} soal?`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus!' })
                .then((r) => { 
                    if (r.isConfirmed) {
                        router.post('/admin/question-bank-bulk-delete', { ids: selectedIds.value }, {
                            onSuccess: () => selectedIds.value = []
                        });
                    }
                });
        };

        const loadExams = async () => {
            const res = await fetch('/admin/question-bank-exams');
            exams.value = await res.json();
        };

        const loadExamQuestions = async () => {
            if (!importForm.value.exam_id) { examQuestions.value = []; return; }
            const res = await fetch(`/admin/question-bank-exam-questions/${importForm.value.exam_id}`);
            examQuestions.value = await res.json();
            importForm.value.question_ids = [];
        };

        const importFromExam = () => {
            router.post('/admin/question-bank-import-from-exam', importForm.value, {
                onSuccess: () => { showImportFromExamModal.value = false; importForm.value = { exam_id: '', category_id: '', question_ids: [] }; }
            });
        };

        const bulkUpdateTags = () => {
            const tags = bulkTagsInput.value.split(',').map(t => t.trim()).filter(t => t);
            router.post('/admin/question-bank-bulk-tags', { ids: selectedIds.value, tags, mode: bulkTagsMode.value }, {
                onSuccess: () => { showBulkTagsModal.value = false; selectedIds.value = []; bulkTagsInput.value = ''; }
            });
        };

        onMounted(loadExams);

        return {
            filters, applyFilter, typeLabel, difficultyLabel, difficultyBadge, truncate, destroy,
            selectedIds, isAllSelected, toggleSelectAll,
            showPreviewModal, previewQuestion, showPreview,
            showStatsModal, statsData, showStats,
            showImportFromExamModal, exams, examQuestions, importForm, loadExamQuestions, importFromExam,
            showBulkTagsModal, bulkTagsInput, bulkTagsMode, bulkDelete, bulkUpdateTags
        };
    }
}
</script>

<style scoped>
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1040; }
.modal { z-index: 1050; }
</style>
