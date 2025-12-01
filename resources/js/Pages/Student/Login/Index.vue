<template>
    <Head>
        <title>Login Siswa - Aplikasi Ujian Online</title>
    </Head>
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="bg-white shadow border-0 rounded border-light p-4 p-lg-5 w-100 fmxw-500">
                <!-- Success Message -->
                <div v-if="$page.props.session.success" class="alert alert-success mt-2">
                    <i class="fa fa-check-circle me-2"></i>
                    {{ $page.props.session.success }}
                </div>

                <!-- Error Message -->
                <div v-if="errors.message" class="alert alert-danger mt-2">
                    <i class="fa fa-exclamation-circle me-2"></i>
                    {{ errors.message }}
                </div>

                <!-- Session Error with Rate Limit Info -->
                <div v-if="$page.props.session.error" class="alert alert-danger mt-2">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    {{ $page.props.session.error }}

                    <!-- Countdown Timer for Rate Limiting -->
                    <div v-if="isLocked" class="mt-2">
                        <small class="d-block">
                            <i class="fa fa-clock me-1"></i>
                            Waktu tunggu: <strong>{{ formatTime(countdown) }}</strong>
                        </small>
                        <div class="progress mt-2" style="height: 5px;">
                            <div
                                class="progress-bar bg-danger"
                                role="progressbar"
                                :style="{ width: progressWidth + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Attempts Warning -->
                <div v-if="$page.props.session.attempts_left && $page.props.session.attempts_left <= 3" class="alert alert-warning mt-2">
                    <i class="fa fa-exclamation-circle me-2"></i>
                    <strong>Peringatan:</strong> Sisa {{ $page.props.session.attempts_left }} percobaan login sebelum akun dikunci sementara.
                </div>

                <form @submit.prevent="submit" class="mt-4">

                    <div class="form-group mb-4">
                        <label for="nisn">NISN</label>
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon1">
                                <i class="fa fa-id-card"></i>
                            </span>
                            <input
                                type="number"
                                class="form-control"
                                v-model="form.nisn"
                                placeholder="Masukkan NISN"
                                :disabled="isLocked || isLoading"
                                required
                            >
                        </div>
                        <div v-if="errors.nisn" class="alert alert-danger mt-2">
                            {{ errors.nisn }}
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-group mb-4">
                            <label for="password">Password</label>
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon2">
                                    <i class="fa fa-lock"></i>
                                </span>
                                <input
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Masukkan Password"
                                    class="form-control"
                                    v-model="form.password"
                                    :disabled="isLocked || isLoading"
                                    required
                                >
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    @click="showPassword = !showPassword"
                                    :disabled="isLocked || isLoading"
                                >
                                    <i :class="showPassword ? 'fa fa-eye-slash' : 'fa fa-eye'"></i>
                                </button>
                            </div>
                            <div v-if="errors.password" class="alert alert-danger mt-2">
                                {{ errors.password }}
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-top mb-4">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    v-model="form.remember"
                                    id="remember"
                                    :disabled="isLocked || isLoading"
                                >
                                <label class="form-check-label mb-0" for="remember">
                                    Ingat saya
                                </label>
                            </div>
                        </div>

                    </div>

                    <div class="d-grid">
                        <button
                            type="submit"
                            class="btn btn-gray-800"
                            :disabled="isLocked || isLoading"
                        >
                            <span v-if="isLoading">
                                <i class="fa fa-spinner fa-spin me-2"></i>
                                Memproses...
                            </span>
                            <span v-else-if="isLocked">
                                <i class="fa fa-lock me-2"></i>
                                Terkunci ({{ formatTime(countdown) }})
                            </span>
                            <span v-else>
                                <i class="fa fa-sign-in-alt me-2"></i>
                                LOGIN
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Security Info -->
                <div class="mt-4 text-center">
                    <small class="text-muted">
                        <i class="fa fa-shield-alt me-1"></i>
                        Maksimal 5 percobaan login dalam 5 menit
                    </small>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    //import layout
    import LayoutStudent from '../../../Layouts/Student.vue';

    //import Head from Inertia
    import {
        Head,
        router,
        usePage
    } from '@inertiajs/vue3';

    //import reactive and ref
    import {
        reactive,
        ref,
        computed,
        onMounted,
        onUnmounted,
        watch
    } from 'vue';

    export default {

        //layout
        layout: LayoutStudent,

        //register component
        components: {
            Head
        },

        //props
        props: {
            errors: Object,
        },

        //inisialisasi composition API
        setup(props) {

            //define form state
            const form = reactive({
                nisn: '',
                password: '',
                remember: false,
            });

            //loading state
            const isLoading = ref(false);

            //show password toggle
            const showPassword = ref(false);

            //countdown for rate limiting
            const countdown = ref(0);
            const initialCountdown = ref(0);
            let countdownInterval = null;

            //get page props
            const page = usePage();

            //check if rate limited
            const isLocked = computed(() => countdown.value > 0);

            //progress width for countdown bar
            const progressWidth = computed(() => {
                if (initialCountdown.value === 0) return 0;
                return (countdown.value / initialCountdown.value) * 100;
            });

            //format time helper
            const formatTime = (seconds) => {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                if (mins > 0) {
                    return `${mins}m ${secs}s`;
                }
                return `${secs}s`;
            };

            //start countdown timer
            const startCountdown = (seconds) => {
                countdown.value = seconds;
                initialCountdown.value = seconds;

                if (countdownInterval) {
                    clearInterval(countdownInterval);
                }

                countdownInterval = setInterval(() => {
                    if (countdown.value > 0) {
                        countdown.value--;
                    } else {
                        clearInterval(countdownInterval);
                        countdownInterval = null;
                    }
                }, 1000);
            };

            //watch for retry_after in session
            watch(
                () => page.props.session.retry_after,
                (newValue) => {
                    if (newValue && newValue > 0) {
                        startCountdown(newValue);
                    }
                },
                { immediate: true }
            );

            //submit method
            const submit = () => {
                if (isLocked.value || isLoading.value) {
                    return;
                }

                isLoading.value = true;

                //send data to server
                router.post('/students/login', {
                    nisn: form.nisn,
                    password: form.password,
                    remember: form.remember,
                }, {
                    onFinish: () => {
                        isLoading.value = false;
                    },
                    onError: () => {
                        isLoading.value = false;
                    }
                });
            };

            //check for initial retry_after on mount
            onMounted(() => {
                if (page.props.session.retry_after) {
                    startCountdown(page.props.session.retry_after);
                }
            });

            //cleanup on unmount
            onUnmounted(() => {
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                }
            });

            //return
            return {
                form,
                submit,
                isLoading,
                showPassword,
                countdown,
                isLocked,
                progressWidth,
                formatTime,
            }
        }

    }

</script>

<style scoped>
.progress {
    background-color: #e9ecef;
    border-radius: 3px;
}

.progress-bar {
    transition: width 1s linear;
}

.btn:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

.form-control:disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
}
</style>
