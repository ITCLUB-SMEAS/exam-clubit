<template>
    <Head>
        <title>Notifikasi - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Notifikasi</h5>
                        <button v-if="notifications.data.length > 0" @click="deleteAll" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash me-1"></i> Hapus Semua
                        </button>
                    </div>
                    <div class="card-body">
                        <div v-if="notifications.data.length === 0" class="text-center py-5 text-muted">
                            <i class="fas fa-bell-slash fa-3x mb-3"></i>
                            <p>Tidak ada notifikasi</p>
                        </div>
                        
                        <div v-else class="list-group list-group-flush">
                            <div v-for="notif in notifications.data" :key="notif.id" 
                                 class="list-group-item d-flex justify-content-between align-items-start"
                                 :class="{ 'bg-light': !notif.read_at }">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i :class="['fa', notif.data.icon, `text-${notif.data.color}`, 'fa-lg']"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ notif.data.title }}</div>
                                        <div class="text-muted">{{ notif.data.message }}</div>
                                        <small class="text-muted">{{ formatDate(notif.created_at) }}</small>
                                    </div>
                                </div>
                                <button @click="deleteNotif(notif.id)" class="btn btn-sm btn-link text-danger">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <Pagination v-if="notifications.data.length > 0" :links="notifications.links" align="end" class="mt-3" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import Swal from 'sweetalert2';

defineOptions({ layout: LayoutAdmin });

defineProps({
    notifications: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const deleteNotif = (id) => {
    router.delete(`/admin/notifications/${id}`);
};

const deleteAll = () => {
    Swal.fire({
        title: 'Hapus Semua Notifikasi?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete('/admin/notifications');
        }
    });
};
</script>
