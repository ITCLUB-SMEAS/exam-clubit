<template>
    <Head>
        <title>Edit Kategori - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="form-label">Nama Kategori</label>
                                <input type="text" class="form-control" v-model="form.name" :class="{'is-invalid': errors.name}">
                                <div class="invalid-feedback">{{ errors.name }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mata Pelajaran (Opsional)</label>
                                <select class="form-select" v-model="form.lesson_id">
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                    <option v-for="lesson in lessons" :key="lesson.id" :value="lesson.id">{{ lesson.title }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Deskripsi (Opsional)</label>
                                <textarea class="form-control" v-model="form.description" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <i class="fa fa-save"></i> Update
                            </button>
                            <Link href="/admin/question-categories" class="btn btn-secondary ms-2">Kembali</Link>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: { category: Object, lessons: Array, errors: Object },
    setup(props) {
        const form = useForm({
            name: props.category.name,
            description: props.category.description || '',
            lesson_id: props.category.lesson_id || ''
        });
        const submit = () => form.put(`/admin/question-categories/${props.category.id}`);
        return { form, submit };
    }
}
</script>
