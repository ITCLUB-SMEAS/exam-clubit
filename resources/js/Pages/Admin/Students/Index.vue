<template>
    <Head>
        <title>Siswa - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="row mb-2">
                    <div class="col-6 col-md-2 mb-2">
                        <Link href="/admin/students/create" class="btn btn-md btn-primary border-0 shadow w-100"
                            type="button"><i class="fas fa-plus-circle me-1"></i>Tambah</Link>
                    </div>
                    <div class="col-6 col-md-2 mb-2">
                        <Link href="/admin/students/import" class="btn btn-md btn-success border-0 shadow w-100 text-white"
                            type="button"><i class="fas fa-file-excel me-1"></i>Import</Link>
                    </div>
                    <div class="col-6 col-md-2 mb-2">
                        <Link href="/admin/students-bulk-password-reset" class="btn btn-md btn-warning border-0 shadow w-100"
                            type="button"><i class="fas fa-key me-1"></i>Reset PW</Link>
                    </div>
                    <div class="col-12 col-md-4 mb-2">
                        <form @submit.prevent="handleSearch">
                            <div class="input-group">
                                <input type="text" class="form-control border-0 shadow" v-model="search" placeholder="Cari siswa...">
                                <span class="input-group-text border-0 shadow">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-1">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">

                        <div class="table-responsive">
                            <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="border-0 rounded-start" style="width:5%">No.</th>
                                        <th class="border-0">Nisn</th>
                                        <th class="border-0">Nama</th>
                                        <th class="border-0">Kelas</th>
                                        <th class="border-0">Jenis Kelamin</th>
                                        <th class="border-0">Status</th>
                                        <th class="border-0 rounded-end" style="width:18%">Aksi</th>
                                    </tr>
                                </thead>
                                <div class="mt-2"></div>
                                <tbody>
                                    <tr v-for="(student, index) in students.data" :key="index">
                                        <td class="fw-bold text-center">
                                            {{ ++index + (students.current_page - 1) * students.per_page }}</td>
                                        <td>{{ student.nisn }}</td>
                                        <td>{{ student.name }}</td>
                                        <td class="text-center">{{ student.classroom.title }}</td>
                                        <td class="text-center">{{ student.gender }}</td>
                                        <td class="text-center">
                                            <span v-if="student.is_blocked" class="badge bg-danger">Diblokir</span>
                                            <span v-else class="badge bg-success">Aktif</span>
                                        </td>
                                        <td class="text-center">
                                            <button @click.prevent="toggleBlock(student)" 
                                                :class="student.is_blocked ? 'btn btn-sm btn-success border-0 shadow me-2' : 'btn btn-sm btn-warning border-0 shadow me-2'" 
                                                type="button" :title="student.is_blocked ? 'Unblock' : 'Block'">
                                                <i :class="student.is_blocked ? 'fas fa-unlock' : 'fas fa-ban'"></i>
                                            </button>
                                            <Link :href="`/admin/students/${student.id}/edit`" class="btn btn-sm btn-info border-0 shadow me-2" type="button"><i class="fas fa-pencil-alt"></i></Link>
                                            <button @click.prevent="destroy(student.id)" class="btn btn-sm btn-danger border-0"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="students.links" align="end" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    //import layout
    import LayoutAdmin from '../../../Layouts/Admin.vue';

    //import component pagination
    import Pagination from '../../../Components/Pagination.vue';

    //import Heade and Link from Inertia
    import {
        Head,
        Link,
        router
    } from '@inertiajs/vue3';

    //import ref from vue
    import {
        ref
    } from 'vue';

    //import sweet alert2
    import Swal from 'sweetalert2';

    export default {
        //layout
        layout: LayoutAdmin,

        //register component
        components: {
            Head,
            Link,
            Pagination
        },

        //props
        props: {
            students: Object,
        },

        //inisialisasi composition API
        setup() {

            //define state search
            const search = ref('' || (new URL(document.location)).searchParams.get('q'));

            //define method search
            const handleSearch = () => {
                router.get('/admin/students', {

                    //send params "q" with value from state "search"
                    q: search.value,
                });
            }

            //define method destroy
            const destroy = (id) => {
                Swal.fire({
                        title: 'Apakah Anda yakin?',
                        text: "Anda tidak akan dapat mengembalikan ini!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    })
                    .then((result) => {
                        if (result.isConfirmed) {

                            router.delete(`/admin/students/${id}`);

                            Swal.fire({
                                title: 'Deleted!',
                                text: 'Siswa Berhasil Dihapus!.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                            });
                        }
                    })
            }

            //define method toggleBlock
            const toggleBlock = (student) => {
                const action = student.is_blocked ? 'unblock' : 'blokir';
                Swal.fire({
                        title: `${student.is_blocked ? 'Unblock' : 'Blokir'} siswa?`,
                        text: `Apakah Anda yakin ingin ${action} ${student.name}?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: student.is_blocked ? '#28a745' : '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: `Ya, ${action}!`
                    })
                    .then((result) => {
                        if (result.isConfirmed) {
                            router.post(`/admin/students/${student.id}/toggle-block`);
                            Swal.fire({
                                title: 'Berhasil!',
                                text: `Siswa berhasil di-${action}.`,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                            });
                        }
                    })
            }

            //return
            return {
                search,
                handleSearch,
                destroy,
                toggleBlock,
            }

        }
    }

</script>

<style>

</style>