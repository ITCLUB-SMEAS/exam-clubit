<template>
    <Head>
        <title>Monitor Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fa fa-tv me-2"></i>Monitor Ujian Real-time</h5>
                    </div>
                    <div class="card-body">
                        <div v-if="activeSessions.length === 0" class="text-center py-5 text-muted">
                            <i class="fa fa-clock fa-3x mb-3"></i>
                            <p>Tidak ada sesi ujian yang sedang berlangsung.</p>
                        </div>
                        <div v-else class="row">
                            <div v-for="session in activeSessions" :key="session.id" class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">{{ session.exam.title }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><i class="fa fa-book me-2"></i>{{ session.exam.lesson?.name }}</p>
                                        <p class="mb-1"><i class="fa fa-users me-2"></i>{{ session.exam.classroom?.name }}</p>
                                        <p class="mb-1"><i class="fa fa-clock me-2"></i>{{ formatTime(session.start_time) }} - {{ formatTime(session.end_time) }}</p>
                                        <div class="mt-3">
                                            <span class="badge bg-success me-1"><i class="fa fa-circle"></i> Live</span>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <Link :href="route('admin.monitor.show', session.id)" class="btn btn-primary btn-sm w-100">
                                            <i class="fa fa-eye me-1"></i>Monitor
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';

defineOptions({ layout: LayoutAdmin });

const props = defineProps({
    activeSessions: Array,
});

const formatTime = (datetime) => {
    return new Date(datetime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};
</script>
