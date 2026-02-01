<template>
    <div>
        <!-- Multiple Choice Single -->
        <div v-if="questionType === 'multiple_choice_single'" role="radiogroup" aria-label="Pilihan jawaban">
            <table>
                <tbody>
                    <tr v-for="(answer, index) in answerOrder" :key="index">
                        <td width="50" style="padding: 10px;">
                            <button 
                                v-if="answer == currentAnswer" 
                                class="btn btn-info btn-sm w-100 shadow" 
                                aria-pressed="true" 
                                :aria-label="'Pilihan ' + options[index] + ' (terpilih)'"
                            >
                                {{ options[index] }}
                            </button>
                            <button 
                                v-else 
                                @click.prevent="$emit('answer-single', answer)" 
                                class="btn btn-outline-info btn-sm w-100 shadow" 
                                aria-pressed="false" 
                                :aria-label="'Pilih jawaban ' + options[index]"
                            >
                                {{ options[index] }}
                            </button>
                        </td>
                        <td style="padding: 10px;">
                            {{ question['option_' + answer] }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Multiple Choice Multiple -->
        <div v-else-if="questionType === 'multiple_choice_multiple'" role="group" aria-label="Pilihan jawaban (pilih lebih dari satu)">
            <div class="list-group">
                <label 
                    v-for="(answer, index) in answerOrder" 
                    :key="index" 
                    class="list-group-item d-flex align-items-center"
                >
                    <input 
                        class="form-check-input me-2" 
                        type="checkbox" 
                        :value="answer" 
                        v-model="selectedOptionsLocal" 
                        :aria-label="'Pilihan ' + options[index]"
                    >
                    <span class="badge bg-secondary me-2" aria-hidden="true">{{ options[index] }}</span>
                    {{ question['option_' + answer] }}
                </label>
            </div>
            <button @click.prevent="$emit('answer-multiple', selectedOptionsLocal)" class="btn btn-info btn-sm mt-3">
                Simpan Jawaban
            </button>
        </div>

        <!-- Short Answer -->
        <div v-else-if="questionType === 'short_answer'">
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-bold" for="short-answer-input">Jawaban Singkat</label>
                    <span v-if="isAnswered" class="badge bg-success"><i class="fas fa-check"></i> Tersimpan</span>
                    <span v-else class="badge bg-secondary">Belum dijawab</span>
                </div>
                <input 
                    type="text" 
                    id="short-answer-input" 
                    class="form-control" 
                    v-model="textAnswerLocal" 
                    placeholder="Ketik jawaban Anda" 
                    aria-describedby="short-answer-help" 
                />
            </div>
            <button @click.prevent="$emit('answer-text', textAnswerLocal)" class="btn btn-info btn-sm" :disabled="isSubmitting">
                <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...</span>
                <span v-else>Simpan Jawaban</span>
            </button>
        </div>

        <!-- Essay -->
        <div v-else-if="questionType === 'essay'">
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-bold">Jawaban Essay</label>
                    <span v-if="isAnswered" class="badge bg-success"><i class="fas fa-check"></i> Tersimpan</span>
                    <span v-else class="badge bg-secondary">Belum dijawab</span>
                </div>
                <textarea 
                    class="form-control" 
                    rows="6" 
                    v-model="textAnswerLocal" 
                    placeholder="Tulis jawaban Anda"
                ></textarea>
                <small class="text-muted">Jawaban akan dinilai manual.</small>
            </div>
            <button @click.prevent="$emit('answer-text', textAnswerLocal)" class="btn btn-info btn-sm" :disabled="isSubmitting">
                <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...</span>
                <span v-else>Simpan Jawaban</span>
            </button>
        </div>

        <!-- True/False -->
        <div v-else-if="questionType === 'true_false'">
            <div class="d-flex gap-3">
                <button 
                    @click.prevent="$emit('answer-single', 1)" 
                    :class="['btn', 'btn-lg', currentAnswer == 1 ? 'btn-success' : 'btn-outline-success']"
                >
                    <i class="fas fa-check me-2"></i> Benar
                </button>
                <button 
                    @click.prevent="$emit('answer-single', 2)" 
                    :class="['btn', 'btn-lg', currentAnswer == 2 ? 'btn-danger' : 'btn-outline-danger']"
                >
                    <i class="fas fa-times me-2"></i> Salah
                </button>
            </div>
        </div>

        <!-- Matching -->
        <div v-else-if="questionType === 'matching'">
            <div class="mb-3">
                <p class="text-muted mb-3">Jodohkan pernyataan di kiri dengan jawaban yang tepat di kanan.</p>
                <div v-for="(pair, idx) in matchingLeftItems" :key="idx" class="row mb-2 align-items-center">
                    <div class="col-5">
                        <div class="p-2 bg-light rounded">{{ pair }}</div>
                    </div>
                    <div class="col-2 text-center">-></div>
                    <div class="col-5">
                        <select class="form-select" v-model="matchingAnswersLocal[pair]">
                            <option value="">-- Pilih --</option>
                            <option v-for="right in matchingRightItems" :key="right" :value="right">{{ right }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <button @click.prevent="$emit('answer-matching', matchingAnswersLocal)" class="btn btn-info btn-sm">
                Simpan Jawaban
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    questionType: {
        type: String,
        default: 'multiple_choice_single'
    },
    question: {
        type: Object,
        required: true
    },
    answerOrder: {
        type: Array,
        default: () => []
    },
    currentAnswer: {
        type: [Number, String],
        default: 0
    },
    selectedOptions: {
        type: Array,
        default: () => []
    },
    textAnswer: {
        type: String,
        default: ''
    },
    matchingAnswers: {
        type: Object,
        default: () => ({})
    },
    matchingLeftItems: {
        type: Array,
        default: () => []
    },
    matchingRightItems: {
        type: Array,
        default: () => []
    },
    isAnswered: {
        type: Boolean,
        default: false
    },
    isSubmitting: {
        type: Boolean,
        default: false
    }
});

const options = ['A', 'B', 'C', 'D', 'E'];

defineEmits(['answer-single', 'answer-multiple', 'answer-text', 'answer-matching']);

// Local state that syncs with props
const selectedOptionsLocal = ref([...props.selectedOptions]);
const textAnswerLocal = ref(props.textAnswer);
const matchingAnswersLocal = ref({...props.matchingAnswers});

// Watch for prop changes
watch(() => props.selectedOptions, (val) => {
    selectedOptionsLocal.value = Array.isArray(val) ? [...val] : [];
}, { immediate: true });

watch(() => props.textAnswer, (val) => {
    textAnswerLocal.value = val || '';
}, { immediate: true });

watch(() => props.matchingAnswers, (val) => {
    matchingAnswersLocal.value = val ? {...val} : {};
}, { immediate: true });
</script>
