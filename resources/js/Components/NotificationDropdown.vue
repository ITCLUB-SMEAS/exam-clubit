<template>
    <div class="dropdown">
        <button class="btn btn-link nav-link dropdown-toggle position-relative" type="button" data-bs-toggle="dropdown">
            <i class="fa fa-bell"></i>
            <span v-if="unreadCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>
        <div class="dropdown-menu dropdown-menu-end shadow" style="width: 100%; min-width: 280px; max-width: 350px; max-height: 400px; overflow-y: auto;">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                <h6 class="mb-0">Notifikasi</h6>
                <button v-if="unreadCount > 0" @click="markAllAsRead" class="btn btn-link btn-sm p-0 text-decoration-none">
                    Tandai semua dibaca
                </button>
            </div>
            
            <div v-if="loading" class="text-center py-3">
                <div class="spinner-border spinner-border-sm" role="status"></div>
            </div>
            
            <div v-else-if="notifications.length === 0" class="text-center py-4 text-muted">
                <i class="fa fa-bell-slash fa-2x mb-2"></i>
                <p class="mb-0">Tidak ada notifikasi</p>
            </div>
            
            <div v-else>
                <a v-for="notif in notifications" :key="notif.id" 
                   href="#" @click.prevent="markAsRead(notif.id)"
                   class="dropdown-item py-2 border-bottom" 
                   :class="{ 'bg-light': !notif.read_at }">
                    <div class="d-flex">
                        <div class="me-2">
                            <i :class="['fa', notif.data.icon, `text-${notif.data.color}`]"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold small">{{ notif.data.title }}</div>
                            <div class="small text-muted text-wrap">{{ notif.data.message }}</div>
                            <div class="small text-muted">{{ notif.created_at }}</div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="text-center py-2 border-top">
                <Link href="/admin/notifications" class="text-decoration-none small">
                    Lihat semua notifikasi
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(true);
let pollInterval = null;

const fetchNotifications = async () => {
    try {
        const response = await axios.get('/admin/notifications/unread');
        notifications.value = response.data.notifications;
        unreadCount.value = response.data.count;
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    } finally {
        loading.value = false;
    }
};

const markAsRead = async (id) => {
    try {
        await axios.post('/admin/notifications/mark-read', { id });
        fetchNotifications();
    } catch (error) {
        console.error('Failed to mark as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/admin/notifications/mark-read');
        fetchNotifications();
    } catch (error) {
        console.error('Failed to mark all as read:', error);
    }
};

onMounted(() => {
    fetchNotifications();
    // Poll every 30 seconds
    pollInterval = setInterval(fetchNotifications, 30000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>
