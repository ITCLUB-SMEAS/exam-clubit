<template>
    <Head>
        <title>Penilaian Essay - Admin</title>
    </Head>

    <main class="content">
        <div class="py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h4">Penilaian Essay</h1>
                    <p class="mb-0 text-muted">Nilai jawaban essay dengan bantuan AI atau manual</p>
                </div>
                <span class="badge bg-warning fs-6" v-if="pendingCount > 0">
                    {{ pendingCount }} menunggu penilaian
                </span>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Pilih Ujian</label>
                        <select v-model="selectedExam" @change="onExamChange" class="form-select">
                            <option value="">-- Pilih Ujian --</option>
                            <option v-for="exam in exams" :key="exam.id" :value="exam.id">
                                {{ exam.title }} ({{ exam.lesson?.title }})
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pilih Sesi</label>
                        <select v-model="selectedSession" @change="onSessionChange" class="form-select" :disabled="!selectedExam">
                            <option value="">-- Pilih Sesi --</option>
                            <option v-for="session in sessions" :key="session.id" :value="session.id">
                                {{ session.title }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button 
                            @click="aiGradeSelected" 
                            class="btn btn-purple"
                            :disabled="selectedAnswers.length === 0 || aiLoading"
                        >
                            <i class="fas fa-robot me-1"></i>
                            <span v-if="aiLoading">Memproses...</span>
                            <span v-else>AI Grade ({{ selectedAnswers.length }})</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Answers List -->
        <div class="card border-0 shadow" v-if="answers.data && answers.data.length > 0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <input type="checkbox" class="form-check-input me-2" @change="toggleSelectAll" :checked="isAllSelected">
                    <h5 class="mb-0">Jawaban Essay ({{ answers.total }})</h5>
                </div>
                <button @click="saveAllGrades" class="btn btn-success btn-sm" :disabled="Object.keys(pendingGrades).length === 0">
                    <i class="fas fa-save me-1"></i> Simpan Semua ({{ Object.keys(pendingGrades).length }})
                </button>
            </div>
            <div class="card-body p-0">
                <div v-for="answer in answers.data" :key="answer.id" class="border-bottom p-4">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Student Info -->
                            <div class="d-flex align-items-center mb-2">
                                <input 
                                    type="checkbox" 
                                    class="form-check-input me-2" 
                                    :value="answer.id" 
                                    v-model="selectedAnswers"
                                    :disabled="answer.points_awarded !== null"
                                >
                                <span class="badge bg-primary me-2">{{ answer.student?.name }}</span>
                                <span class="badge bg-secondary">{{ answer.question?.question_type }}</span>
                                <span v-if="answer.points_awarded !== null" class="badge bg-success ms-2">
                                    Sudah dinilai
                                </span>
                            </div>

                            <!-- Question -->
                            <div class="mb-3">
                                <strong>Soal:</strong>
                                <div class="bg-light p-2 rounded mt-1" v-html="answer.question?.question"></div>
                            </div>

                            <!-- Answer -->
                            <div>
                                <strong>Jawaban Siswa:</strong>
                                <div class="border p-3 rounded mt-1 bg-white">
                                    <div v-if="answer.answer_text" v-html="answer.answer_text"></div>
                                    <div v-else class="text-muted fst-italic">Tidak ada jawaban</div>
                                </div>
                            </div>

                            <!-- AI Result -->
                            <div v-if="aiResults[answer.id]" class="mt-3 p-3 bg-purple-light rounded border border-purple">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><i class="fas fa-robot me-1"></i> Saran AI</strong>
                                    <button @click="applyAiGrade(answer.id)" class="btn btn-sm btn-purple">
                                        <i class="fas fa-check me-1"></i> Terapkan
                                    </button>
                                </div>
                                <p class="mb-1"><strong>Nilai:</strong> {{ aiResults[answer.id].score }} / {{ answer.question?.points || 1 }}</p>
                                <p class="mb-1"><strong>Feedback:</strong> {{ aiResults[answer.id].feedback }}</p>
                                <div v-if="aiResults[answer.id].strengths?.length" class="mb-1">
                                    <strong>Kelebihan:</strong>
                                    <ul class="mb-0 ps-3">
                                        <li v-for="s in aiResults[answer.id].strengths" :key="s">{{ s }}</li>
                                    </ul>
                                </div>
                                <div v-if="aiResults[answer.id].improvements?.length">
                                    <strong>Saran Perbaikan:</strong>
                                    <ul class="mb-0 ps-3">
                                        <li v-for="i in aiResults[answer.id].improvements" :key="i">{{ i }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="bg-light p-3 rounded">
                                <label class="form-label fw-bold">Nilai (Max: {{ answer.question?.points || 1 }})</label>
                                <div class="input-group mb-2">
                                    <input 
                                        type="number" 
                                        class="form-control" 
                                        :value="getGradeValue(answer)"
                                        @input="setGrade(answer.id, $event.target.value, answer.question?.points || 1)"
                                        :max="answer.question?.points || 1"
                                        min="0"
                                        step="0.5"
                                    >
                                    <span class="input-group-text">/ {{ answer.question?.points || 1 }}</span>
                                </div>

                                <!-- Quick Grade Buttons -->
                                <div class="btn-group w-100 mb-2">
                                    <button @click="setGrade(answer.id, 0, answer.question?.points)" class="btn btn-outline-danger btn-sm">0%</button>
                                    <button @click="setGrade(answer.id, (answer.question?.points || 1) * 0.5, answer.question?.points)" class="btn btn-outline-warning btn-sm">50%</button>
                                    <button @click="setGrade(answer.id, (answer.question?.points || 1) * 0.75, answer.question?.points)" class="btn btn-outline-info btn-sm">75%</button>
                                    <button @click="setGrade(answer.id, answer.question?.points || 1, answer.question?.points)" class="btn btn-outline-success btn-sm">100%</button>
                                </div>

                                <!-- AI Grade Single -->
                                <button 
                                    @click="aiGradeSingle(answer)" 
                                    class="btn btn-outline-purple w-100 mb-2"
                                    :disabled="aiLoadingSingle[answer.id] || !answer.answer_text"
                                >
                                    <i class="fas fa-robot me-1"></i>
                                    <span v-if="aiLoadingSingle[answer.id]">Memproses...</span>
                                    <span v-else>Nilai dengan AI</span>
                                </button>

                                <button 
                                    @click="saveSingleGrade(answer.id)" 
                                    class="btn btn-primary w-100"
                                    :disabled="!pendingGrades[answer.id]"
                                >
                                    <i class="fas fa-check me-1"></i> Simpan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <Pagination :links="answers.links" />
            </div>
        </div>

        <!-- Empty State -->
        <div class="card border-0 shadow" v-else-if="selectedExam && selectedSession">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <p class="text-muted">Tidak ada jawaban essay yang perlu dinilai</p>
            </div>
        </div>

        <div class="card border-0 shadow" v-else>
            <div class="card-body text-center py-5">
                <i class="fas fa-hand-pointer fa-3x text-muted mb-3"></i>
                <p class="text-muted">Pilih ujian dan sesi untuk melihat jawaban essay</p>
            </div>
        </div>
    </main>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import Swal from 'sweetalert2';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    exams: Array,
    sessions: Array,
    answers: Object,
    pendingCount: Number,
    filters: Object,
});

const selectedExam = ref(props.filters?.exam_id || '');
const selectedSession = ref(props.filters?.session_id || '');
const pendingGrades = reactive({});
const selectedAnswers = ref([]);
const aiResults = reactive({});
const aiLoading = ref(false);
const aiLoadingSingle = reactive({});

const isAllSelected = computed(() => {
    const ungradedAnswers = props.answers.data?.filter(a => a.points_awarded === null) || [];
    return ungradedAnswers.length > 0 && selectedAnswers.value.length === ungradedAnswers.length;
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedAnswers.value = props.answers.data?.filter(a => a.points_awarded === null).map(a => a.id) || [];
    } else {
        selectedAnswers.value = [];
    }
};

const onExamChange = () => {
    selectedSession.value = '';
    router.get('/admin/essay-grading', { exam_id: selectedExam.value }, { preserveState: true });
};

const onSessionChange = () => {
    router.get('/admin/essay-grading', { 
        exam_id: selectedExam.value, 
        session_id: selectedSession.value 
    }, { preserveState: true });
};

const getGradeValue = (answer) => {
    if (pendingGrades[answer.id] !== undefined) return pendingGrades[answer.id];
    return answer.points_awarded ?? '';
};

const setGrade = (answerId, value, maxPoints) => {
    const numValue = Math.min(Math.max(0, parseFloat(value) || 0), maxPoints);
    pendingGrades[answerId] = numValue;
};

const saveSingleGrade = (answerId) => {
    if (pendingGrades[answerId] === undefined) return;

    router.post(`/admin/essay-grading/${answerId}`, {
        points: pendingGrades[answerId],
    }, {
        preserveScroll: true,
        onSuccess: () => {
            delete pendingGrades[answerId];
            Swal.fire({ icon: 'success', title: 'Tersimpan!', timer: 1500, showConfirmButton: false });
        },
    });
};

const saveAllGrades = () => {
    const grades = Object.entries(pendingGrades).map(([id, points]) => ({
        answer_id: parseInt(id),
        points: points,
    }));

    if (grades.length === 0) return;

    router.post('/admin/essay-grading-bulk', { grades }, {
        preserveScroll: true,
        onSuccess: () => {
            Object.keys(pendingGrades).forEach(k => delete pendingGrades[k]);
            Swal.fire({ icon: 'success', title: 'Semua nilai tersimpan!', timer: 1500, showConfirmButton: false });
        },
    });
};

const aiGradeSingle = async (answer) => {
    aiLoadingSingle[answer.id] = true;
    
    router.post(`/admin/essay-grading/${answer.id}/ai`, {}, {
        preserveScroll: true,
        onSuccess: (page) => {
            const flash = page.props.flash;
            if (flash.ai_result) {
                aiResults[flash.answer_id] = flash.ai_result;
                setGrade(flash.answer_id, flash.ai_result.score, answer.question?.points || 1);
            }
            if (flash.error) {
                Swal.fire({ icon: 'error', title: 'Error', text: flash.error });
            }
        },
        onFinish: () => {
            aiLoadingSingle[answer.id] = false;
        },
    });
};

const aiGradeSelected = () => {
    if (selectedAnswers.value.length === 0) return;
    if (selectedAnswers.value.length > 10) {
        Swal.fire({ icon: 'warning', title: 'Maksimal 10 jawaban', text: 'Pilih maksimal 10 jawaban untuk AI grading sekaligus.' });
        return;
    }

    aiLoading.value = true;
    
    router.post('/admin/essay-grading-ai-bulk', { answer_ids: selectedAnswers.value }, {
        preserveScroll: true,
        onSuccess: (page) => {
            const results = page.props.flash.ai_bulk_results || [];
            results.forEach(r => {
                aiResults[r.answer_id] = r;
                const answer = props.answers.data?.find(a => a.id === r.answer_id);
                if (answer) {
                    setGrade(r.answer_id, r.score, answer.question?.points || 1);
                }
            });
            if (results.length > 0) {
                Swal.fire({ icon: 'success', title: `${results.length} jawaban dinilai AI`, timer: 2000, showConfirmButton: false });
            }
        },
        onFinish: () => {
            aiLoading.value = false;
            selectedAnswers.value = [];
        },
    });
};

const applyAiGrade = (answerId) => {
    const result = aiResults[answerId];
    if (!result) return;

    router.post(`/admin/essay-grading/${answerId}/apply-ai`, {
        points: result.score,
        feedback: result.feedback,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            delete aiResults[answerId];
            delete pendingGrades[answerId];
            Swal.fire({ icon: 'success', title: 'Nilai AI diterapkan!', timer: 1500, showConfirmButton: false });
        },
    });
};
</script>

<style scoped>
.btn-purple {
    background-color: #6f42c1;
    border-color: #6f42c1;
    color: white;
}
.btn-purple:hover {
    background-color: #5a32a3;
    border-color: #5a32a3;
    color: white;
}
.btn-outline-purple {
    color: #6f42c1;
    border-color: #6f42c1;
}
.btn-outline-purple:hover {
    background-color: #6f42c1;
    color: white;
}
.bg-purple-light {
    background-color: #f3f0ff;
}
.border-purple {
    border-color: #6f42c1 !important;
}
</style>
