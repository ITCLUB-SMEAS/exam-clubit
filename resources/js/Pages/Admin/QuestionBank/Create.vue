<template>
    <Head>
        <title>Tambah Soal ke Bank - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Kategori</label>
                                    <select class="form-select" v-model="form.category_id">
                                        <option value="">-- Tanpa Kategori --</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
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
                                <div class="col-md-3">
                                    <label class="form-label">Difficulty</label>
                                    <select class="form-select" v-model="form.difficulty">
                                        <option value="easy">Mudah</option>
                                        <option value="medium">Sedang</option>
                                        <option value="hard">Sulit</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Poin</label>
                                    <input type="number" class="form-control" v-model="form.points" min="0" step="0.5">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Soal</label>
                                <TiptapEditor v-model="form.question" :height="200" />
                            </div>

                            <!-- Multiple Choice Options -->
                            <div v-if="isMultipleChoice" class="mb-3">
                                <label class="form-label">Opsi Jawaban</label>
                                <div v-for="i in 5" :key="i" class="input-group mb-2">
                                    <span class="input-group-text">
                                        <input v-if="form.question_type === 'multiple_choice_single'" type="radio" :value="i" v-model="form.answer">
                                        <input v-else type="checkbox" :value="i" v-model="form.correct_answers">
                                    </span>
                                    <input type="text" class="form-control" v-model="form['option_' + i]" :placeholder="'Opsi ' + i">
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
                                        <button type="button" class="btn btn-danger" @click="removePair(idx)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary" @click="addPair">+ Tambah Pasangan</button>
                            </div>

                            <!-- Tags -->
                            <div class="mb-3">
                                <label class="form-label">Tags</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" v-model="tagsText" placeholder="tag1, tag2, tag3 (pisahkan dengan koma)">
                                    <button type="button" class="btn btn-outline-primary" @click="generateAiTags" :disabled="generatingTags || !form.question">
                                        <i class="fas fa-magic" :class="{ 'fa-spin': generatingTags }"></i> AI Generate
                                    </button>
                                </div>
                                <div v-if="form.tags.length" class="mt-2">
                                    <span v-for="(tag, idx) in form.tags" :key="idx" class="badge bg-primary me-1">
                                        {{ tag }} <i class="fas fa-times ms-1" style="cursor:pointer" @click="removeTag(idx)"></i>
                                    </span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fas fa-save"></i> Simpan
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
import TiptapEditor from '../../../Components/TiptapEditor.vue';
import axios from 'axios';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, TiptapEditor },
    props: { categories: Array, errors: Object },
    setup(props) {
        const form = useForm({
            category_id: '',
            question: '',
            question_type: 'multiple_choice_single',
            difficulty: 'medium',
            points: 1,
            option_1: '', option_2: '', option_3: '', option_4: '', option_5: '',
            answer: '',
            correct_answers: [],
            matching_pairs: [{ left: '', right: '' }],
            tags: []
        });

        const shortAnswerText = ref('');
        const tagsText = ref('');
        const generatingTags = ref(false);

        watch(shortAnswerText, (val) => {
            form.correct_answers = val.split(',').map(s => s.trim()).filter(s => s);
        });

        watch(tagsText, (val) => {
            form.tags = val.split(',').map(s => s.trim().toLowerCase()).filter(s => s);
        });

        const isMultipleChoice = computed(() => 
            ['multiple_choice_single', 'multiple_choice_multiple'].includes(form.question_type)
        );

        const addPair = () => form.matching_pairs.push({ left: '', right: '' });
        const removePair = (idx) => form.matching_pairs.splice(idx, 1);
        const removeTag = (idx) => {
            form.tags.splice(idx, 1);
            tagsText.value = form.tags.join(', ');
        };

        const generateAiTags = async () => {
            if (!form.question || generatingTags.value) return;
            
            generatingTags.value = true;
            try {
                const category = props.categories.find(c => c.id === form.category_id);
                const res = await axios.post('/admin/question-bank-generate-tags', {
                    question: form.question,
                    category: category?.name || null
                });
                
                if (res.data.tags) {
                    form.tags = res.data.tags;
                    tagsText.value = res.data.tags.join(', ');
                }
            } catch (e) {
                alert(e.response?.data?.error || 'Gagal generate tags');
            } finally {
                generatingTags.value = false;
            }
        };

        const submit = () => form.post('/admin/question-bank');

        return { form, shortAnswerText, tagsText, isMultipleChoice, addPair, removePair, removeTag, submit, generateAiTags, generatingTags };
    }
}
</script>
