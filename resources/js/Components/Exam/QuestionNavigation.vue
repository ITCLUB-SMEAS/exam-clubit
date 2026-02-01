<template>
    <div class="d-flex flex-wrap">
        <div 
            v-for="(question, index) in questions" 
            :key="index" 
            class="p-1" 
            style="width: 20%; min-width: 45px;"
        >
            <!-- If time_per_question active, only allow current and next questions -->
            <template v-if="questionTimeLimit">
                <button 
                    v-if="index + 1 == currentPage" 
                    class="btn btn-gray-400 btn-sm w-100" 
                    disabled 
                    :aria-label="'Soal nomor ' + (index + 1) + ', sedang dibuka'"
                >
                    {{ index + 1 }}
                </button>
                <button 
                    v-else-if="index + 1 < currentPage" 
                    class="btn btn-secondary btn-sm w-100" 
                    disabled 
                    :title="'Soal sudah dilewati'" 
                    :aria-label="'Soal nomor ' + (index + 1) + ', terkunci'"
                >
                    <i class="fas fa-lock" style="font-size: 10px;"></i>
                </button>
                <button 
                    v-else-if="!isAnswered(question)" 
                    class="btn btn-outline-info btn-sm w-100" 
                    disabled 
                    :aria-label="'Soal nomor ' + (index + 1) + ', belum dikerjakan'"
                >
                    {{ index + 1 }}
                </button>
                <button 
                    v-else 
                    class="btn btn-info btn-sm w-100" 
                    disabled 
                    :aria-label="'Soal nomor ' + (index + 1) + ', sudah dikerjakan'"
                >
                    {{ index + 1 }}
                </button>
            </template>
            
            <!-- Normal navigation -->
            <template v-else>
                <button 
                    @click.prevent="$emit('navigate', index)" 
                    v-if="index + 1 == currentPage" 
                    class="btn btn-gray-400 btn-sm w-100" 
                    :aria-label="'Soal nomor ' + (index + 1) + ', sedang dibuka'"
                >
                    {{ index + 1 }}
                </button>
                <button 
                    @click.prevent="$emit('navigate', index)" 
                    v-else-if="!isAnswered(question)" 
                    class="btn btn-outline-info btn-sm w-100" 
                    :aria-label="'Soal nomor ' + (index + 1) + ', belum dikerjakan'"
                >
                    {{ index + 1 }}
                </button>
                <button 
                    @click.prevent="$emit('navigate', index)" 
                    v-else 
                    class="btn btn-info btn-sm w-100" 
                    :aria-label="'Soal nomor ' + (index + 1) + ', sudah dikerjakan'"
                >
                    {{ index + 1 }}
                </button>
            </template>
        </div>
    </div>
</template>

<script setup>
defineProps({
    questions: {
        type: Array,
        required: true
    },
    currentPage: {
        type: Number,
        required: true
    },
    questionTimeLimit: {
        type: Number,
        default: 0
    }
});

defineEmits(['navigate']);

const isAnswered = (question) => {
    if (!question) return false;
    if (question.answer && question.answer !== 0) return true;
    if (question.answer_options && question.answer_options.length > 0) return true;
    if (question.answer_text && question.answer_text.trim() !== '') return true;
    if (question.matching_answers && Object.keys(question.matching_answers).length > 0) return true;
    return false;
};
</script>
