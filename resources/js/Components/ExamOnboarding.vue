<template>
    <div v-if="show" class="modal fade show" style="display: block; background: rgba(0,0,0,0.8); z-index: 2000;" role="dialog" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Panduan Ujian</h5>
                    <button type="button" class="btn-close" @click="skip" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3 text-primary">
                        <i :class="steps[currentStep].icon" class="fa-4x"></i>
                    </div>
                    <h5 class="fw-bold">{{ steps[currentStep].title }}</h5>
                    <p class="text-muted">{{ steps[currentStep].desc }}</p>
                    
                    <div class="d-flex justify-content-center mt-4">
                        <span v-for="(step, index) in steps" :key="index" 
                              class="mx-1 rounded-circle"
                              :class="index === currentStep ? 'bg-primary' : 'bg-gray-300'"
                              style="width: 10px; height: 10px; display: inline-block;">
                        </span>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button class="btn btn-link text-muted" @click="skip">Lewati</button>
                    <div>
                        <button class="btn btn-secondary me-2" v-if="currentStep > 0" @click="prev">Kembali</button>
                        <button class="btn btn-primary" v-if="currentStep < steps.length - 1" @click="next">Lanjut</button>
                        <button class="btn btn-success" v-else @click="finish">Mulai Ujian</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';

export default {
    name: 'ExamOnboarding',
    props: {
        examId: {
            type: [String, Number],
            required: true
        }
    },
    setup(props) {
        const show = ref(false);
        const currentStep = ref(0);
        
        const steps = [
            {
                title: 'Selamat Datang',
                desc: 'Sebelum memulai, mari pelajari antarmuka ujian ini agar Anda lancar mengerjakan.',
                icon: 'fas fa-door-open'
            },
            {
                title: 'Waktu Ujian',
                desc: 'Perhatikan timer di pojok kanan atas. Terdapat progress bar yang menunjukkan sisa waktu Anda.',
                icon: 'fas fa-clock'
            },
            {
                title: 'Navigasi Soal',
                desc: 'Gunakan panel nomor di sebelah kanan (atau bawah di mobile) untuk berpindah antar soal.',
                icon: 'fas fa-th'
            },
            {
                title: 'Menjawab Soal',
                desc: 'Jawaban Anda tersimpan otomatis. Untuk Essay/Isian, perhatikan indikator "Tersimpan".',
                icon: 'fas fa-pen-alt'
            },
            {
                title: 'Peringatan & Anti-Cheat',
                desc: 'Jangan berpindah tab atau keluar dari fullscreen untuk menghindari sanksi pelanggaran.',
                icon: 'fas fa-exclamation-triangle'
            },
            {
                title: 'Siap Mengerjakan?',
                desc: 'Klik tombol ini untuk memulai. Semoga sukses!',
                icon: 'fas fa-flag-checkered'
            }
        ];

        onMounted(() => {
            // Show only if not seen globally (version-based for future updates)
            const hasSeenGlobal = localStorage.getItem('onboarding_seen_global_v1');
            
            if (!hasSeenGlobal) {
                show.value = true;
            }
        });

        const next = () => {
            if (currentStep.value < steps.length - 1) {
                currentStep.value++;
            }
        };

        const prev = () => {
            if (currentStep.value > 0) {
                currentStep.value--;
            }
        };

        const skip = () => {
            finish();
        };

        const finish = () => {
            show.value = false;
            localStorage.setItem('onboarding_seen_global_v1', 'true');
        };

        return {
            show,
            currentStep,
            steps,
            next,
            prev,
            skip,
            finish
        };
    }
}
</script>
