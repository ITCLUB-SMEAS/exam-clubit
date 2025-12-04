<template>
    <Head>
        <title>Deteksi Plagiarisme - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fa fa-search me-2"></i>Deteksi Plagiarisme Essay</h5>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Ujian</label>
                                <select class="form-select" v-model="selectedExam" @change="onExamChange">
                                    <option value="">-- Pilih Ujian --</option>
                                    <option v-for="e in exams" :key="e.id" :value="e.id">
                                        {{ e.title }} ({{ e.lesson?.title }})
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sesi</label>
                                <select class="form-select" v-model="selectedSession" :disabled="!selectedExam">
                                    <option value="">-- Pilih Sesi --</option>
                                    <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.title }}</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button @click="checkPlagiarism" class="btn btn-primary" :disabled="!selectedExam || !selectedSession">
                                    <i class="fa fa-search me-1"></i> Cek Plagiarisme
                                </button>
                            </div>
                        </div>

                        <!-- Results -->
                        <div v-if="results.length > 0">
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                Ditemukan <strong>{{ totalSimilarities }}</strong> pasangan jawaban dengan kemiripan tinggi (≥70%)
                            </div>

                            <div v-for="(item, idx) in results" :key="idx" class="card mb-3">
                                <div class="card-header bg-light">
                                    <strong>Soal:</strong> <span v-html="item.question_text"></span>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Siswa 1</th>
                                                <th>Siswa 2</th>
                                                <th class="text-center">Kemiripan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(sim, i) in item.similarities" :key="i">
                                                <td>
                                                    <strong>{{ sim.student1.name }}</strong><br>
                                                    <small class="text-muted">{{ sim.student1.nisn }}</small><br>
                                                    <small class="text-secondary">{{ sim.answer1_preview }}</small>
                                                </td>
                                                <td>
                                                    <strong>{{ sim.student2.name }}</strong><br>
                                                    <small class="text-muted">{{ sim.student2.nisn }}</small><br>
                                                    <small class="text-secondary">{{ sim.answer2_preview }}</small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge" :class="getSimilarityBadge(sim.similarity)">
                                                        {{ sim.similarity }}%
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="checked" class="alert alert-success">
                            <i class="fa fa-check-circle me-2"></i>
                            Tidak ditemukan kemiripan jawaban yang mencurigakan.
                        </div>

                        <div v-else class="text-center text-muted py-5">
                            <i class="fa fa-search fa-3x mb-3"></i>
                            <p>Pilih ujian dan sesi untuk memulai pengecekan plagiarisme.</p>
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

export default {
    layout: LayoutAdmin,
    components: { Head },
    props: {
        exams: Array,
        sessions: Array,
        results: Array,
        filters: Object,
    },
    data() {
        return {
            selectedExam: this.filters?.exam_id || '',
            selectedSession: this.filters?.session_id || '',
            checked: this.filters?.exam_id && this.filters?.session_id,
        };
    },
    computed: {
        totalSimilarities() {
            return this.results.reduce((sum, r) => sum + r.similarities.length, 0);
        }
    },
    methods: {
        onExamChange() {
            this.selectedSession = '';
            if (this.selectedExam) {
                router.get('/admin/plagiarism', { exam_id: this.selectedExam }, { preserveState: true });
            }
        },
        checkPlagiarism() {
            router.get('/admin/plagiarism', {
                exam_id: this.selectedExam,
                session_id: this.selectedSession,
            });
            this.checked = true;
        },
        getSimilarityBadge(similarity) {
            if (similarity >= 90) return 'bg-danger';
            if (similarity >= 80) return 'bg-warning';
            return 'bg-info';
        }
    }
}
</script>
