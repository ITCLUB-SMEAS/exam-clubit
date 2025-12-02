<template>
    <Head>
        <title>Login Administrator - Aplikasi Ujian Online</title>
    </Head>
    <div class="bg-white shadow border-0 rounded border-light p-4 p-lg-5 w-100 fmxw-500">
        <div class="text-center text-md-center mb-4 mt-md-0">
            <h3>ADMINISTRATOR</h3>
        </div>
        <form @submit.prevent="submit" class="mt-4">
            <div class="form-group mb-4">
                <label for="email">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text" id="basic-addon1">
                        <i class="fa fa-envelope"></i>
                    </span>
                    <input type="email" class="form-control" v-model="form.email" placeholder="Email Address">
                </div>
                <div v-if="errors.email" class="alert alert-danger mt-2">
                    {{ errors.email }}
                </div>
            </div>

            <div class="form-group">
                <div class="form-group mb-4">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <span class="input-group-text" id="basic-addon2">
                            <i class="fa fa-lock"></i>
                        </span>
                        <input type="password" placeholder="Password" class="form-control" v-model="form.password">
                    </div>
                    <div v-if="errors.password" class="alert alert-danger mt-2">
                        {{ errors.password }}
                    </div>
                </div>

                <!-- Turnstile Widget -->
                <div class="mb-4">
                    <div ref="turnstileRef"></div>
                    <div v-if="errors.cf_turnstile_response" class="alert alert-danger mt-2">
                        {{ errors.cf_turnstile_response }}
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-top mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="remember">
                        <label class="form-check-label mb-0" for="remember">
                            Remember me
                        </label>
                    </div>
                </div>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-gray-800" :disabled="!turnstileToken">LOGIN</button>
            </div>
        </form>
    </div>
</template>

<script>
import LayoutAuth from '../../Layouts/Auth.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, onMounted } from 'vue';

export default {
    layout: LayoutAuth,
    components: { Head },
    props: {
        errors: Object,
        session: Object,
        turnstileSiteKey: String
    },

    setup(props) {
        const form = reactive({ email: '', password: '' });
        const turnstileToken = ref('');
        const turnstileRef = ref(null);

        onMounted(() => {
            if (window.turnstile) {
                renderTurnstile();
            } else {
                const script = document.createElement('script');
                script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onTurnstileLoad';
                script.async = true;
                document.head.appendChild(script);
                window.onTurnstileLoad = renderTurnstile;
            }
        });

        const renderTurnstile = () => {
            if (turnstileRef.value && window.turnstile) {
                window.turnstile.render(turnstileRef.value, {
                    sitekey: props.turnstileSiteKey,
                    callback: (token) => { turnstileToken.value = token; },
                    'expired-callback': () => { turnstileToken.value = ''; }
                });
            }
        };

        const submit = () => {
            router.post('/admin/login', {
                email: form.email,
                password: form.password,
                cf_turnstile_response: turnstileToken.value
            }, {
                onFinish: () => {
                    if (window.turnstile) window.turnstile.reset();
                    turnstileToken.value = '';
                }
            });
        };

        return { form, submit, turnstileToken, turnstileRef, turnstileSiteKey: props.turnstileSiteKey };
    }
}
</script>
