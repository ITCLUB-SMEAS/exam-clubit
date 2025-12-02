<template>
    <Head>
        <title>Preview Ujian - {{ exam.title }}</title>
    </Head>

    <main class="content">
        <div class="py-4">
            <Link :href="`/admin/exams/${exam.id}`" class="btn btn-secondary mb-3">
                <i class="fa fa-arrow-left me-2"></i> Kembali
            </Link>

            <div class="alert alert-info">
                <i class="fa fa-eye me-2"></i>
                <strong>Mode Preview</strong> - Tampilan ini menunjukkan bagaimana siswa akan melihat ujian.
            </div>
        </div>

        <!-- Exam Header -->
        <div class="card border-0 shadow mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-1">{{ exam.title }}</h4>
                        <p class="text-muted mb-0">
                            {{ exam.lesson?.title }} | {{ exam.classroom?.title }}
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-primary fs-6 me-2">
                            <i class="fa fa-clock me-1"></i> {{ exam.duration }} menit
                        </span>
                        <span class="badge bg-secondary fs-6">
                            {{ questions.length }} soal
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Question Navigation -->
        <div class="card border-0 shadow mb-4">
            <div class="card-header">
                <h6 class="mb-0">Navigasi Soal</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <button 
                        v-for="(q, index) in questions" 
                        :key="q.id"
                        @click="currentQuestion = index"
                        :class="['btn', 'btn-sm', currentQuestion === index ? 'btn-primary' : 'btn-outline-secondary']"
                        style="width: 45px;"
                    >
                        {{ index + 1 }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Current Question -->
        <div class="card border-0 shadow" v-if="questions.length > 0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    Soal No. {{ currentQuestion + 1 }}
                    <span class="badge bg-info ms-2">{{ getTypeLabel(activeQuestion.type) }}</span>
                    <span class="badge bg-warning ms-1">{{ activeQuestion.points }} poin</span>
                </h5>
                <div>
                    <button @click="prevQuestion" :disabled="currentQuestion === 0" class="btn btn-sm btn-outline-secondary me-1">
                        <i class="fa fa-chevron-left"></i>
                    </button>
                    <button @click="nextQuestion" :disabled="currentQuestion === questions.length - 1" class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Question Text -->
                <div class="mb-4 p-3 bg-light rounded" v-html="activeQuestion.question"></div>

                <!-- Multiple Choice Single -->
                <div v-if="activeQuestion.type === 'multiple_choice_single'">
                    <div v-for="(opt, idx) in activeQuestion.options" :key="idx" class="form-check mb-2 p-3 border rounded">
                        <input class="form-check-input" type="radio" :name="'q'+activeQuestion.id" disabled>
                        <label class="form-check-label ms-2">
                            <span class="badge bg-secondary me-2">{{ String.fromCharCode(65 + idx) }}</span>
                            {{ opt.text }}
                        </label>
                        <span v-if="opt.number == activeQuestion.answer" class="badge bg-success ms-2">
                            <i class="fa fa-check"></i> Jawaban Benar
                        </span>
                    </div>
                </div>

                <!-- Multiple Choice Multiple -->
                <div v-else-if="activeQuestion.type === 'multiple_choice_multiple'">
                    <p class="text-muted mb-2"><i class="fa fa-info-circle"></i> Pilih semua jawaban yang benar</p>
                    <div v-for="(opt, idx) in activeQuestion.options" :key="idx" class="form-check mb-2 p-3 border rounded">
                        <input class="form-check-input" type="checkbox" disabled>
                        <label class="form-check-label ms-2">
                            <span class="badge bg-secondary me-2">{{ String.fromCharCode(65 + idx) }}</span>
                            {{ opt.text }}
                        </label>
                        <span v-if="activeQuestion.correct_answers?.includes(opt.number)" class="badge bg-success ms-2">
                            <i class="fa fa-check"></i> Benar
                        </span>
                    </div>
                </div>

                <!-- True/False -->
                <div v-else-if="activeQuestion.type === 'true_false'" class="d-flex gap-3">
                    <div class="p-4 border rounded text-center" style="min-width: 150px;">
                        <i class="fa fa-check fa-2x text-success mb-2"></i>
                        <div>Benar</div>
                        <span v-if="activeQuestion.answer == 1" class="badge bg-success mt-2">Jawaban</span>
                    </div>
                    <div class="p-4 border rounded text-center" style="min-width: 150px;">
                        <i class="fa fa-times fa-2x text-danger mb-2"></i>
                        <div>Salah</div>
                        <span v-if="activeQuestion.answer == 2" class="badge bg-success mt-2">Jawaban</span>
                    </div>
                </div>

                <!-- Short Answer -->
                <div v-else-if="activeQuestion.type === 'short_answer'">
                    <input type="text" class="form-control" placeholder="Siswa akan mengetik jawaban singkat di sini" disabled>
                    <div class="mt-2 text-muted">
                        <i class="fa fa-key me-1"></i> Kunci Jawaban: 
                        <code>{{ activeQuestion.correct_answers?.join(', ') || '-' }}</code>
                    </div>
                </div>

                <!-- Essay -->
                <div v-else-if="activeQuestion.type === 'essay'">
                    <textarea class="form-control" rows="5" placeholder="Siswa akan menulis jawaban essay di sini" disabled></textarea>
                    <div class="mt-2 text-info">
                        <i class="fa fa-info-circle me-1"></i> Jawaban essay akan dinilai manual oleh guru
                    </div>
                </div>

                <!-- Matching -->
                <div v-else-if="activeQuestion.type === 'matching'">
                    <p class="text-muted mb-3"><i class="fa fa-info-circle"></i> Siswa akan menjodohkan item di kolom kiri dengan kolom kanan</p>
                    <div class="row" v-if="activeQuestion.matching_pairs">
                        <div class="col-md-5">
                            <div v-for="(pair, idx) in activeQuestion.matching_pairs" :key="'l'+idx" class="p-2 border rounded mb-2 bg-light">
                                {{ idx + 1 }}. {{ pair.left }}
                            </div>
                        </div>
                        <div class="col-md-2 text-center align-self-center">
                            <i class="fa fa-arrows-alt-h fa-2x text-muted"></i>
                        </div>
                        <div class="col-md-5">
                            <div v-for="(pair, idx) in activeQuestion.matching_pairs" :key="'r'+idx" class="p-2 border rounded mb-2 bg-light">
                                {{ String.fromCharCode(65 + idx) }}. {{ pair.right }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Questions -->
        <div class="card border-0 shadow" v-else>
            <div class="card-body text-center py-5">
                <i class="fa fa-exclamation-circle fa-3x text-warning mb-3"></i>
                <p class="text-muted">Ujian ini belum memiliki soal</p>
                <Link :href="`/admin/exams/${exam.id}/questions/create`" class="btn btn-primary">
                    <i class="fa fa-plus me-1"></i> Tambah Soal
                </Link>
            </div>
        </div>
    </main>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    exam: Object,
    questions: Array,
});

const currentQuestion = ref(0);

const activeQuestion = computed(() => props.questions[currentQuestion.value] || {});

const prevQuestion = () => {
    if (currentQuestion.value > 0) currentQuestion.value--;
};

const nextQuestion = () => {
    if (currentQuestion.value < props.questions.length - 1) currentQuestion.value++;
};

const getTypeLabel = (type) => {
    const labels = {
        'multiple_choice_single': 'Pilihan Ganda',
        'multiple_choice_multiple': 'Pilihan Ganda (Multi)',
        'true_false': 'Benar/Salah',
        'short_answer': 'Jawaban Singkat',
        'essay': 'Essay',
        'matching': 'Menjodohkan',
    };
    return labels[type] || type;
};
</script>
