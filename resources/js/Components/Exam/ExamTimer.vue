<template>
    <div class="d-flex flex-column align-items-end" role="timer" aria-label="Waktu ujian">
        <div class="d-flex flex-wrap gap-2 mb-1">
            <!-- Violation counter for mobile -->
            <span v-if="showViolationBadge" :class="violationBadgeClass" class="badge p-2 d-md-none" role="status">
                <i class="fas fa-shield-alt me-1" aria-hidden="true"></i>
                {{ violationCount }}/{{ maxViolations }}
            </span>
            
            <!-- Timer per soal (jika aktif) -->
            <VueCountdown 
                v-if="questionTimeLimit > 0" 
                :time="questionTimeRemaining" 
                @end="$emit('question-time-end')" 
                v-slot="{ minutes, seconds }"
            >
                <span class="badge bg-warning text-dark p-2" role="timer" aria-label="Waktu soal">
                    <i class="fas fa-stopwatch" aria-hidden="true"></i> 
                    <span class="d-none d-sm-inline">Soal:</span> {{ minutes }}:{{ String(seconds).padStart(2, '0') }}
                </span>
            </VueCountdown>
            
            <!-- Timer total ujian -->
            <VueCountdown 
                :time="duration" 
                @progress="handleProgress" 
                @end="$emit('time-end')" 
                v-slot="{ hours, minutes, seconds }"
            >
                <span class="badge bg-info p-2" role="timer" aria-label="Sisa waktu ujian">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline">{{ hours }}j {{ minutes }}m {{ seconds }}d</span>
                    <span class="d-sm-none">{{ hours }}:{{ String(minutes).padStart(2, '0') }}:{{ String(seconds).padStart(2, '0') }}</span>
                </span>
            </VueCountdown>
        </div>
        
        <div class="progress w-100" style="height: 5px;">
            <div 
                class="progress-bar" 
                :class="timerProgressColor" 
                role="progressbar" 
                :style="{ width: timerProgress + '%' }" 
                :aria-valuenow="timerProgress" 
                aria-valuemin="0" 
                aria-valuemax="100"
            ></div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import VueCountdown from '@chenfengyuan/vue-countdown';

const props = defineProps({
    duration: {
        type: Number,
        required: true
    },
    totalDuration: {
        type: Number,
        required: true
    },
    questionTimeLimit: {
        type: Number,
        default: 0
    },
    questionTimeRemaining: {
        type: Number,
        default: 0
    },
    showViolationBadge: {
        type: Boolean,
        default: false
    },
    violationCount: {
        type: Number,
        default: 0
    },
    maxViolations: {
        type: Number,
        default: 3
    },
    violationBadgeClass: {
        type: String,
        default: 'bg-success'
    }
});

const emit = defineEmits(['progress', 'time-end', 'question-time-end']);

const timerProgress = computed(() => {
    if (props.totalDuration <= 0) return 0;
    const safeDuration = Math.max(0, props.duration);
    const progress = (safeDuration / props.totalDuration) * 100;
    return Math.min(100, Math.max(0, progress));
});

const timerProgressColor = computed(() => {
    if (timerProgress.value < 10) return 'bg-danger';
    if (timerProgress.value < 30) return 'bg-warning';
    return 'bg-info';
});

const handleProgress = () => {
    emit('progress');
};
</script>
