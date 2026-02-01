<template>
    <Head>
        <title>Laporan Nilai Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fas fa-filter"></i> Filter Nilai Ujian</h5>
                        <hr>
                        <form @submit.prevent="filter">
                            
                            <div class="row">
                                <div class="col-md-9">
                                    <label class="control-label" for="name">Ujian</label>
                                    <select class="form-select" v-model="form.exam_id">
                                        <option v-for="(exam, index) in exams" :key="index" :value="exam.id">{{ exam.title }} — Kelas : {{ exam.classroom?.title || '-' }} — Pelajaran : {{ exam.lesson?.title || '-' }}</option>
                                    </select>
                                    <div v-if="errors.exam_id" class="alert alert-danger mt-2">
                                        {{ errors.exam_id }}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold text-white">*</label>
                                    <button type="submit" class="btn btn-md btn-primary border-0 shadow w-100"> <i class="fas fa-filter"></i> Filter</button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>

                <div v-if="gradesData.length > 0" class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-9 col-12">
                                <h5 class="mt-2"><i class="fas fa-chart-line"></i> Laporan Nilai Ujian</h5>
                            </div>
                            <div class="col-md-3 col-12">
                                <a :href="`/admin/reports/export?exam_id=${form.exam_id}`" target="_blank" class="btn btn-success btn-md border-0 shadow w-100 text-white"><i class="fas fa-file-excel"></i> DOWNLOAD EXCEL</a>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="border-0 rounded-start" style="width:5%">No.</th>
                                        <th class="border-0">Ujian</th>
                                        <th class="border-0">Sesi</th>
                                        <th class="border-0">Nama Siswa</th>
                                        <th class="border-0">Kelas</th>
                                        <th class="border-0">Pelajaran</th>
                                        <th class="border-0">Nilai</th>
                                    </tr>
                                </thead>
                                <div class="mt-2"></div>
                                <tbody>
                                    <tr v-for="(grade, index) in gradesData" :key="grade.id">
                                        <td class="fw-bold text-center">
                                            {{ startIndex + index + 1 }}
                                        </td>
                                        <td>{{ grade.exam?.title || '-' }}</td>
                                        <td>{{ grade.exam_session?.title || '-' }}</td>
                                        <td>{{ grade.student?.name || '-' }}</td>
                                        <td class="text-center">{{ grade.student?.classroom?.title || '-' }}</td>
                                        <td>{{ grade.exam?.lesson?.title || '-' }}</td>
                                        <td class="fw-bold text-center">{{ grade.grade }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <Pagination v-if="grades.links" :links="grades.links" align="end" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    //import layout Admin
    import LayoutAdmin from '../../../Layouts/Admin.vue';
    
    //import Pagination component
    import Pagination from '../../../Components/Pagination.vue';

    //import Head from Inertia
    import {
        Head,
        router
    } from '@inertiajs/vue3';

    //import reactive and computed from vue
    import { reactive, computed } from 'vue';

    export default {

        //layout
        layout: LayoutAdmin,

        //register components
        components: {
            Head,
            Pagination,
        },

        //props
        props: {
            errors: Object,
            exams: Array,
            grades: {
                type: [Array, Object],
                default: () => []
            },
        },

        //inisialisasi composition API
        setup(props) {

            //define state
            const form = reactive({
                'exam_id': '' || (new URL(document.location)).searchParams.get('exam_id'),
            });

            // Computed property to handle both array and paginated object
            const gradesData = computed(() => {
                if (Array.isArray(props.grades)) {
                    return props.grades;
                }
                return props.grades.data || [];
            });

            // Computed property for pagination start index
            const startIndex = computed(() => {
                if (props.grades && props.grades.from) {
                    return props.grades.from - 1;
                }
                return 0;
            });

             //define methods filter
            const filter = () => {

                //HTTP request
                router.get('/admin/reports/filter', {

                    //send data to server
                    exam_id: form.exam_id,
                });

            }

            //return
            return {
                form,
                filter,
                gradesData,
                startIndex,
            }

        }

    }

</script>

<style>

</style>