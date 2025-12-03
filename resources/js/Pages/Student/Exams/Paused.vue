<template>
    <Head><title>Ujian Di-Pause</title></Head>
    <div class="row justify-content-center mb-5 mt-5">
        <div class="col-md-6">
            <div class="card border-0 shadow text-center">
                <div class="card-body py-5">
                    <i class="fa fa-pause-circle text-warning" style="font-size: 80px;"></i>
                    <h3 class="mt-4">Ujian Di-Pause</h3>
                    <p class="text-muted mb-4">
                        Ujian Anda sedang di-pause oleh pengawas.<br>
                        Silakan tunggu hingga ujian dilanjutkan.
                    </p>
                    <div class="alert alert-warning" v-if="pause_reason">
                        <strong>Alasan:</strong> {{ pause_reason }}
                    </div>
                    <div class="mt-4">
                        <p class="text-muted small">
                            <i class="fa fa-info-circle me-1"></i>
                            Halaman akan otomatis refresh setiap 10 detik.
                        </p>
                        <button @click="checkStatus" class="btn btn-primary">
                            <i class="fa fa-sync me-1"></i> Cek Status
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutStudent from '../../../Layouts/Student.vue';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

export default {
    layout: LayoutStudent,
    components: { Head },
    props: {
        exam_group: Object,
        grade: Object,
        pause_reason: String,
    },
    setup(props) {
        let interval;

        const checkStatus = () => {
            router.reload();
        };

        onMounted(() => {
            interval = setInterval(checkStatus, 10000);
        });

        onUnmounted(() => {
            clearInterval(interval);
        });

        return { checkStatus };
    }
};
</script>
