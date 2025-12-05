<template>
    <Head>
        <title>Profil Saya - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mt-5">
        <div class="row">
            <!-- Sidebar Profile -->
            <div class="col-md-3">
                <div class="card border-0 shadow text-center">
                    <div class="card-body">
                        <div class="position-relative d-inline-block mb-3">
                            <img :src="avatarUrl" class="rounded-circle" width="120" height="120" style="object-fit: cover;">
                            <label class="position-absolute bottom-0 end-0 btn btn-sm btn-primary rounded-circle" style="width: 32px; height: 32px;">
                                <i class="fa fa-camera"></i>
                                <input type="file" @change="uploadPhoto" accept="image/*" class="d-none">
                            </label>
                        </div>
                        <h5 class="mb-1">{{ user.name }}</h5>
                        <p class="text-muted small">{{ user.email }}</p>
                        <span class="badge bg-primary">{{ user.role }}</span>
                    </div>
                </div>

                <!-- Quick Nav -->
                <div class="list-group mt-3 shadow-sm">
                    <a href="#profile" class="list-group-item list-group-item-action" :class="{active: tab === 'profile'}" @click.prevent="tab = 'profile'">
                        <i class="fa fa-user me-2"></i>Profil
                    </a>
                    <a href="#password" class="list-group-item list-group-item-action" :class="{active: tab === 'password'}" @click.prevent="tab = 'password'">
                        <i class="fa fa-key me-2"></i>Password
                    </a>
                    <a href="#2fa" class="list-group-item list-group-item-action" :class="{active: tab === '2fa'}" @click.prevent="tab = '2fa'">
                        <i class="fa fa-shield-alt me-2"></i>Keamanan 2FA
                    </a>
                    <a href="#history" class="list-group-item list-group-item-action" :class="{active: tab === 'history'}" @click.prevent="tab = 'history'">
                        <i class="fa fa-history me-2"></i>Riwayat Login
                    </a>
                </div>
            </div>

            <!-- Content -->
            <div class="col-md-9">
                <!-- Profile Tab -->
                <div v-show="tab === 'profile'" class="card border-0 shadow">
                    <div class="card-header bg-white"><h5 class="mb-0">Edit Profil</h5></div>
                    <div class="card-body">
                        <form @submit.prevent="updateProfile">
                            <div class="mb-3">
                                <label class="form-label">Nama</label>
                                <input v-model="profileForm.name" type="text" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input v-model="profileForm.email" type="email" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary" :disabled="profileForm.processing">
                                <i class="fa fa-save me-1"></i>Simpan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Password Tab -->
                <div v-show="tab === 'password'" class="card border-0 shadow">
                    <div class="card-header bg-white"><h5 class="mb-0">Ubah Password</h5></div>
                    <div class="card-body">
                        <form @submit.prevent="updatePassword">
                            <div class="mb-3">
                                <label class="form-label">Password Saat Ini</label>
                                <input v-model="passwordForm.current_password" type="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input v-model="passwordForm.password" type="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password</label>
                                <input v-model="passwordForm.password_confirmation" type="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary" :disabled="passwordForm.processing">
                                <i class="fa fa-key me-1"></i>Ubah Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 2FA Tab -->
                <div v-show="tab === '2fa'" class="card border-0 shadow">
                    <div class="card-header bg-white"><h5 class="mb-0">Two-Factor Authentication</h5></div>
                    <div class="card-body">
                        <div v-if="twoFactorEnabled" class="text-center">
                            <i class="fa fa-check-circle text-success fa-3x mb-3"></i>
                            <h5 class="text-success">2FA Aktif</h5>
                            <div class="mt-4" v-if="recoveryCodes">
                                <h6>Recovery Codes</h6>
                                <div class="bg-light p-3 rounded mb-3">
                                    <code v-for="code in recoveryCodes" :key="code" class="d-block">{{ code }}</code>
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-center">
                                <button @click="regenerateCodes" class="btn btn-outline-warning btn-sm">Regenerate Codes</button>
                                <button @click="showDisable = true" class="btn btn-outline-danger btn-sm">Nonaktifkan</button>
                            </div>
                        </div>
                        <div v-else class="text-center">
                            <i class="fa fa-shield-alt text-warning fa-3x mb-3"></i>
                            <h5>2FA Belum Aktif</h5>
                            <p class="text-muted">Aktifkan untuk keamanan tambahan</p>
                            <button @click="setup2FA" class="btn btn-primary">Aktifkan 2FA</button>
                        </div>

                        <!-- Setup Modal -->
                        <div v-if="showSetup" class="mt-4 p-3 border rounded">
                            <h6>Scan QR Code:</h6>
                            <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(qrUrl)" class="mb-3">
                            <p class="small">Secret: <code>{{ secret }}</code></p>
                            <form @submit.prevent="enable2FA">
                                <input v-model="code2fa" type="text" class="form-control mb-2" placeholder="Kode 6 digit" maxlength="6">
                                <button type="submit" class="btn btn-success">Verifikasi</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Login History Tab -->
                <div v-show="tab === 'history'" class="card border-0 shadow">
                    <div class="card-header bg-white"><h5 class="mb-0">Riwayat Login (10 Terakhir)</h5></div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Waktu</th><th>Status</th><th>Device</th><th>IP</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="h in loginHistory" :key="h.id">
                                    <td><small>{{ formatDate(h.created_at) }}</small></td>
                                    <td><span :class="h.status === 'success' ? 'badge bg-success' : 'badge bg-danger'">{{ h.status }}</span></td>
                                    <td>{{ h.device }} / {{ h.browser }}</td>
                                    <td><code>{{ h.ip_address }}</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disable 2FA Modal -->
        <div v-if="showDisable" class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header"><h5>Nonaktifkan 2FA</h5></div>
                    <form @submit.prevent="disable2FA">
                        <div class="modal-body">
                            <input v-model="disablePassword" type="password" class="form-control" placeholder="Password" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" @click="showDisable = false" class="btn btn-secondary">Batal</button>
                            <button type="submit" class="btn btn-danger">Nonaktifkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';
import axios from 'axios';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    user: Object,
    twoFactorEnabled: Boolean,
    recoveryCodes: Array,
    loginHistory: Array,
});

const tab = ref('profile');
const showSetup = ref(false);
const showDisable = ref(false);
const secret = ref('');
const qrUrl = ref('');
const code2fa = ref('');
const disablePassword = ref('');

const avatarUrl = computed(() => {
    if (props.user.photo) return `/storage/${props.user.photo}`;
    return `https://ui-avatars.com/api/?name=${props.user.name}&background=4e73df&color=ffffff&size=120`;
});

const profileForm = useForm({ name: props.user.name, email: props.user.email });
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });

const updateProfile = () => profileForm.put(route('admin.profile.update'));
const updatePassword = () => passwordForm.put(route('admin.profile.password'), { onSuccess: () => passwordForm.reset() });

const uploadPhoto = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const form = useForm({ photo: file });
    form.post(route('admin.profile.photo'), { forceFormData: true });
};

const setup2FA = async () => {
    const { data } = await axios.get(route('admin.profile.2fa.setup'));
    secret.value = data.secret;
    qrUrl.value = data.qrCodeUrl;
    showSetup.value = true;
};

const enable2FA = () => {
    router.post(route('admin.profile.2fa.enable'), { code: code2fa.value }, {
        onSuccess: () => { showSetup.value = false; code2fa.value = ''; }
    });
};

const disable2FA = () => {
    router.post(route('admin.profile.2fa.disable'), { password: disablePassword.value }, {
        onSuccess: () => { showDisable.value = false; disablePassword.value = ''; }
    });
};

const regenerateCodes = () => {
    const pwd = prompt('Masukkan password:');
    if (pwd) router.post(route('admin.profile.2fa.regenerate'), { password: pwd });
};

const formatDate = (d) => new Date(d).toLocaleString('id-ID');
</script>
