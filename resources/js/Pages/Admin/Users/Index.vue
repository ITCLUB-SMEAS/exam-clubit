<template>
    <Head>
        <title>Manajemen User - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row mb-3">
            <div class="col-md-2">
                <Link href="/admin/users/create" class="btn btn-primary w-100">
                    <i class="fas fa-plus-circle"></i> Tambah User
                </Link>
            </div>
            <div class="col-md-3">
                <select class="form-select" v-model="filters.role" @change="applyFilter">
                    <option value="">Semua Role</option>
                    <option value="admin">Admin</option>
                    <option value="guru">Guru</option>
                </select>
            </div>
            <div class="col-md-7">
                <form @submit.prevent="applyFilter">
                    <div class="input-group">
                        <input type="text" class="form-control" v-model="filters.q" placeholder="Cari nama...">
                        <button class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th style="width:5%">No.</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th style="width:15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(user, index) in users.data" :key="user.id">
                                <td class="text-center">{{ ++index + (users.current_page - 1) * users.per_page }}</td>
                                <td>{{ user.name }}</td>
                                <td>{{ user.email }}</td>
                                <td>
                                    <span class="badge" :class="user.role === 'admin' ? 'bg-danger' : 'bg-info'">
                                        {{ user.role === 'admin' ? 'Admin' : 'Guru' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <Link :href="`/admin/users/${user.id}/edit`" class="btn btn-sm btn-info me-1">
                                        <i class="fas fa-pencil-alt"></i>
                                    </Link>
                                    <button @click="destroy(user.id)" class="btn btn-sm btn-danger" :disabled="user.id === currentUserId">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination :links="users.links" align="end" />
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: { users: Object, filters: Object },
    setup(props) {
        const page = usePage();
        const currentUserId = page.props.auth?.user?.id;
        const filters = ref({ q: props.filters?.q || '', role: props.filters?.role || '' });

        const applyFilter = () => {
            router.get('/admin/users', {
                q: filters.value.q || undefined,
                role: filters.value.role || undefined,
            });
        };

        const destroy = (id) => {
            Swal.fire({
                title: 'Hapus user?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!'
            }).then((result) => {
                if (result.isConfirmed) router.delete(`/admin/users/${id}`);
            });
        };

        return { filters, applyFilter, destroy, currentUserId };
    }
}
</script>
