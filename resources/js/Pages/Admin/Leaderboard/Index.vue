<template>
    <Head><title>Leaderboard - Admin</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="card border-0 shadow">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa fa-trophy text-warning me-2"></i>Leaderboard / Ranking</h5>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Ujian</label>
                        <select class="form-select" v-model="form.exam_id" @change="filter">
                            <option value="">-- Pilih Ujian --</option>
                            <option v-for="e in exams" :key="e.id" :value="e.id">{{ e.title }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sesi</label>
                        <select class="form-select" v-model="form.session_id" @change="filter">
                            <option value="">Semua Sesi</option>
                            <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.title }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kelas</label>
                        <select class="form-select" v-model="form.classroom_id" @change="filter">
                            <option value="">Semua Kelas</option>
                            <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.title }}</option>
                        </select>
                    </div>
                </div>

                <!-- Leaderboard Table -->
                <div class="table-responsive" v-if="leaderboard.length > 0">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width:60px">Rank</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th class="text-center">Nilai</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Durasi</th>
                                <th class="text-center">Attempt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in leaderboard" :key="item.rank" :class="getRankClass(item.rank)">
                                <td class="text-center fw-bold">
                                    <span v-if="item.rank === 1">🥇</span>
                                    <span v-else-if="item.rank === 2">🥈</span>
                                    <span v-else-if="item.rank === 3">🥉</span>
                                    <span v-else>{{ item.rank }}</span>
                                </td>
                                <td>{{ item.student_name }}</td>
                                <td>{{ item.classroom }}</td>
                                <td class="text-center fw-bold">{{ item.grade }}</td>
                                <td class="text-center">
                                    <span :class="item.status === 'passed' ? 'badge bg-success' : 'badge bg-danger'">
                                        {{ item.status === 'passed' ? 'Lulus' : 'Tidak Lulus' }}
                                    </span>
                                </td>
                                <td class="text-center">{{ item.duration }}</td>
                                <td class="text-center">{{ item.attempt }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else-if="form.exam_id" class="alert alert-info">
                    Belum ada data untuk ditampilkan.
                </div>
                <div v-else class="alert alert-secondary">
                    Pilih ujian untuk melihat leaderboard.
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head },
    props: {
        exams: Array,
        sessions: Array,
        classrooms: Array,
        leaderboard: Array,
        filters: Object,
    },
    setup(props) {
        const form = reactive({
            exam_id: props.filters?.exam_id || '',
            session_id: props.filters?.session_id || '',
            classroom_id: props.filters?.classroom_id || '',
        });

        const filter = () => {
            router.get('/admin/leaderboard', form, { preserveState: true });
        };

        const getRankClass = (rank) => {
            if (rank === 1) return 'table-warning';
            if (rank <= 3) return 'table-light';
            return '';
        };

        return { form, filter, getRankClass };
    }
};
</script>
