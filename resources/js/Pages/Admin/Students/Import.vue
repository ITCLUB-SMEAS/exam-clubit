<template>
    <Head>
        <title>Import Siswa - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link href="/admin/students" class="btn btn-md btn-primary border-0 shadow mb-3 me-3" type="button"><i
                    class="fas fa-long-arrow-alt-left me-2"></i> Kembali</Link>
                <a href="/admin/students/template"
                    class="btn btn-md btn-success border-0 shadow mb-3 text-white" type="button"><i
                        class="fas fa-file-excel me-2"></i> Download Template</a>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-user"></i> Import Siswa</h5>
                        <hr>

                        <!-- Info Box -->
                        <div class="alert alert-info mb-4">
                            <h6><i class="fas fa-info-circle me-2"></i>Format Kolom Excel</h6>
                            <ul class="mb-0 small">
                                <li><strong>nisn</strong> - NISN siswa (wajib)</li>
                                <li><strong>name</strong> - Nama lengkap siswa (wajib)</li>
                                <li><strong>password</strong> - Password (default: 123456)</li>
                                <li><strong>gender</strong> - Jenis kelamin: L/P (default: L)</li>
                                <li><strong>classroom_id</strong> - ID Kelas (default: 1)</li>
                                <li><strong>room_id</strong> - ID Ruangan (kosong/auto = otomatis)</li>
                                <li><strong>photo_url</strong> - <span class="badge bg-success">BARU!</span> URL foto siswa (opsional, akan didownload otomatis)</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submit">

                            <div class="mb-4">
                                <label>File Excel (.xlsx, .xls)</label>
                                <input type="file" class="form-control" accept=".xlsx,.xls" @input="form.file = $event.target.files[0]">
                                <div v-if="errors.file" class="alert alert-danger mt-2">
                                    {{ errors.file }}
                                </div>
                                <div v-if="errors[0]" class="alert alert-danger mt-2">
                                    {{ errors[0] }}
                                </div>
                            </div>

                            <button type="submit" class="btn btn-md btn-primary border-0 shadow me-2">Upload</button>
                            <button type="reset" class="btn btn-md btn-warning border-0 shadow">Reset</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    //import layout
    import LayoutAdmin from '../../../Layouts/Admin.vue';

    //import Heade and Link from Inertia
    import {
        Head,
        Link,
        router
    } from '@inertiajs/vue3';

    //import reactive from vue
    import {
        reactive
    } from 'vue';

    //import sweet alert2
    import Swal from 'sweetalert2';

    export default {

        //layout
        layout: LayoutAdmin,

        //register components
        components: {
            Head,
            Link
        },

        //props
        props: {
            errors: Object,
        },

        //inisialisasi composition API
        setup() {

            //define form with reactive
            const form = reactive({
                file: '',
            });

            //method "submit"
            const submit = () => {

                //send data to server
                router.post('/admin/students/import', {
                    //data
                    file: form.file,
                }, {
                    onSuccess: () => {
                        //show success alert
                        Swal.fire({
                            title: 'Success!',
                            text: 'Import Siswa Berhasil Disimpan!.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    },
                });
            }

            //return
            return {
                form,
                submit
            };

        }

    }

</script>

<style>

</style>