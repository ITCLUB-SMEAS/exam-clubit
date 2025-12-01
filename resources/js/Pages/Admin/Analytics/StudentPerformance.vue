<template>
    <Head>
        <title>Performa Siswa - Analytics</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <Link href="/admin/analytics" class="btn btn-primary mb-3">
            <i class="fa fa-arrow-left"></i> Kembali
        </Link>

        <div class="card border-0 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fa fa-users"></i> Performa Siswa</h5>
                    <select class="form-select w-auto" v-model="selectedClass" @change="filterByClass">
                        <option value="">Semua Kelas</option>
                        <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.title }}</option>
                    </select>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Ujian</th>
                                <th>Rata-rata</th>
                                <th>Lulus</th>
                                <th>Tidak Lulus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(student, idx) in students" :key="student.id">
                                <td>{{ idx + 1 }}</td>
                                <td>{{ student.nisn }}</td>
                                <td>{{ student.name }}</td>
                                <td>{{ student.classroom }}</td>
                                <td>{{ student.exams_taken }}</td>
                                <td>
                                    <span :class="gradeClass(student.avg_grade)">{{ student.avg_grade }}</span>
                                </td>
                                <td><span class="badge bg-success">{{ student.passed }}</span></td>
                                <td><span class="badge bg-danger">{{ student.failed }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        students: Array,
        classrooms: Array,
        selectedClassroom: [String, Number],
    },
    setup(props) {
        const selectedClass = ref(props.selectedClassroom || '');

        const filterByClass = () => {
            router.get('/admin/analytics/students', {
                classroom_id: selectedClass.value || undefined
            });
        };

        const gradeClass = (grade) => {
            if (grade >= 80) return 'text-success fw-bold';
            if (grade >= 60) return 'text-warning fw-bold';
            return 'text-danger fw-bold';
        };

        return { selectedClass, filterByClass, gradeClass };
    }
}
</script>
