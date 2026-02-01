<template>
    <div>
        <!-- Adaptive Mode Badge -->
        <div v-if="isAdaptiveMode" class="mt-1">
            <span class="badge bg-purple">
                <i class="fas fa-brain me-1"></i> Mode Adaptive (CAT)
            </span>
            <span v-if="currentDifficulty" :class="difficultyBadgeClass" class="badge ms-1">
                <i :class="difficultyIcon"></i> {{ difficultyLabel }}
            </span>
        </div>

        <!-- Adaptive Ability Progress (shown in adaptive mode) -->
        <div v-if="isAdaptiveMode" class="card-body border-bottom py-2">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted"><i class="fas fa-chart-line me-1"></i> Level Kemampuan</small>
                <small :class="abilityLevelClass">{{ abilityLevelText }}</small>
            </div>
            <div class="progress" style="height: 8px;">
                <div 
                    class="progress-bar" 
                    :class="abilityProgressClass" 
                    role="progressbar" 
                    :style="{ width: abilityProgressWidth }" 
                    :aria-valuenow="abilityProgress" 
                    aria-valuemin="0" 
                    aria-valuemax="100"
                ></div>
            </div>
            <div class="d-flex justify-content-between mt-1">
                <small class="text-muted" style="font-size: 10px;">Mudah</small>
                <small class="text-muted" style="font-size: 10px;">Sedang</small>
                <small class="text-muted" style="font-size: 10px;">Sulit</small>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    isAdaptiveMode: {
        type: Boolean,
        default: false
    },
    currentDifficulty: {
        type: String,
        default: 'medium'
    },
    questions: {
        type: Array,
        default: () => []
    }
});

const difficultyBadgeClass = computed(() => {
    const diff = props.currentDifficulty;
    if (diff === 'easy') return 'bg-success';
    if (diff === 'hard') return 'bg-danger';
    return 'bg-warning text-dark';
});

const difficultyLabel = computed(() => {
    const labels = {
        'easy': 'Mudah',
        'medium': 'Sedang',
        'hard': 'Sulit'
    };
    return labels[props.currentDifficulty] || 'Sedang';
});

const difficultyIcon = computed(() => {
    const diff = props.currentDifficulty;
    if (diff === 'easy') return 'fas fa-leaf';
    if (diff === 'hard') return 'fas fa-fire';
    return 'fas fa-balance-scale';
});

const abilityProgress = computed(() => {
    const questions = props.questions || [];
    if (questions.length === 0) return 50;

    let correctWeight = 0;
    let totalWeight = 0;

    questions.forEach(q => {
        if (q.answer !== 0 || q.answer_text || q.answer_options?.length > 0) {
            const difficulty = q.question?.difficulty || 'medium';
            const weight = difficulty === 'easy' ? 1 : (difficulty === 'hard' ? 3 : 2);
            totalWeight += weight;
            
            if (q.is_correct === 'Y' || q.is_correct === true || q.is_correct === 1) {
                correctWeight += weight;
            }
        }
    });

    if (totalWeight === 0) return 50;
    return Math.round((correctWeight / totalWeight) * 100);
});

const abilityProgressWidth = computed(() => {
    return abilityProgress.value + '%';
});

const abilityProgressClass = computed(() => {
    const progress = abilityProgress.value;
    if (progress >= 70) return 'bg-success';
    if (progress >= 40) return 'bg-warning';
    return 'bg-danger';
});

const abilityLevelText = computed(() => {
    const progress = abilityProgress.value;
    if (progress >= 80) return 'Sangat Baik';
    if (progress >= 60) return 'Baik';
    if (progress >= 40) return 'Cukup';
    return 'Perlu Bimbingan';
});

const abilityLevelClass = computed(() => {
    const progress = abilityProgress.value;
    if (progress >= 70) return 'text-success fw-bold';
    if (progress >= 40) return 'text-warning fw-bold';
    return 'text-danger fw-bold';
});
</script>

<style scoped>
.bg-purple {
    background-color: #6f42c1 !important;
    color: white;
}

.progress-bar {
    transition: width 0.5s ease-in-out;
}
</style>
