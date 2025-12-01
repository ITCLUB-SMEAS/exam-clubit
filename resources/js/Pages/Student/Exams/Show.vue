<template>
    <Head>
        <title>Ujian Dengan Nomor Soal : {{ page }} - Aplikasi Ujian Online</title>
    </Head>

    <!-- Anti-Cheat Warning Banner -->
    <div v-if="antiCheat.warningReached.value && !showViolationWarning" class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i>
        <strong>Peringatan!</strong> Anda telah melakukan {{ antiCheat.violationCount.value }} pelanggaran.
        Sisa pelanggaran yang diizinkan: {{ antiCheat.remainingViolations.value }}
        <button type="button" class="btn-close" @click="dismissWarningBanner"></button>
    </div>

    <!-- Violation Counter Badge -->
    <div v-if="antiCheatConfig.enabled" class="position-fixed" style="top: 10px; right: 10px; z-index: 1050;">
        <span :class="violationBadgeClass" class="badge p-2">
            <i class="fa fa-shield-alt me-1"></i>
            Pelanggaran: {{ antiCheat.violationCount.value }}/{{ antiCheatConfig.max_violations }}
        </span>
    </div>

    <div class="row mb-5">
        <div class="col-md-7">
            <div class="card border-0 shadow">
                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="mb-0">Soal No. <strong class="fw-bold">{{ page }}</strong></h5>
                        </div>
                        <div>
                            <VueCountdown :time="duration" @progress="handleChangeDuration" @end="showModalEndTimeExam = true" v-slot="{ hours, minutes, seconds }">
                                <span class="badge bg-info p-2"> <i class="fa fa-clock"></i> {{ hours }} jam,
                                    {{ minutes }} menit, {{ seconds }} detik.</span>
                            </VueCountdown>
                        </div>
                    </div>
                </div>
                <div class="card-body">

                    <div v-if="question_active !== null">

                        <div>
                            <p v-html="question_active.question.question"></p>
                        </div>

                        <div v-if="isMultipleChoiceSingle">
                            <table>
                                <tbody>
                                    <tr v-for="(answer, index) in answer_order" :key="index">
                                        <td width="50" style="padding: 10px;">

                                            <button v-if="answer == question_active.answer" class="btn btn-info btn-sm w-100 shdaow">{{ options[index] }}</button>

                                            <button v-else @click.prevent="submitAnswerSingle(answer)" class="btn btn-outline-info btn-sm w-100 shdaow">{{ options[index] }}</button>

                                        </td>
                                        <td style="padding: 10px;">
                                            <p v-html="question_active.question['option_'+answer]"></p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else-if="isMultipleChoiceMultiple">
                            <div class="list-group">
                                <label v-for="(answer, index) in answer_order" :key="index" class="list-group-item d-flex align-items-center">
                                    <input class="form-check-input me-2" type="checkbox" :value="answer" v-model="selectedOptions">
                                    <span class="badge bg-secondary me-2">{{ options[index] }}</span>
                                    <span v-html="question_active.question['option_'+answer]"></span>
                                </label>
                            </div>
                            <button @click.prevent="submitAnswerMultiple" class="btn btn-info btn-sm mt-3">Simpan Jawaban</button>
                        </div>

                        <div v-else-if="isShortAnswer">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Jawaban Singkat</label>
                                <input type="text" class="form-control" v-model="textAnswer" placeholder="Ketik jawaban Anda" />
                            </div>
                            <button @click.prevent="submitAnswerText" class="btn btn-info btn-sm">Simpan Jawaban</button>
                        </div>

                        <div v-else-if="isEssay">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Jawaban Essay</label>
                                <textarea class="form-control" rows="6" v-model="textAnswer" placeholder="Tulis jawaban Anda"></textarea>
                                <small class="text-muted">Jawaban akan dinilai manual.</small>
                            </div>
                            <button @click.prevent="submitAnswerText" class="btn btn-info btn-sm">Simpan Jawaban</button>
                        </div>

                        <div v-else-if="isTrueFalse">
                            <div class="d-flex gap-3">
                                <button @click.prevent="submitAnswerSingle(1)" :class="['btn', 'btn-lg', question_active.answer == 1 ? 'btn-success' : 'btn-outline-success']">
                                    <i class="fa fa-check me-2"></i> Benar
                                </button>
                                <button @click.prevent="submitAnswerSingle(2)" :class="['btn', 'btn-lg', question_active.answer == 2 ? 'btn-danger' : 'btn-outline-danger']">
                                    <i class="fa fa-times me-2"></i> Salah
                                </button>
                            </div>
                        </div>

                        <div v-else-if="isMatching">
                            <div class="mb-3">
                                <p class="text-muted mb-3">Jodohkan pernyataan di kiri dengan jawaban yang tepat di kanan.</p>
                                <div v-for="(pair, idx) in matchingLeftItems" :key="idx" class="row mb-2 align-items-center">
                                    <div class="col-5">
                                        <div class="p-2 bg-light rounded">{{ pair }}</div>
                                    </div>
                                    <div class="col-2 text-center">→</div>
                                    <div class="col-5">
                                        <select class="form-select" v-model="matchingAnswers[pair]">
                                            <option value="">-- Pilih --</option>
                                            <option v-for="right in matchingRightItems" :key="right" :value="right">{{ right }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button @click.prevent="submitAnswerMatching" class="btn btn-info btn-sm">Simpan Jawaban</button>
                        </div>

                    </div>

                    <div v-else>
                        <div class="alert alert-danger border-0 shadow">
                            <i class="fa fa-exclamation-triangle"></i> Soal Tidak Ditemukan!.
                        </div>
                    </div>

                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <div class="text-start">
                            <button v-if="page > 1" @click.prevent="prevPage" type="button" class="btn btn-gray-400 btn-sm btn-block mb-2">Sebelumnya</button>
                        </div>
                        <div class="text-end">
                            <button v-if="page < Object.keys(all_questions).length" @click.prevent="nextPage" type="button" class="btn btn-gray-400 btn-sm">Selanjutnya</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card border-0 shadow">
                <div class="card-header text-center">
                    <div class="badge bg-success p-2"> {{ question_answered }} dikerjakan</div>
                </div>
                <div class="card-body" style="height: 330px;overflow-y: auto">

                    <div v-for="(question, index) in all_questions" :key="index">
                        <div width="20%" style="width: 20%; float: left;">
                            <div style="padding: 5px;">

                                <button @click.prevent="clickQuestion(index)" v-if="index+1 == page" class="btn btn-gray-400 btn-sm w-100">{{ index + 1 }}</button>

                                <button @click.prevent="clickQuestion(index)" v-if="index+1 != page && !isAnswered(question)" class="btn btn-outline-info btn-sm w-100">{{ index + 1 }}</button>

                                <button @click.prevent="clickQuestion(index)" v-if="index+1 != page && isAnswered(question)" class="btn btn-info btn-sm w-100">{{ index + 1 }}</button>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="card-footer">
                    <button @click="showModalEndExam = true" class="btn btn-danger btn-md border-0 shadow w-100">Akhiri Ujian</button>
                </div>
            </div>
        </div>
    </div>

    <!-- modal akhiri ujian -->
    <div v-if="showModalEndExam" class="modal fade" :class="{ 'show': showModalEndExam }" tabindex="-1" aria-hidden="true" style="display:block;" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Akhiri Ujian ?</h5>
                </div>
                <div class="modal-body">
                    Setelah mengakhiri ujian, Anda tidak dapat kembali ke ujian ini lagi. Yakin akan mengakhiri ujian?
                </div>
                <div class="modal-footer">
                    <button @click.prevent="endExam" type="button" class="btn btn-primary">Ya, Akhiri</button>
                    <button @click.prevent="showModalEndExam = false" type="button" class="btn btn-secondary">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- modal waktu ujian berakhir -->
    <div v-if="showModalEndTimeExam" class="modal fade" :class="{ 'show': showModalEndTimeExam }" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" style="display:block;" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Waktu Habis !</h5>
                </div>
                <div class="modal-body">
                    Waktu ujian sudah berakhir!. Klik <strong class="fw-bold">Ya</strong> untuk mengakhiri ujian.
                </div>
                <div class="modal-footer">
                    <button @click.prevent="endExam" type="button" class="btn btn-primary">Ya</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Peringatan Pelanggaran -->
    <div v-if="showViolationWarning" class="modal fade show" tabindex="-1" style="display:block; background: rgba(0,0,0,0.7);" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-warning">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        Peringatan Pelanggaran!
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fa fa-shield-alt fa-4x text-warning"></i>
                    </div>
                    <h5 class="text-danger">{{ lastViolationMessage }}</h5>
                    <p class="mb-2">
                        Total Pelanggaran: <strong>{{ antiCheat.violationCount.value }}</strong> / {{ antiCheatConfig.max_violations }}
                    </p>
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar"
                             :class="violationProgressClass"
                             role="progressbar"
                             :style="{ width: violationProgressWidth }"
                             :aria-valuenow="antiCheat.violationCount.value"
                             aria-valuemin="0"
                             :aria-valuemax="antiCheatConfig.max_violations">
                            {{ antiCheat.violationCount.value }} / {{ antiCheatConfig.max_violations }}
                        </div>
                    </div>
                    <p class="text-muted small">
                        <i class="fa fa-info-circle me-1"></i>
                        Jika Anda mencapai batas maksimal pelanggaran, ujian dapat diakhiri secara otomatis.
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button @click="dismissViolationWarning" type="button" class="btn btn-warning">
                        <i class="fa fa-check me-1"></i> Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Auto Submit (Max Violations) -->
    <div v-if="showAutoSubmitModal" class="modal fade show" tabindex="-1" style="display:block; background: rgba(0,0,0,0.8);" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-ban me-2"></i>
                        Batas Pelanggaran Tercapai!
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fa fa-times-circle fa-4x text-danger"></i>
                    </div>
                    <h5>Ujian akan diakhiri secara otomatis</h5>
                    <p>Anda telah mencapai batas maksimal pelanggaran ({{ antiCheatConfig.max_violations }}).</p>
                    <p class="text-muted">Ujian akan diakhiri dalam <strong>{{ autoSubmitCountdown }}</strong> detik...</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button @click.prevent="endExam" type="button" class="btn btn-danger">
                        <i class="fa fa-stop me-1"></i> Akhiri Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Fullscreen Required -->
    <div v-if="showFullscreenModal" class="modal fade show" tabindex="-1" style="display:block; background: rgba(0,0,0,0.9);" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-expand me-2"></i>
                        Mode Fullscreen Diperlukan
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fa fa-desktop fa-4x text-primary"></i>
                    </div>
                    <h5>Ujian ini memerlukan mode fullscreen</h5>
                    <p class="text-muted">Klik tombol di bawah untuk masuk ke mode fullscreen dan melanjutkan ujian.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button @click="requestFullscreen" type="button" class="btn btn-primary btn-lg">
                        <i class="fa fa-expand me-1"></i> Masuk Fullscreen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Blocked Environment (Multiple Monitors / VM) -->
    <div v-if="showBlockedModal" class="modal fade show" tabindex="-1" style="display:block; background: rgba(0,0,0,0.95);" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fa fa-ban me-2"></i>
                        Lingkungan Tidak Diizinkan
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fa fa-exclamation-circle fa-4x text-danger"></i>
                    </div>
                    <h5 class="text-danger">{{ blockedMessage }}</h5>
                    <p class="text-muted">
                        Ujian ini tidak dapat dilanjutkan dengan konfigurasi perangkat Anda saat ini.
                        Silakan gunakan satu monitor dan pastikan tidak menggunakan Virtual Machine.
                    </p>
                </div>
                <div class="modal-footer justify-content-center">
                    <Link href="/student/dashboard" class="btn btn-secondary">
                        <i class="fa fa-arrow-left me-1"></i> Kembali ke Dashboard
                    </Link>
                </div>
            </div>
        </div>
    </div>

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

    export default {
        //layout
        layout: LayoutStudent,

        //register components
        components: {
            Head,
            Link,
            VueCountdown
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
                    max_violations: 10,
                    warning_threshold: 3,
                    auto_submit_on_max_violations: false,
                })
            },
            initial_violations: {
                type: Number,
                default: 0
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

                // Callbacks
                onViolation: (data) => {
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

                onBlockedEnvironment: (data) => {
                    blockedMessage.value = data.message;
                    showBlockedModal.value = true;
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

                    //sync with server every 10 seconds
                    if (counter.value % 10 == 1) {

                        //update duration and sync with server time
                        axios.put(`/student/exam-duration/update/${props.duration.id}`, {
                            duration: duration.value
                        }).then(response => {
                            // Sync duration with server time
                            if (response.data.duration !== undefined) {
                                duration.value = response.data.duration;
                            }
                            // Auto end if server says ended
                            if (response.data.ended) {
                                showModalEndTimeExam.value = true;
                            }
                        }).catch(() => {
                            // Keep local duration on error
                        });

                    }

                }

            });

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
            const submitAnswerMultiple = (() => {
                router.post('/student/exam-answer', {
                    exam_id: props.exam_group.exam.id,
                    exam_session_id: props.exam_group.exam_session.id,
                    question_id: props.question_active.question.id,
                    answer_options: selectedOptions.value,
                    duration: duration.value
                });
            });

            //method submit answer (text/essay)
            const submitAnswerText = (() => {
                router.post('/student/exam-answer', {
                    exam_id: props.exam_group.exam.id,
                    exam_session_id: props.exam_group.exam_session.id,
                    question_id: props.question_active.question.id,
                    answer_text: textAnswer.value,
                    duration: duration.value
                });
            });

            //method submit answer (matching)
            const submitAnswerMatching = (() => {
                router.post('/student/exam-answer', {
                    exam_id: props.exam_group.exam.id,
                    exam_session_id: props.exam_group.exam_session.id,
                    question_id: props.question_active.question.id,
                    matching_answers: matchingAnswers.value,
                    duration: duration.value
                });
            });

            //define state modal
            const showModalEndExam      = ref(false);
            const showModalEndTimeExam  = ref(false);

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

                //show success alert
                Swal.fire({
                    title: 'Success!',
                    text: 'Ujian Selesai!.',
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 4000
                });

            });

            // Cleanup on unmount
            onUnmounted(() => {
                antiCheat.cleanup();
                if (autoSubmitTimer) {
                    clearInterval(autoSubmitTimer);
                }
            });

            //return
            return {
                options,
                duration,
                handleChangeDuration,
                prevPage,
                nextPage,
                clickQuestion,
                submitAnswerSingle,
                submitAnswerMultiple,
                submitAnswerText,
                submitAnswerMatching,
                showModalEndExam,
                showModalEndTimeExam,
                endExam,
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
    user-drag: none;
}
</style>
