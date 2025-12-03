<template>
    <Head>
        <title>Tambah Soal Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link :href="`/admin/exams/${exam.id}`" class="btn btn-md btn-primary border-0 shadow mb-3" type="button"><i class="fa fa-long-arrow-alt-left me-2"></i> Kembali</Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-question-circle"></i> Tambah Soal Ujian</h5>
                        <hr>
                        <form @submit.prevent="submit">

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                    <tbody>
                                        <tr>
                                            <td style="width:20%" class="fw-bold">Tipe Soal</td>
                                            <td>
                                                <select class="form-control" v-model="form.question_type">
                                                    <option value="multiple_choice_single">Pilihan Ganda (Satu Jawaban)</option>
                                                    <option value="multiple_choice_multiple">Pilihan Ganda (Banyak Jawaban)</option>
                                                    <option value="true_false">Benar/Salah</option>
                                                    <option value="short_answer">Jawaban Singkat</option>
                                                    <option value="essay">Essay (dinilai manual)</option>
                                                    <option value="matching">Menjodohkan</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width:20%" class="fw-bold">Poin Soal</td>
                                            <td>
                                                <input type="number" class="form-control" min="0" step="0.5" v-model="form.points" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width:20%" class="fw-bold">Soal</td>
                                            <td>
                                                <TiptapEditor v-model="form.question" :height="200" />
                                            </td>
                                        </tr>
                                        <!-- Multiple Choice Options -->
                                        <template v-if="isMultipleChoice">
                                            <tr v-for="(label, idx) in optionLabels" :key="idx">
                                                <td class="fw-bold">Pilihan {{ label }}</td>
                                                <td>
                                                    <TiptapEditor v-model="form['option_'+(idx+1)]" :height="130" />
                                                </td>
                                            </tr>
                                        </template>
                                        <!-- True/False Options -->
                                        <template v-if="form.question_type === 'true_false'">
                                            <tr>
                                                <td class="fw-bold">Jawaban Benar</td>
                                                <td>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" value="1" v-model="form.answer" id="true">
                                                        <label class="form-check-label" for="true">Benar</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" value="2" v-model="form.answer" id="false">
                                                        <label class="form-check-label" for="false">Salah</label>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                        <!-- Single Answer -->
                                        <tr v-if="form.question_type === 'multiple_choice_single'">
                                            <td class="fw-bold">Jawaban Benar</td>
                                            <td>
                                                <select class="form-control" v-model="form.answer">
                                                    <option v-for="(label, idx) in optionLabels" :key="idx" :value="idx+1">{{ label }}</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <!-- Multiple Answers -->
                                        <tr v-if="form.question_type === 'multiple_choice_multiple'">
                                            <td class="fw-bold">Jawaban Benar</td>
                                            <td>
                                                <div class="form-check form-check-inline" v-for="(label, idx) in optionLabels" :key="idx">
                                                    <input class="form-check-input" type="checkbox" :value="idx+1" v-model="form.correct_answers">
                                                    <label class="form-check-label">{{ label }}</label>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- Short Answer -->
                                        <tr v-if="form.question_type === 'short_answer'">
                                            <td class="fw-bold">Jawaban Benar</td>
                                            <td>
                                                <input type="text" class="form-control" v-model="form.correct_answers_text" placeholder="pisahkan dengan koma, contoh: jakarta, jakarta pusat" />
                                                <small class="text-muted">Tidak case sensitive.</small>
                                            </td>
                                        </tr>
                                        <!-- Essay -->
                                        <tr v-if="form.question_type === 'essay'">
                                            <td class="fw-bold">Catatan</td>
                                            <td><span class="text-muted">Jawaban akan dinilai manual.</span></td>
                                        </tr>
                                        <!-- Matching -->
                                        <template v-if="form.question_type === 'matching'">
                                            <tr>
                                                <td class="fw-bold">Pasangan Jawaban</td>
                                                <td>
                                                    <div v-for="(pair, idx) in form.matching_pairs" :key="idx" class="row mb-2">
                                                        <div class="col-5">
                                                            <input type="text" class="form-control" v-model="pair.left" placeholder="Pernyataan kiri" />
                                                        </div>
                                                        <div class="col-1 text-center pt-2">→</div>
                                                        <div class="col-5">
                                                            <input type="text" class="form-control" v-model="pair.right" placeholder="Jawaban kanan" />
                                                        </div>
                                                        <div class="col-1">
                                                            <button type="button" class="btn btn-sm btn-danger" @click="removePair(idx)">×</button>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-secondary" @click="addPair">+ Tambah Pasangan</button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <button type="submit" class="btn btn-md btn-primary border-0 shadow me-2">Simpan</button>
                            <button type="reset" class="btn btn-md btn-warning border-0 shadow">Reset</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, computed } from 'vue';
import Swal from 'sweetalert2';
import TiptapEditor from '../../../Components/TiptapEditor.vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, TiptapEditor },
    props: {
        errors: Object,
        exam: Object,
    },
    setup(props) {
        const form = reactive({
            question: '',
            question_type: 'multiple_choice_single',
            points: 1,
            option_1: '',
            option_2: '',
            option_3: '',
            option_4: '',
            option_5: '',
            answer: '',
            correct_answers: [],
            correct_answers_text: '',
            matching_pairs: [{ left: '', right: '' }, { left: '', right: '' }],
        });

        const optionLabels = ["A", "B", "C", "D", "E"];

        const isMultipleChoice = computed(() =>
            form.question_type === 'multiple_choice_single' || form.question_type === 'multiple_choice_multiple'
        );

        const addPair = () => form.matching_pairs.push({ left: '', right: '' });
        const removePair = (idx) => form.matching_pairs.length > 2 && form.matching_pairs.splice(idx, 1);

        const submit = () => {
            let correctAnswers = form.correct_answers;
            if (form.question_type === 'short_answer' && form.correct_answers_text) {
                correctAnswers = form.correct_answers_text.split(',').map(v => v.trim()).filter(Boolean);
            }

            const payload = {
                question: form.question,
                question_type: form.question_type,
                points: form.points,
                option_1: form.option_1,
                option_2: form.option_2,
                option_3: form.option_3,
                option_4: form.option_4,
                option_5: form.option_5,
                answer: form.answer,
                correct_answers: correctAnswers,
                matching_pairs: form.question_type === 'matching' ? form.matching_pairs.filter(p => p.left && p.right) : null,
            };

            router.post(`/admin/exams/${props.exam.id}/questions/store`, payload, {
                onSuccess: () => {
                    Swal.fire({ title: 'Success!', text: 'Soal Ujian Berhasil Disimpan!', icon: 'success', showConfirmButton: false, timer: 2000 });
                },
            });
        };

        return { form, isMultipleChoice, optionLabels, submit, addPair, removePair };
    }
}
</script>
