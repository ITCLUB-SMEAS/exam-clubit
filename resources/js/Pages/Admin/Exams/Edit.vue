<template>
    <Head>
        <title>Edit Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link href="/admin/exams" class="btn btn-md btn-primary border-0 shadow mb-3" type="button"><i class="fas fa-long-arrow-alt-left me-2"></i> Kembali</Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-edit"></i> Edit Ujian</h5>
                        <hr>
                        <form @submit.prevent="submit">

                            <div class="mb-4">
                                <label>Nama Ujian</label>
                                <input type="text" class="form-control" placeholder="Masukkan Nama Ujian" v-model="form.title">
                                <div v-if="errors.title" class="alert alert-danger mt-2">
                                    {{ errors.title }}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Mata Pelajaran</label>
                                        <select class="form-select" v-model="form.lesson_id">
                                            <option v-for="(lesson, index) in lessons" :key="index" :value="lesson.id">{{ lesson.title }}</option>
                                        </select>
                                        <div v-if="errors.lesson_id" class="alert alert-danger mt-2">
                                            {{ errors.lesson_id }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Kelas</label>
                                        <select class="form-select" v-model="form.classroom_id">
                                            <option v-for="(classroom, index) in classrooms" :key="index" :value="classroom.id">{{ classroom.title }}</option>
                                        </select>
                                        <div v-if="errors.classroom_id" class="alert alert-danger mt-2">
                                            {{ errors.classroom_id }}
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="mb-4">
                                <label>Deskripsi</label>
                                <TiptapEditor v-model="form.description" :height="200" />
                                <div v-if="errors.description" class="alert alert-danger mt-2">
                                    {{ errors.description }}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Acak Soal</label>
                                        <select class="form-select" v-model="form.random_question">
                                            <option value="Y">Y</option>
                                            <option value="N">N</option>
                                        </select>
                                        <div v-if="errors.random_question" class="alert alert-danger mt-2">
                                            {{ errors.random_question }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Acak Jawaban</label>
                                        <select class="form-select" v-model="form.random_answer">
                                            <option value="Y">Y</option>
                                            <option value="N">N</option>
                                        </select>
                                        <div v-if="errors.random_answer" class="alert alert-danger mt-2">
                                            {{ errors.random_answer }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Tampilkan Hasil</label>
                                        <select class="form-select" v-model="form.show_answer">
                                            <option value="Y">Y</option>
                                            <option value="N">N</option>
                                        </select>
                                        <div v-if="errors.show_answer" class="alert alert-danger mt-2">
                                            {{ errors.show_answer }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Durasi (Menit)</label>
                                        <input type="number" min="1" class="form-control" placeholder="Masukkan Durasi Ujian (Menit)" v-model="form.duration">
                                        <div v-if="errors.duration" class="alert alert-danger mt-2">
                                            {{ errors.duration }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Advanced Features -->
                            <hr>
                            <h6 class="mb-3"><i class="fas fa-cog"></i> Pengaturan Lanjutan</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <label>KKM / Passing Grade</label>
                                        <input type="number" min="0" max="100" step="0.1" class="form-control" placeholder="0 = tidak ada" v-model="form.passing_grade">
                                        <small class="text-muted">Nilai minimum kelulusan (0-100)</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <label>Maks. Percobaan</label>
                                        <input type="number" min="1" class="form-control" placeholder="1" v-model="form.max_attempts">
                                        <small class="text-muted">Untuk remedial (default: 1)</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <label>Batasi Jumlah Soal</label>
                                        <input type="number" min="1" class="form-control" placeholder="Kosong = semua" v-model="form.question_limit">
                                        <small class="text-muted">Kosongkan untuk semua soal</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <label>Waktu per Soal (detik)</label>
                                        <input type="number" min="1" class="form-control" placeholder="Kosong = tidak ada" v-model="form.time_per_question">
                                        <small class="text-muted">Kosongkan jika tidak perlu</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Scoring Options -->
                            <hr>
                            <h6 class="mb-3"><i class="fas fa-calculator"></i> Pengaturan Penilaian</h6>
                            
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="partialCredit" v-model="form.enable_partial_credit">
                                    <label class="form-check-label" for="partialCredit">
                                        <i class="fas fa-percent me-1"></i> Aktifkan Partial Credit
                                    </label>
                                </div>
                                <small class="text-muted">Untuk soal pilihan ganda multiple: jawaban sebagian benar dapat poin proporsional (misal: 2/3 benar = 66% poin)</small>
                            </div>

                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="negativeMarking" v-model="form.enable_negative_marking">
                                    <label class="form-check-label" for="negativeMarking">
                                        <i class="fas fa-minus-circle me-1"></i> Aktifkan Negative Marking
                                    </label>
                                </div>
                                <small class="text-muted">Jawaban salah akan mengurangi poin (cocok untuk ujian kompetitif seperti SBMPTN/UTBK)</small>
                            </div>

                            <div class="mb-4" v-if="form.enable_negative_marking">
                                <label>Persentase Pengurangan Poin (%)</label>
                                <input type="number" min="0" max="100" step="0.01" class="form-control" placeholder="25" v-model="form.negative_marking_percentage">
                                <small class="text-muted">Contoh: 25% berarti jawaban salah dikurangi 25% dari poin soal (benar +4, salah -1)</small>
                            </div>

                            <!-- Anti-Cheat Info Banner -->
                            <hr>
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-shield-alt me-2"></i>
                                <strong>Anti-Cheat Otomatis Aktif:</strong> Deteksi pindah tab, fullscreen, copy-paste, multiple monitor, dan virtual machine otomatis aktif.
                            </div>

                            <!-- Face Detection Option -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="faceDetection" v-model="form.face_detection_enabled">
                                    <label class="form-check-label" for="faceDetection">
                                        <i class="fas fa-video me-1"></i> Aktifkan Face Detection
                                    </label>
                                </div>
                                <small class="text-muted">Deteksi wajah siswa via webcam (memerlukan izin kamera)</small>
                            </div>

                            <!-- Audio Detection Option -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="audioDetection" v-model="form.audio_detection_enabled">
                                    <label class="form-check-label" for="audioDetection">
                                        <i class="fas fa-microphone me-1"></i> Aktifkan Audio Detection
                                    </label>
                                </div>
                                <small class="text-muted">Deteksi suara mencurigakan via mikrofon (memerlukan izin mikrofon)</small>
                            </div>

                            <button type="submit" class="btn btn-md btn-primary border-0 shadow me-2">Update</button>
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
    import { reactive } from 'vue';

    //import sweet alert2
    import Swal from 'sweetalert2';

    //import Tiptap
    import TiptapEditor from '../../../Components/TiptapEditor.vue';

    export default {

        //layout
        layout: LayoutAdmin,

        //register components
        components: {
            Head,
            Link,
            TiptapEditor
        },

        //props
        props: {
            errors: Object,
            exam: Object,
            lessons: Array,
            classrooms: Array,
        },

        //inisialisasi composition API
        setup(props) {

            //define form with reactive
            const form = reactive({
                title: props.exam.title,
                lesson_id: props.exam.lesson_id,
                classroom_id: props.exam.classroom_id,
                duration: props.exam.duration,
                description: props.exam.description,
                random_question: props.exam.random_question,
                random_answer: props.exam.random_answer,
                show_answer: props.exam.show_answer,
                passing_grade: props.exam.passing_grade || 0,
                max_attempts: props.exam.max_attempts || 1,
                question_limit: props.exam.question_limit || '',
                time_per_question: props.exam.time_per_question || '',
                enable_partial_credit: props.exam.enable_partial_credit || false,
                enable_negative_marking: props.exam.enable_negative_marking || false,
                negative_marking_percentage: props.exam.negative_marking_percentage || 25,
                face_detection_enabled: props.exam.face_detection_enabled || false,
                audio_detection_enabled: props.exam.audio_detection_enabled || false,
            });

            //method "submit"
            const submit = () => {

                //send data to server
                router.put(`/admin/exams/${props.exam.id}`, {
                    //data
                    title: form.title,
                    lesson_id: form.lesson_id,
                    classroom_id: form.classroom_id,
                    duration: form.duration,
                    description: form.description,
                    random_question: form.random_question,
                    random_answer: form.random_answer,
                    show_answer: form.show_answer,
                    passing_grade: form.passing_grade || 0,
                    max_attempts: form.max_attempts || 1,
                    question_limit: form.question_limit || null,
                    time_per_question: form.time_per_question || null,
                    enable_partial_credit: form.enable_partial_credit,
                    enable_negative_marking: form.enable_negative_marking,
                    negative_marking_percentage: form.negative_marking_percentage || 25,
                    face_detection_enabled: form.face_detection_enabled,
                    audio_detection_enabled: form.audio_detection_enabled,
                }, {
                    onSuccess: () => {
                        //show success alert
                        Swal.fire({
                            title: 'Success!',
                            text: 'Ujian Berhasil Diupdate!.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    },
                });

            }

            return {
                form,
                submit,
            };

        }

    }

</script>

<style>

</style>
