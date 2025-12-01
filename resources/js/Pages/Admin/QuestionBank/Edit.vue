<template>
    <Head>
        <title>Edit Soal Bank - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Kategori</label>
                                    <select class="form-select" v-model="form.category_id">
                                        <option value="">-- Tanpa Kategori --</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tipe Soal</label>
                                    <select class="form-select" v-model="form.question_type">
                                        <option value="multiple_choice_single">Pilihan Ganda (Single)</option>
                                        <option value="multiple_choice_multiple">Pilihan Ganda (Multiple)</option>
                                        <option value="true_false">Benar/Salah</option>
                                        <option value="short_answer">Jawaban Singkat</option>
                                        <option value="essay">Essay</option>
                                        <option value="matching">Menjodohkan</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Poin</label>
                                    <input type="number" class="form-control" v-model="form.points" min="0" step="0.5">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Soal</label>
                                <Editor v-model="form.question" api-key="no-api-key" :init="editorConfig" />
                            </div>

                            <!-- Multiple Choice Options -->
                            <div v-if="isMultipleChoice" class="mb-3">
                                <label class="form-label">Opsi Jawaban</label>
                                <div v-for="i in 5" :key="i" class="input-group mb-2">
                                    <span class="input-group-text">
                                        <input v-if="form.question_type === 'multiple_choice_single'" type="radio" :value="i" v-model="form.answer">
                                        <input v-else type="checkbox" :value="i" v-model="form.correct_answers">
                                    </span>
                                    <input type="text" class="form-control" v-model="form[`option_${i}`]" :placeholder="`Opsi ${i}`">
                                </div>
                            </div>

                            <!-- True/False -->
                            <div v-if="form.question_type === 'true_false'" class="mb-3">
                                <label class="form-label">Jawaban Benar</label>
                                <select class="form-select" v-model="form.answer">
                                    <option value="true">Benar</option>
                                    <option value="false">Salah</option>
                                </select>
                            </div>

                            <!-- Short Answer -->
                            <div v-if="form.question_type === 'short_answer'" class="mb-3">
                                <label class="form-label">Jawaban yang Diterima (pisahkan dengan koma)</label>
                                <input type="text" class="form-control" v-model="shortAnswerText" placeholder="jawaban1, jawaban2">
                            </div>

                            <!-- Matching -->
                            <div v-if="form.question_type === 'matching'" class="mb-3">
                                <label class="form-label">Pasangan</label>
                                <div v-for="(pair, idx) in form.matching_pairs" :key="idx" class="row mb-2">
                                    <div class="col-5">
                                        <input type="text" class="form-control" v-model="pair.left" placeholder="Kiri">
                                    </div>
                                    <div class="col-5">
                                        <input type="text" class="form-control" v-model="pair.right" placeholder="Kanan">
                                    </div>
                                    <div class="col-2">
                                        <button type="button" class="btn btn-danger" @click="removePair(idx)"><i class="fa fa-trash"></i></button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" @click="addPair">+ Tambah Pasangan</button>
                            </div>

                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fa fa-save"></i> Update
                            </button>
                            <Link href="/admin/question-bank" class="btn btn-secondary ms-2">Kembali</Link>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Editor from '@tinymce/tinymce-vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Editor },
    props: { question: Object, categories: Array, errors: Object },
    setup(props) {
        const q = props.question;
        const form = useForm({
            category_id: q.category_id || '',
            question: q.question,
            question_type: q.question_type,
            points: q.points,
            option_1: q.option_1 || '', option_2: q.option_2 || '', option_3: q.option_3 || '',
            option_4: q.option_4 || '', option_5: q.option_5 || '',
            answer: q.answer || '',
            correct_answers: q.correct_answers || [],
            matching_pairs: q.matching_pairs || [{ left: '', right: '' }]
        });

        const shortAnswerText = ref(q.question_type === 'short_answer' && q.correct_answers ? q.correct_answers.join(', ') : '');
        watch(shortAnswerText, (val) => {
            form.correct_answers = val.split(',').map(s => s.trim()).filter(s => s);
        });

        const isMultipleChoice = computed(() => 
            ['multiple_choice_single', 'multiple_choice_multiple'].includes(form.question_type)
        );

        const addPair = () => form.matching_pairs.push({ left: '', right: '' });
        const removePair = (idx) => form.matching_pairs.splice(idx, 1);

        const editorConfig = { height: 200, menubar: false, plugins: 'lists', toolbar: 'bold italic | bullist numlist' };

        const submit = () => form.put(`/admin/question-bank/${q.id}`);

        return { form, shortAnswerText, isMultipleChoice, addPair, removePair, editorConfig, submit };
    }
}
</script>
