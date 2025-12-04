<template>
    <nav v-if="$page.props.auth.student" class="navbar navbar-expand-lg navbar-transparent navbar-dark navbar-theme-primary mb-4 shadow">
        <div class="container position-relative">
            <Link class="navbar-brand me-lg-3" href="/student/dashboard">
                <span class="text-white fw-bold">Ujian Online</span>
            </Link>
            <button
                class="navbar-toggler"
                type="button"
                @click="toggleNavbar"
                aria-controls="navbarCollapse"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" :class="{ 'show': isNavbarOpen }" id="navbarCollapse">
                <ul class="navbar-nav me-auto mb-2 mb-md-0">
                </ul>
                <div class="d-flex align-items-center" v-if="$page.props.auth.student">
                    <!-- Profile Link -->
                    <Link href="/student/profile" class="btn btn-outline-light btn-sm me-2">
                        <i class="fa fa-user-circle me-1"></i>
                        <span class="d-none d-md-inline">{{ $page.props.auth.student.name }}</span>
                    </Link>
                    <!-- Logout Button -->
                    <button
                        @click="handleLogout"
                        class="btn btn-secondary shadow"
                        :disabled="isLoggingOut"
                    >
                        <span v-if="isLoggingOut">
                            <i class="fa fa-spinner fa-spin me-1"></i>
                            Logout...
                        </span>
                        <span v-else>
                            <i class="fa fa-sign-out-alt me-1"></i>
                            LOGOUT
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </nav>
    <div class="container">
        <slot />
    </div>

    <!-- Logout Confirmation Modal -->
    <div v-if="showLogoutModal" class="modal fade show" tabindex="-1" style="display: block;" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-sign-out-alt me-2"></i>
                        Konfirmasi Logout
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        @click="showLogoutModal = false"
                        :disabled="isLoggingOut"
                    ></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin keluar dari sistem?</p>
                    <div class="alert alert-warning mb-0">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        <small>Jika Anda sedang mengerjakan ujian, pastikan ujian sudah selesai sebelum logout.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        @click="showLogoutModal = false"
                        :disabled="isLoggingOut"
                    >
                        <i class="fa fa-times me-1"></i>
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn btn-danger"
                        @click="confirmLogout"
                        :disabled="isLoggingOut"
                    >
                        <span v-if="isLoggingOut">
                            <i class="fa fa-spinner fa-spin me-1"></i>
                            Memproses...
                        </span>
                        <span v-else>
                            <i class="fa fa-sign-out-alt me-1"></i>
                            Ya, Logout
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Backdrop -->
    <div v-if="showLogoutModal" class="modal-backdrop fade show"></div>
    
    <!-- PWA Install Prompt -->
    <PwaInstall />
</template>

<script>
    //import Link and router
    import { Link, router } from '@inertiajs/vue3';

    //import ref
    import { ref } from 'vue';
    
    //import PWA component
    import PwaInstall from '../Components/PwaInstall.vue';

    export default {
        //register components
        components: {
            Link,
            PwaInstall
        },

        setup() {
            //navbar toggle state
            const isNavbarOpen = ref(false);

            //logout modal state
            const showLogoutModal = ref(false);

            //logging out state
            const isLoggingOut = ref(false);

            //toggle navbar
            const toggleNavbar = () => {
                isNavbarOpen.value = !isNavbarOpen.value;
            };

            //handle logout click
            const handleLogout = () => {
                showLogoutModal.value = true;
            };

            //confirm logout
            const confirmLogout = () => {
                isLoggingOut.value = true;

                router.post('/student/logout', {}, {
                    onFinish: () => {
                        isLoggingOut.value = false;
                        showLogoutModal.value = false;
                    },
                    onError: () => {
                        // CSRF token expired (419), redirect to login
                        window.location.href = '/';
                    }
                });
            };

            return {
                isNavbarOpen,
                toggleNavbar,
                showLogoutModal,
                isLoggingOut,
                handleLogout,
                confirmLogout,
            };
        }
    }
</script>

<style scoped>
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1040;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal {
    z-index: 1050;
    background-color: rgba(0, 0, 0, 0.3);
}

.btn:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

/* Mobile navbar fix */
@media (max-width: 991.98px) {
    .navbar-collapse.show {
        background-color: var(--bs-primary, #1c2540);
        padding: 1rem;
        border-radius: 0.5rem;
        margin-top: 0.5rem;
    }
}
</style>
