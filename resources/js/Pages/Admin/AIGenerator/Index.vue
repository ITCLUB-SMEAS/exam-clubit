<template>
    <Head>
        <title>AI Generator Soal - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-5">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-robot me-2"></i>Generate Soal dengan AI</h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="generate">
                            <div class="mb-3">
                                <label class="form-label">Topik/Materi</label>
                                <input type="text" class="form-control" v-model="form.topic" placeholder="Contoh: Sistem Pernapasan Manusia" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipe Soal</label>
                                <select class="form-select" v-model="form.type">
                                    <option value="multiple_choice_single">Pilihan Ganda</option>
                                    <option value="true_false">Benar/Salah</option>
                                    <option value="essay">Essay</option>
                                    <option value="short_answer">Jawaban Singkat</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label">Jumlah Soal</label>
                                        <select class="form-select" v-model="form.count">
                                            <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tingkat Kesulitan</label>
                                        <select class="form-select" v-model="form.difficulty">
                                            <option value="easy">Mudah</option>
                                            <option value="medium">Sedang</option>
                                            <option value="hard">Sulit</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" :disabled="generating">
                                <span v-if="generating"><i class="fas fa-spinner fa-spin me-1"></i> Generating...</span>
                                <span v-else><i class="fas fa-magic me-1"></i> Generate Soal</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Hasil Generate</h5>
                        <span class="badge bg-primary" v-if="questions.length">{{ questions.length }} soal</span>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                        <div v-if="questions.length === 0" class="text-center text-muted py-5">
                            <i class="fas fa-lightbulb fa-3x mb-3"></i>
                            <p>Soal yang di-generate akan muncul di sini.</p>
                        </div>

                        <div v-for="(q, idx) in questions" :key="idx" class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-secondary mb-2">Soal {{ idx + 1 }}</span>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" :id="'sel' + idx" v-model="selectedQuestions" :value="idx">
                                    <label class="form-check-label" :for="'sel' + idx">Pilih</label>
                                </div>
                            </div>
                            <p class="mb-2"><strong>{{ q.question }}</strong></p>
                            
                            <div v-if="q.options && q.options.length > 0" class="mb-2">
                                <div v-for="(opt, oi) in q.options" :key="oi" class="ms-3">
                                    <span :class="{'text-success fw-bold': q.answer === oi + 1}">
                                        {{ String.fromCharCode(65 + oi) }}. {{ opt }}
                                        <i v-if="q.answer === oi + 1" class="fas fa-check ms-1"></i>
                                    </span>
                                </div>
                            </div>
                            <div v-else class="mb-2">
                                <small class="text-muted">Jawaban:</small>
                                <p class="text-success mb-0">{{ q.answer }}</p>
                            </div>
                            <small class="text-muted" v-if="q.explanation">
                                <i class="fas fa-info-circle me-1"></i>{{ q.explanation }}
                            </small>
                        </div>
                    </div>
                    <div class="card-footer bg-white" v-if="questions.length > 0">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <select class="form-select form-select-sm" v-model="targetExamId">
                                    <option value="">-- Pilih Ujian --</option>
                                    <option v-for="e in exams" :key="e.id" :value="e.id">{{ e.title }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" class="form-control form-control-sm" v-model="points" placeholder="Poin" min="1">
                            </div>
                            <div class="col-md-5">
                                <button @click="saveToExam" class="btn btn-success btn-sm w-100" :disabled="!targetExamId || selectedQuestions.length === 0">
                                    <i class="fas fa-save me-1"></i> Simpan {{ selectedQuestions.length }} Soal ke Ujian
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head },
    props: {
        exams: Array,
    },
    data() {
        return {
            form: {
                topic: '',
                type: 'multiple_choice_single',
                count: 5,
                difficulty: 'medium',
            },
            questions: [],
            selectedQuestions: [],
            generating: false,
            targetExamId: '',
            points: 1,
        };
    },
    methods: {
        async generate() {
            this.generating = true;
            this.questions = [];
            this.selectedQuestions = [];

            try {
                const res = await axios.post('/admin/ai/generate-questions', this.form);
                if (res.data.questions) {
                    this.questions = res.data.questions;
                    this.selectedQuestions = this.questions.map((_, i) => i);
                } else {
                    Swal.fire('Error', 'Response tidak valid dari AI.', 'error');
                }
            } catch (e) {
                console.error('AI Generate Error:', e);
                const msg = e.response?.data?.error || e.response?.data?.message || e.message || 'Gagal generate soal.';
                Swal.fire('Error', msg, 'error');
            } finally {
                this.generating = false;
            }
        },
        saveToExam() {
            const selected = this.selectedQuestions.map(i => ({
                question: this.questions[i].question,
                type: this.form.type,
                options: this.questions[i].options || [],
                answer: this.questions[i].answer,
            }));

            router.post(`/admin/exams/${this.targetExamId}/ai-save-questions`, {
                questions: selected,
                points: this.points,
            }, {
                onSuccess: () => {
                    Swal.fire('Berhasil', `${selected.length} soal berhasil disimpan.`, 'success');
                    this.questions = [];
                    this.selectedQuestions = [];
                }
            });
        }
    }
}
</script>
