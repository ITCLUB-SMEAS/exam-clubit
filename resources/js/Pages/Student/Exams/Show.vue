<template>
    <Head>
        <title>Ujian Dengan Nomor Soal : {{ page }} - Aplikasi Ujian Online</title>
    </Head>

    <!-- Landscape Warning for Mobile -->
    <LandscapeWarning />
    
    <!-- Onboarding Tutorial -->
    <ExamOnboarding :exam-id="exam_group.id" />

    <!-- Skip to main content link for screen readers -->
    <a href="#main-question" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:z-50 focus:p-4 focus:bg-white">
        Langsung ke soal
    </a>

    <!-- Anti-Cheat Warning Banner -->
    <div v-if="antiCheat.warningReached.value && !showViolationWarning" class="alert alert-warning alert-dismissible fade show mb-3" role="alert" aria-live="assertive">
        <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
        <strong>Peringatan!</strong> Anda telah melakukan {{ antiCheat.violationCount.value }} pelanggaran.
        Sisa pelanggaran yang diizinkan: {{ antiCheat.remainingViolations.value }}
        <button type="button" class="btn-close" @click="dismissWarningBanner" aria-label="Tutup peringatan"></button>
    </div>

    <!-- Violation Counter Badge -->
    <div v-if="antiCheatConfig.enabled" class="position-fixed d-none d-md-block" style="top: 10px; right: 10px; z-index: 1050;" role="status" aria-live="polite">
        <span :class="violationBadgeClass" class="badge p-2">
            <i class="fas fa-shield-alt me-1" aria-hidden="true"></i>
            <span class="sr-only">Jumlah pelanggaran:</span>
            Pelanggaran: {{ antiCheat.violationCount.value }}/{{ antiCheatConfig.max_violations }}
        </span>
    </div>

    <!-- Face Detection Camera (Hidden - Stealth Mode) -->
    <video v-if="face_detection_enabled" ref="faceVideoRef" autoplay muted playsinline style="position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none;" aria-hidden="true"></video>

    <div class="row mb-5">
        <div class="col-12 col-md-7 mb-3 mb-md-0">
            <div class="card border-0 shadow" role="main" id="main-question">
                <div class="card-header">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                        <div>
                            <h5 class="mb-0" aria-live="polite">Soal No. <strong class="fw-bold">{{ page }}</strong> <span v-if="!isAdaptiveMode">dari {{ Object.keys(all_questions).length }}</span></h5>
                            <!-- Adaptive Mode Badge -->
                            <div v-if="isAdaptiveMode" class="mt-1">
                                <span class="badge bg-purple">
                                    <i class="fas fa-brain me-1"></i> Mode Adaptive (CAT)
                                </span>
                                <span v-if="currentDifficulty" :class="difficultyBadgeClass" class="badge ms-1">
                                    <i :class="difficultyIcon"></i> {{ difficultyLabel }}
                                </span>
                            </div>
                        </div>
                        <ExamTimer
                            :duration="duration"
                            :total-duration="exam_group.exam.duration * 60 * 1000"
                            :question-time-limit="questionTimeLimit"
                            :question-time-remaining="questionTimeRemaining"
                            :show-violation-badge="antiCheatConfig.enabled"
                            :violation-count="antiCheat.violationCount.value"
                            :max-violations="antiCheatConfig.max_violations"
                            :violation-badge-class="violationBadgeClass"
                            @progress="handleChangeDuration"
                            @time-end="showModalEndTimeExam = true"
                            @question-time-end="handleQuestionTimeEnd"
                        />
                    </div>
                </div>
                <div class="card-body">

                    <div v-if="question_active !== null">

                        <div role="region" aria-label="Pertanyaan">
                            <p v-html="question_active.question.question"></p>
                        </div>

                        <AnswerInput
                            :question-type="question_active.question.question_type || 'multiple_choice_single'"
                            :question="question_active.question"
                            :answer-order="answer_order"
                            :current-answer="question_active.answer"
                            :selected-options="selectedOptions"
                            :text-answer="textAnswer"
                            :matching-answers="matchingAnswers"
                            :matching-left-items="matchingLeftItems"
                            :matching-right-items="matchingRightItems"
                            :is-answered="isAnswered(question_active)"
                            :is-submitting="isSubmitting"
                            @answer-single="submitAnswerSingle"
                            @answer-multiple="submitAnswerMultiple"
                            @answer-text="submitAnswerText"
                            @answer-matching="submitAnswerMatching"
                        />

                    </div>

                    <div v-else>
                        <div class="alert alert-danger border-0 shadow">
                            <i class="fas fa-exclamation-triangle"></i> Soal Tidak Ditemukan!.
                        </div>
                    </div>

                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <div class="text-start">
                            <!-- Disable prev button if time_per_question is active -->
                            <button v-if="page > 1 && !questionTimeLimit" @click.prevent="prevPage" type="button" class="btn btn-gray-400 btn-sm btn-block mb-2">Sebelumnya</button>
                            <span v-else-if="page > 1 && questionTimeLimit" class="text-muted small">
                                <i class="fas fa-lock me-1"></i> Tidak bisa kembali
                            </span>
                        </div>
                        <div class="text-end">
                            <button v-if="page < Object.keys(all_questions).length" @click.prevent="nextPage" type="button" class="btn btn-gray-400 btn-sm">Selanjutnya</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5">
            <div class="card border-0 shadow">
                <div class="card-header text-center">
                    <div class="badge bg-success p-2"> {{ question_answered }} dikerjakan</div>
                    <div v-if="questionTimeLimit" class="text-muted small mt-1">
                        <i class="fas fa-info-circle me-1"></i> Mode waktu per soal aktif
                    </div>
                    <!-- Adaptive Mode Info -->
                    <div v-if="isAdaptiveMode" class="text-muted small mt-1">
                        <i class="fas fa-brain me-1"></i> Soal menyesuaikan kemampuan Anda
                    </div>
                </div>

                <!-- Adaptive Ability Progress (shown in adaptive mode) -->
                <AdaptiveProgress
                    :is-adaptive-mode="isAdaptiveMode"
                    :current-difficulty="currentDifficulty"
                    :questions="all_questions"
                />
                <div class="card-body" style="max-height: 330px; overflow-y: auto">
                    <QuestionNavigation
                        :questions="all_questions"
                        :current-page="page"
                        :question-time-limit="questionTimeLimit"
                        @navigate="clickQuestion"
                    />
                </div>
                <div class="card-footer">
                    <button @click="confirmEndExam" class="btn btn-danger btn-md border-0 shadow w-100">Akhiri Ujian</button>
                </div>
            </div>
        </div>
    </div>

    <!-- All Violation and System Modals -->
    <ViolationModals
        :show-violation-warning="showViolationWarning"
        :show-auto-submit-modal="showAutoSubmitModal"
        :show-fullscreen-modal="showFullscreenModal"
        :show-blocked-modal="showBlockedModal"
        :show-time-end-modal="showModalEndTimeExam"
        :show-liveness-modal="livenessModalVisible"
        :last-violation-message="lastViolationMessage"
        :violation-count="antiCheat.violationCount.value"
        :max-violations="antiCheatConfig.max_violations"
        :auto-submit-countdown="autoSubmitCountdown"
        :blocked-message="blockedMessage"
        :liveness-challenge="livenessChallenge"
        :liveness-countdown="livenessCountdown"
        @dismiss="dismissViolationWarning"
        @end-exam="endExam"
        @request-fullscreen="requestFullscreen"
    />

</template>

<script>
    //import layout student
    import LayoutStudent from '../../../Layouts/Student.vue';

    //import Head and Link from Inertia
    import {
        Head,
        Link,
        router
    } from '@inertiajs/vue3';

    //import ref, computed, onMounted, onUnmounted
    import {
        ref,
        computed,
        onMounted,
        onUnmounted,
        watch
    } from 'vue';

    //import VueCountdown
    import VueCountdown from '@chenfengyuan/vue-countdown';

    //import axios
    import axios from 'axios';

    //import sweet alert2
    import Swal from 'sweetalert2';

    //import useAntiCheat composable
    import { useAntiCheat } from '../../../composables/useAntiCheat.js';

    //import useFaceDetection composable
    import { useFaceDetection } from '../../../composables/useFaceDetection.js';

    //import useAudioDetection composable
    import { useAudioDetection } from '../../../composables/useAudioDetection.js';

    //import new anti-cheat composables
    import { useBrowserFingerprint } from '../../../composables/useBrowserFingerprint.js';
    import { useNetworkMonitor } from '../../../composables/useNetworkMonitor.js';
    import { useIdleDetection } from '../../../composables/useIdleDetection.js';
    import { useLivenessDetection } from '../../../composables/useLivenessDetection.js';

    //import LandscapeWarning component
    import LandscapeWarning from '../../../Components/LandscapeWarning.vue';
    
    //import ExamOnboarding component
    import ExamOnboarding from '../../../Components/ExamOnboarding.vue';

    //import Exam components
    import { 
        ExamTimer, 
        QuestionNavigation, 
        ViolationModals, 
        AnswerInput, 
        AdaptiveProgress 
    } from '../../../Components/Exam';

    export default {
        //layout
        layout: LayoutStudent,

        //register components
        components: {
            Head,
            Link,
            VueCountdown,
            LandscapeWarning,
            ExamOnboarding,
            ExamTimer,
            QuestionNavigation,
            ViolationModals,
            AnswerInput,
            AdaptiveProgress
        },

        //props
        props: {
            id: Number,
            page: Number,
            exam_group: Object,
            all_questions: Array,
            question_answered: Number,
            question_active: Object,
            answer_order: Array,
            duration: Object,
            anticheat_config: {
                type: Object,
                default: () => ({
                    enabled: true,
                    fullscreen_required: true,
                    block_tab_switch: true,
                    block_copy_paste: true,
                    block_right_click: true,
                    detect_devtools: true,
                    max_violations: 3,
                    warning_threshold: 2,
                    auto_submit_on_max_violations: true,
                })
            },
            initial_violations: {
                type: Number,
                default: 0
            },
            face_detection_enabled: {
                type: Boolean,
                default: false
            },
            audio_detection_enabled: {
                type: Boolean,
                default: false
            }
        },

        //composition API
        setup(props) {

            //define options for answer
            let options = ["A", "B", "C", "D", "E"];

            //define state counter
            const counter = ref(0);

            //define state duration
            const duration = ref(props.duration.duration);

            // Answer states
            const selectedOptions = ref([]);
            const textAnswer = ref('');

            // Time per question
            const questionTimeLimit = ref(props.exam_group.exam.time_per_question || 0);
            const questionTimeRemaining = ref(questionTimeLimit.value * 1000); // Convert to ms

            // Anti-cheat config
            const antiCheatConfig = ref(props.anticheat_config);

            // Anti-cheat UI states
            const showViolationWarning = ref(false);
            const showAutoSubmitModal = ref(false);
            const showFullscreenModal = ref(false);
            const lastViolationMessage = ref('');
            const autoSubmitCountdown = ref(5);
            const dismissedWarningBanner = ref(false);

            // Blocked environment state
            const showBlockedModal = ref(false);
            const blockedMessage = ref('');

            // Face detection
            const faceVideoRef = ref(null);
            const faceDetectionActive = ref(false);
            const faceDetection = props.face_detection_enabled ? useFaceDetection({
                checkInterval: 20000, // Check every 20 seconds
                consecutiveThreshold: 2, // 2 consecutive fails = violation
                onNoFace: () => {
                    antiCheat.recordViolation('no_face', 'Wajah tidak terdeteksi di kamera');
                },
                onMultipleFaces: (count) => {
                    antiCheat.recordViolation('multiple_faces', `Terdeteksi ${count} wajah di kamera`);
                },
            }) : null;

            // Audio detection
            const audioDetection = props.audio_detection_enabled ? useAudioDetection({
                threshold: 45, // Voice level threshold (0-100)
                sustainedDuration: 2000, // Must sustain 2 seconds to trigger
                onSuspiciousAudio: (level) => {
                    antiCheat.recordViolation('suspicious_audio', `Terdeteksi suara mencurigakan (level: ${level})`);
                },
            }) : null;

            // Browser Fingerprint - detect device change mid-exam
            const browserFingerprint = useBrowserFingerprint({
                onDeviceChanged: (data) => {
                    antiCheat.recordViolation('device_changed', 'Terdeteksi pergantian perangkat/browser');
                },
            });

            // Network Monitor - detect suspicious external requests
            const networkMonitor = useNetworkMonitor({
                onSuspiciousActivity: (record) => {
                    antiCheat.recordViolation('suspicious_network', `Terdeteksi akses ke: ${new URL(record.url).hostname}`);
                },
            });

            // Idle Detection - detect AFK students
            const idleDetection = useIdleDetection({
                idleThreshold: 120000, // 2 minutes idle = violation
                warningThreshold: 60000, // 1 minute = warning
                onIdleWarning: (elapsed) => {
                    Swal.fire({
                        title: 'Peringatan!',
                        text: 'Anda tidak aktif selama 1 menit. Silakan lanjutkan mengerjakan ujian.',
                        icon: 'warning',
                        timer: 5000,
                        showConfirmButton: false,
                    });
                },
                onIdle: (elapsed) => {
                    antiCheat.recordViolation('idle_detected', `Tidak ada aktivitas selama ${Math.round(elapsed/1000)} detik`);
                },
            });

            // Liveness Detection - verify real person with random challenges
            const livenessEnabled = true; // Enabled for production
            const livenessModalVisible = ref(false);
            const livenessChallenge = ref(null);
            const livenessCountdown = ref(0);
            const liveness = (props.face_detection_enabled && livenessEnabled) ? useLivenessDetection({
                challengeInterval: 300000, // Challenge every 5 minutes
                challengeTimeout: 15000, // 15 seconds to complete
                onChallengeFailed: () => {
                    livenessModalVisible.value = false;
                    antiCheat.recordViolation('liveness_failed', 'Gagal menyelesaikan verifikasi liveness');
                    Swal.fire({
                        title: 'Verifikasi Gagal!',
                        text: 'Anda gagal menyelesaikan verifikasi. Pelanggaran telah dicatat.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                    });
                },
                onChallengeSuccess: () => {
                    livenessModalVisible.value = false;
                    Swal.fire({
                        title: 'Verifikasi Berhasil!',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                },
            }) : null;

            // Watch liveness state
            if (liveness) {
                watch(() => liveness.showChallengeModal.value, (val) => {
                    livenessModalVisible.value = val;
                    livenessChallenge.value = liveness.currentChallenge.value;
                    livenessCountdown.value = liveness.challengeCountdown.value;
                });
                watch(() => liveness.challengeCountdown.value, (val) => {
                    livenessCountdown.value = val;
                });
            }

            // Initialize anti-cheat
            const antiCheat = useAntiCheat({
                enabled: antiCheatConfig.value.enabled,
                fullscreenRequired: antiCheatConfig.value.fullscreen_required,
                blockTabSwitch: antiCheatConfig.value.block_tab_switch,
                blockCopyPaste: antiCheatConfig.value.block_copy_paste,
                blockRightClick: antiCheatConfig.value.block_right_click,
                blockMultipleMonitors: antiCheatConfig.value.block_multiple_monitors,
                blockVirtualMachine: antiCheatConfig.value.block_virtual_machine,
                detectDevtools: antiCheatConfig.value.detect_devtools,
                maxViolations: antiCheatConfig.value.max_violations,
                warningThreshold: antiCheatConfig.value.warning_threshold,
                autoSubmitOnMaxViolations: antiCheatConfig.value.auto_submit_on_max_violations,
                examId: props.exam_group.exam.id,
                examSessionId: props.exam_group.exam_session.id,
                gradeId: props.duration.id,
                externalVideoElement: faceVideoRef,

                // Callbacks
                onViolation: (data) => {
                    // Handle warning-only (no violation recorded)
                    if (data.isWarningOnly) {
                        Swal.fire({
                            title: 'Peringatan!',
                            text: data.description,
                            icon: 'warning',
                            confirmButtonText: 'Saya Mengerti',
                            allowOutsideClick: false,
                            timer: 5000,
                            timerProgressBar: true,
                        });
                        return;
                    }
                    
                    lastViolationMessage.value = getViolationMessage(data.type);
                    showViolationWarning.value = true;
                },

                onWarningThreshold: (data) => {
                    Swal.fire({
                        title: 'Peringatan!',
                        html: `Anda telah melakukan <strong>${data.totalViolations}</strong> pelanggaran.<br>Sisa pelanggaran: <strong>${data.remaining}</strong>`,
                        icon: 'warning',
                        confirmButtonText: 'Saya Mengerti',
                        allowOutsideClick: false,
                    });
                },

                onMaxViolations: (data) => {
                    if (antiCheatConfig.value.auto_submit_on_max_violations) {
                        showAutoSubmitModal.value = true;
                        startAutoSubmitCountdown();
                    } else {
                        Swal.fire({
                            title: 'Batas Pelanggaran!',
                            html: `Anda telah mencapai batas maksimal pelanggaran (${antiCheatConfig.value.max_violations}).<br>Hasil ujian Anda mungkin akan ditandai untuk ditinjau.`,
                            icon: 'error',
                            confirmButtonText: 'Saya Mengerti',
                            allowOutsideClick: false,
                        });
                    }
                },

                onAutoSubmit: () => {
                    endExam();
                },

                onBlocked: () => {
                    Swal.fire({
                        title: 'Akun Diblokir!',
                        text: 'Akun Anda telah diblokir karena terlalu banyak pelanggaran. Ujian akan diakhiri.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                    }).then(() => {
                        endExam();
                    });
                },

                onBlockedEnvironment: (data) => {
                    blockedMessage.value = data.message;
                    showBlockedModal.value = true;
                },

                onDuplicateTab: () => {
                    Swal.fire({
                        title: 'Tab Duplikat Terdeteksi!',
                        text: 'Ujian sudah dibuka di tab lain. Tutup tab ini dan kembali ke tab ujian yang asli.',
                        icon: 'error',
                        confirmButtonText: 'Mengerti',
                        allowOutsideClick: false,
                    });
                },
            });

            // Set initial violation count
            if (props.initial_violations > 0) {
                antiCheat.violationCount.value = props.initial_violations;
                antiCheat.remainingViolations.value = antiCheatConfig.value.max_violations - props.initial_violations;
            }

            // Get violation message for UI
            const getViolationMessage = (type) => {
                const messages = {
                    'tab_switch': 'Anda terdeteksi berpindah tab/window!',
                    'fullscreen_exit': 'Anda terdeteksi keluar dari mode fullscreen!',
                    'copy_paste': 'Copy/paste tidak diizinkan selama ujian!',
                    'right_click': 'Klik kanan tidak diizinkan selama ujian!',
                    'devtools': 'Developer Tools tidak diizinkan selama ujian!',
                    'blur': 'Window ujian kehilangan fokus!',
                    'screenshot': 'Screenshot tidak diizinkan selama ujian!',
                    'keyboard_shortcut': 'Shortcut keyboard terlarang terdeteksi!',
                    'multiple_monitors': 'Penggunaan multiple monitor terdeteksi!',
                    'virtual_machine': 'Penggunaan Virtual Machine terdeteksi!',
                    'remote_desktop': 'Penggunaan Remote Desktop terdeteksi!',
                    'no_face': 'Wajah tidak terdeteksi di kamera!',
                    'multiple_faces': 'Terdeteksi lebih dari satu wajah!',
                    'suspicious_audio': 'Terdeteksi suara mencurigakan!',
                    'multiple_tabs': 'Ujian dibuka di multiple tab!',
                    'popup_blocked': 'Mencoba membuka popup/window baru!',
                    'external_link': 'Mencoba membuka link eksternal!',
                };
                return messages[type] || 'Pelanggaran terdeteksi!';
            };

            // Auto submit countdown
            let autoSubmitTimer = null;
            const startAutoSubmitCountdown = () => {
                autoSubmitCountdown.value = 5;
                autoSubmitTimer = setInterval(() => {
                    autoSubmitCountdown.value--;
                    if (autoSubmitCountdown.value <= 0) {
                        clearInterval(autoSubmitTimer);
                        endExam();
                    }
                }, 1000);
            };

            // Computed properties for UI
            const violationBadgeClass = computed(() => {
                const count = antiCheat.violationCount.value;
                const max = antiCheatConfig.value.max_violations;
                const ratio = count / max;

                if (ratio >= 0.8) return 'bg-danger';
                if (ratio >= 0.5) return 'bg-warning text-dark';
                return 'bg-success';
            });

            const violationProgressWidth = computed(() => {
                const count = antiCheat.violationCount.value;
                const max = antiCheatConfig.value.max_violations;
                return `${Math.min(100, (count / max) * 100)}%`;
            });

            const violationProgressClass = computed(() => {
                const count = antiCheat.violationCount.value;
                const max = antiCheatConfig.value.max_violations;
                const ratio = count / max;

                if (ratio >= 0.8) return 'bg-danger';
                if (ratio >= 0.5) return 'bg-warning';
                return 'bg-success';
            });

            const currentQuestionType = computed(() => {
                return props.question_active?.question?.question_type || 'multiple_choice_single';
            });

            const isMultipleChoiceSingle = computed(() => currentQuestionType.value === 'multiple_choice_single');
            const isMultipleChoiceMultiple = computed(() => currentQuestionType.value === 'multiple_choice_multiple');
            const isShortAnswer = computed(() => currentQuestionType.value === 'short_answer');
            const isEssay = computed(() => currentQuestionType.value === 'essay');
            const isTrueFalse = computed(() => currentQuestionType.value === 'true_false');
            const isMatching = computed(() => currentQuestionType.value === 'matching');

            // Matching question data
            const matchingAnswers = ref({});
            const matchingLeftItems = computed(() => {
                const pairs = props.question_active?.question?.matching_pairs || [];
                return pairs.map(p => p.left);
            });
            const matchingRightItems = computed(() => {
                const pairs = props.question_active?.question?.matching_pairs || [];
                const rights = pairs.map(p => p.right);
                // Shuffle for display
                return [...rights].sort(() => Math.random() - 0.5);
            });

            // ==========================
            // ADAPTIVE TESTING (CAT) UI
            // ==========================
            
            // Check if exam is in adaptive mode
            const isAdaptiveMode = computed(() => {
                return props.exam_group?.exam?.adaptive_mode === true || props.exam_group?.exam?.adaptive_mode === 1;
            });

            // Get current question difficulty
            const currentDifficulty = computed(() => {
                return props.question_active?.question?.difficulty || 'medium';
            });

            // Difficulty badge class
            const difficultyBadgeClass = computed(() => {
                const diff = currentDifficulty.value;
                if (diff === 'easy') return 'bg-success';
                if (diff === 'hard') return 'bg-danger';
                return 'bg-warning text-dark';
            });

            // Difficulty label
            const difficultyLabel = computed(() => {
                const labels = {
                    'easy': 'Mudah',
                    'medium': 'Sedang',
                    'hard': 'Sulit'
                };
                return labels[currentDifficulty.value] || 'Sedang';
            });

            // Difficulty icon
            const difficultyIcon = computed(() => {
                const diff = currentDifficulty.value;
                if (diff === 'easy') return 'fas fa-leaf';
                if (diff === 'hard') return 'fas fa-fire';
                return 'fas fa-balance-scale';
            });

            // Estimate ability based on answered questions (frontend approximation)
            const abilityProgress = computed(() => {
                const questions = props.all_questions || [];
                if (questions.length === 0) return 50;

                let correctWeight = 0;
                let totalWeight = 0;

                questions.forEach(q => {
                    // Only count answered questions
                    if (q.answer !== 0 || q.answer_text || q.answer_options?.length > 0) {
                        const difficulty = q.question?.difficulty || 'medium';
                        const weight = difficulty === 'easy' ? 1 : (difficulty === 'hard' ? 3 : 2);
                        totalWeight += weight;
                        
                        // Check if correct (is_correct field)
                        if (q.is_correct === 'Y' || q.is_correct === true || q.is_correct === 1) {
                            correctWeight += weight;
                        }
                    }
                });

                if (totalWeight === 0) return 50; // Start at middle
                
                // Scale to 0-100
                return Math.round((correctWeight / totalWeight) * 100);
            });

            // Ability progress width for progress bar
            const abilityProgressWidth = computed(() => {
                return abilityProgress.value + '%';
            });

            // Ability progress bar color
            const abilityProgressClass = computed(() => {
                const progress = abilityProgress.value;
                if (progress >= 70) return 'bg-success';
                if (progress >= 40) return 'bg-warning';
                return 'bg-danger';
            });

            // Ability level text
            const abilityLevelText = computed(() => {
                const progress = abilityProgress.value;
                if (progress >= 80) return 'Sangat Baik';
                if (progress >= 60) return 'Baik';
                if (progress >= 40) return 'Cukup';
                return 'Perlu Bimbingan';
            });

            // Ability level class for styling
            const abilityLevelClass = computed(() => {
                const progress = abilityProgress.value;
                if (progress >= 70) return 'text-success fw-bold';
                if (progress >= 40) return 'text-warning fw-bold';
                return 'text-danger fw-bold';
            });

            // Dismiss functions
            const dismissViolationWarning = () => {
                showViolationWarning.value = false;

                // Re-enter fullscreen if required
                if (antiCheatConfig.value.fullscreen_required && !antiCheat.isFullscreen.value) {
                    showFullscreenModal.value = true;
                }
            };

            const dismissWarningBanner = () => {
                dismissedWarningBanner.value = true;
            };

            const requestFullscreen = async () => {
                const success = await antiCheat.enterFullscreen();
                if (success) {
                    showFullscreenModal.value = false;
                }
            };

            // Watch fullscreen state
            watch(() => antiCheat.isFullscreen.value, (isFullscreen) => {
                if (!isFullscreen && antiCheatConfig.value.fullscreen_required && antiCheat.isInitialized.value) {
                    // Only show modal if not showing violation warning
                    if (!showViolationWarning.value && !showAutoSubmitModal.value) {
                        showFullscreenModal.value = true;
                    }
                }
            });

            // Watch active question to sync selected answers for non-single choice
            watch(() => props.question_active, (val) => {
                if (!val) {
                    selectedOptions.value = [];
                    textAnswer.value = '';
                    matchingAnswers.value = {};
                    return;
                }

                const type = val.question.question_type || 'multiple_choice_single';
                if (type === 'multiple_choice_multiple') {
                    const existing = val.answer_options || [];
                    selectedOptions.value = Array.isArray(existing) ? [...existing] : [];
                } else {
                    selectedOptions.value = [];
                }

                if (type === 'short_answer' || type === 'essay') {
                    textAnswer.value = val.answer_text || '';
                } else {
                    textAnswer.value = '';
                }

                if (type === 'matching') {
                    matchingAnswers.value = val.matching_answers || {};
                } else {
                    matchingAnswers.value = {};
                }
            }, { immediate: true });

            //handleChangeDuration
            const handleChangeDuration = (() => {

                //decrement duration
                duration.value = duration.value - 1000;

                //increment counter
                counter.value = counter.value + 1;

                //cek jika durasi di atas 0
                if (duration.value > 0) {

                    //sync with server every 10 seconds - SERVER TIME IS AUTHORITATIVE
                    if (counter.value % 10 == 1) {
                        syncDurationWithServer();
                    }

                }

            });

            // Sync duration with server - SERVER IS AUTHORITATIVE
            const syncDurationWithServer = async () => {
                try {
                    const response = await axios.put(`/student/exam-duration/update/${props.duration.id}`, {
                        duration: duration.value
                    });
                    
                    // ALWAYS use server duration as authoritative
                    if (response.data.duration !== undefined && response.data.duration >= 0) {
                        duration.value = response.data.duration;
                    }
                    
                    // Auto end if server says ended
                    if (response.data.ended) {
                        showModalEndTimeExam.value = true;
                    }
                } catch (error) {
                    console.warn('Duration sync failed:', error);
                }
            };

            // Sync on page visibility change (wake from sleep/tab switch back)
            const handleVisibilityChange = () => {
                if (document.visibilityState === 'visible') {
                    // Force sync with server when page becomes visible
                    syncDurationWithServer();
                }
            };

            // Handle question time end - auto move to next question
            const handleQuestionTimeEnd = () => {
                if (props.page < props.all_questions.length) {
                    Swal.fire({
                        title: 'Waktu Soal Habis!',
                        text: 'Pindah ke soal berikutnya...',
                        icon: 'warning',
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => {
                        nextPage();
                    });
                }
            };

            //metohd prevPage
            const prevPage = (() => {

                //update duration
                axios.put(`/student/exam-duration/update/${props.duration.id}`, {
                    duration: duration.value
                });

                //redirect to prevPage
                router.get(`/student/exam/${props.id}/${props.page - 1}`);

            });

            //method nextPage
            const nextPage = (() => {

                //update duration
                axios.put(`/student/exam-duration/update/${props.duration.id}`, {
                    duration: duration.value
                });

                //redirect to nextPage
                router.get(`/student/exam/${props.id}/${props.page + 1}`);
            });

            //method clickQuestion
            const clickQuestion = ((index) => {

                //update duration
                axios.put(`/student/exam-duration/update/${props.duration.id}`, {
                    duration: duration.value
                });

                //redirect to questin
                router.get(`/student/exam/${props.id}/${index + 1}`);
            });

            const isAnswered = (question) => {
                if (!question) return false;
                if (question.answer && question.answer !== 0) return true;
                if (question.answer_options && question.answer_options.length > 0) return true;
                if (question.answer_text && question.answer_text.trim() !== '') return true;
                if (question.matching_answers && Object.keys(question.matching_answers).length > 0) return true;
                return false;
            };

            //method submit answer (single choice)
            const submitAnswerSingle = ((answer) => {
                router.post('/student/exam-answer', {
                    exam_id: props.exam_group.exam.id,
                    exam_session_id: props.exam_group.exam_session.id,
                    question_id: props.question_active.question.id,
                    answer: answer,
                    duration: duration.value
                });
            });

            //method submit answer (multiple choice multiple)
            const submitAnswerMultiple = ((options) => {
                // Use emitted options or fallback to local state
                const answersToSubmit = options || selectedOptions.value;
                router.post('/student/exam-answer', {
                    exam_id: props.exam_group.exam.id,
                    exam_session_id: props.exam_group.exam_session.id,
                    question_id: props.question_active.question.id,
                    answer_options: answersToSubmit,
                    duration: duration.value
                }, {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Jawaban tersimpan',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                });
            });

            // Loading state
            const isSubmitting = ref(false);

            //method submitAnswerText
            const submitAnswerText = ((text) => {
                // Use emitted text or fallback to local state
                const answerToSubmit = text || textAnswer.value;
                isSubmitting.value = true;
                router.post('/student/exam-answer', {
                    exam_id: props.exam_group.exam.id,
                    exam_session_id: props.exam_group.exam_session.id,
                    question_id: props.question_active.question.id,
                    answer_text: answerToSubmit,
                    duration: duration.value
                }, {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Jawaban tersimpan',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    },
                    onFinish: () => {
                        isSubmitting.value = false;
                    }
                });
            });

            //method submit answer (matching)
            const submitAnswerMatching = ((answers) => {
                // Use emitted answers or fallback to local state
                const answersToSubmit = answers || matchingAnswers.value;
                router.post('/student/exam-answer', {
                    exam_id: props.exam_group.exam.id,
                    exam_session_id: props.exam_group.exam_session.id,
                    question_id: props.question_active.question.id,
                    matching_answers: answersToSubmit,
                    duration: duration.value
                }, {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Jawaban tersimpan',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                });
            });

            //define state modal
            const showModalEndExam      = ref(false); // Deprecated, replaced by SweetAlert
            const showModalEndTimeExam  = ref(false);

            // Calculate total duration in ms
            const totalDuration = props.exam_group.exam.duration * 60 * 1000;

            // Timer progress computed (clamped to 0-100 to prevent UI overflow)
            const timerProgress = computed(() => {
                if (totalDuration <= 0) return 0;
                // Clamp duration to prevent negative values from network delays
                const safeDuration = Math.max(0, duration.value);
                const progress = (safeDuration / totalDuration) * 100;
                // Clamp progress to 0-100 range
                return Math.min(100, Math.max(0, progress));
            });
            
            const timerProgressColor = computed(() => {
                if (timerProgress.value < 10) return 'bg-danger';
                if (timerProgress.value < 30) return 'bg-warning';
                return 'bg-info';
            });

            // Confirm end exam with SweetAlert2
            const confirmEndExam = () => {
                Swal.fire({
                    title: 'Akhiri Ujian?',
                    text: "Setelah mengakhiri ujian, Anda tidak dapat kembali mengerjakan. Yakin ingin menyelesaikan?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Selesai!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        endExam();
                    }
                });
            };

            //method endExam
            const endExam = (() => {
                // Cleanup anti-cheat
                antiCheat.cleanup();

                // Exit fullscreen
                antiCheat.exitFullscreen();

                // Clear auto submit timer if running
                if (autoSubmitTimer) {
                    clearInterval(autoSubmitTimer);
                }

                router.post('/student/exam-end', {
                    exam_group_id: props.exam_group.id,
                    exam_id: props.exam_group.exam.id,
                    exam_session_id: props.exam_group.exam_session.id,
                });

                // SweetAlert will be handled by the redirect or on-finish of the Inertia visit if needed, 
                // but usually the backend redirects to result page.
            });

            // Cleanup on unmount
            onUnmounted(() => {
                antiCheat.cleanup();
                if (autoSubmitTimer) {
                    clearInterval(autoSubmitTimer);
                }
                if (faceDetection) {
                    faceDetection.cleanup();
                }
                if (audioDetection) {
                    audioDetection.cleanup();
                }
                // Cleanup new composables
                networkMonitor.stop();
                idleDetection.stop();
                // Remove visibility listener
                document.removeEventListener('visibilitychange', handleVisibilityChange);
            });

            // Initialize face detection after mount
            onMounted(async () => {
                // Add visibility change listener for timer sync (wake from sleep)
                document.addEventListener('visibilitychange', handleVisibilityChange);
                
                // Initial sync with server to get accurate time
                syncDurationWithServer();

                // Initialize browser fingerprint
                await browserFingerprint.initialize();

                // Start network monitoring
                networkMonitor.start();

                // Start idle detection
                await idleDetection.start();

                // Initialize face detection with retry
                if (props.face_detection_enabled && faceDetection) {
                    // Wait for video element to be ready
                    await new Promise(resolve => setTimeout(resolve, 500));
                    
                    if (faceVideoRef.value) {
                        try {
                            const initialized = await faceDetection.initialize(faceVideoRef.value);
                            if (initialized) {
                                faceDetection.start();
                                faceDetectionActive.value = true;
                            }
                        } catch (e) {
                            console.warn('Face detection init failed:', e);
                        }
                    }
                }

                // Initialize audio detection
                if (props.audio_detection_enabled && audioDetection) {
                    try {
                        const initialized = await audioDetection.initialize();
                        if (initialized) {
                            audioDetection.start();
                        }
                    } catch (e) {
                        console.warn('Audio detection init failed:', e);
                    }
                }

                // Initialize liveness detection (uses same video as face detection)
                if (props.face_detection_enabled && liveness && faceVideoRef.value) {
                    try {
                        const initialized = await liveness.initialize(faceVideoRef.value);
                        if (initialized) {
                            liveness.start();
                        }
                    } catch (e) {
                        console.warn('Liveness detection init failed:', e);
                    }
                }
            });

            //return
            return {
                options,
                duration,
                handleChangeDuration,
                handleQuestionTimeEnd,
                questionTimeLimit,
                questionTimeRemaining,
                prevPage,
                nextPage,
                clickQuestion,
                submitAnswerSingle,
                submitAnswerMultiple,
                submitAnswerText,
                isSubmitting,
                submitAnswerMatching,
                showModalEndExam,
                showModalEndTimeExam,
                endExam,
                confirmEndExam,
                timerProgress,
                timerProgressColor,
                isAnswered,
                selectedOptions,
                textAnswer,
                matchingAnswers,
                matchingLeftItems,
                matchingRightItems,
                isMultipleChoiceSingle,
                isMultipleChoiceMultiple,
                isShortAnswer,
                isEssay,
                isTrueFalse,
                isMatching,

                // Anti-cheat
                antiCheat,
                antiCheatConfig,
                showViolationWarning,
                showAutoSubmitModal,
                showFullscreenModal,
                showBlockedModal,
                blockedMessage,
                lastViolationMessage,
                autoSubmitCountdown,
                dismissedWarningBanner,
                violationBadgeClass,
                violationProgressWidth,
                violationProgressClass,
                dismissViolationWarning,
                dismissWarningBanner,
                requestFullscreen,

                // Face detection
                faceVideoRef,
                faceDetectionActive,

                // Liveness detection
                livenessModalVisible,
                livenessChallenge,
                livenessCountdown,

                // Adaptive Testing (CAT)
                isAdaptiveMode,
                currentDifficulty,
                difficultyBadgeClass,
                difficultyLabel,
                difficultyIcon,
                abilityProgress,
                abilityProgressWidth,
                abilityProgressClass,
                abilityLevelText,
                abilityLevelClass,
            }

        }
    }

</script>

<style scoped>
/* Anti-cheat styles */
.modal.show {
    display: block;
}

.badge {
    font-size: 0.85rem;
}

/* Disable text selection during exam */
.card-body {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

/* Prevent drag */
img {
    -webkit-user-drag: none;
    -khtml-user-drag: none;
    -moz-user-drag: none;
    -o-user-drag: none;
}

/* Adaptive Testing (CAT) Styles */
.bg-purple {
    background-color: #6f42c1 !important;
    color: white;
}

.progress-bar {
    transition: width 0.5s ease-in-out;
}
</style>
