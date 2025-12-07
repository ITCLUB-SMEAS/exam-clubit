<template>
    <Head>
        <title>Test Math Editor - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-square-root-alt"></i> Test Formula Editor</h5>
                        <hr>
                        
                        <div class="alert alert-info">
                            <strong>Testing Instructions:</strong>
                            <ul class="mb-0">
                                <li>Click the <strong>f(x)</strong> button to insert LaTeX formula</li>
                                <li>Use quick buttons for common symbols (fraction, sqrt, etc)</li>
                                <li>Type regular text and mix with formulas</li>
                                <li>Test examples below</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><strong>Editor:</strong></label>
                            <TiptapMathEditor v-model="content" :height="300" />
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><strong>HTML Output:</strong></label>
                            <pre class="bg-light p-3 border rounded"><code>{{ content }}</code></pre>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><strong>Rendered Preview:</strong></label>
                            <div class="border rounded p-3 bg-white" v-html="content"></div>
                        </div>

                        <hr>

                        <h6>Test Examples (Click to Load):</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="loadExample(1)">
                                Quadratic Formula
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="loadExample(2)">
                                Integral
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="loadExample(3)">
                                Matrix
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="loadExample(4)">
                                Physics
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="loadExample(5)">
                                Chemistry
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="content = ''">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import TiptapMathEditor from '../../Components/TiptapMathEditor.vue'
import LayoutAdmin from '../../Layouts/Admin.vue'

defineOptions({ layout: LayoutAdmin })

const content = ref('')

const loadExample = (num) => {
    const examples = {
        1: '<p>Rumus kuadrat untuk menyelesaikan persamaan <span data-type="mathematics" data-latex="ax^2 + bx + c = 0"></span> adalah:</p><p><span data-type="mathematics" data-latex="x = \\frac{-b \\pm \\sqrt{b^2-4ac}}{2a}"></span></p>',
        
        2: '<p>Hitung integral berikut:</p><p><span data-type="mathematics" data-latex="\\int_{0}^{1} x^2 \\, dx = \\left[ \\frac{x^3}{3} \\right]_{0}^{1} = \\frac{1}{3}"></span></p>',
        
        3: '<p>Matriks identitas 2x2:</p><p><span data-type="mathematics" data-latex="I = \\begin{pmatrix} 1 & 0 \\\\ 0 & 1 \\end{pmatrix}"></span></p>',
        
        4: '<p>Hukum Newton II:</p><p><span data-type="mathematics" data-latex="F = ma"></span></p><p>Energi kinetik:</p><p><span data-type="mathematics" data-latex="E_k = \\frac{1}{2}mv^2"></span></p>',
        
        5: '<p>Persamaan reaksi:</p><p><span data-type="mathematics" data-latex="2H_2 + O_2 \\rightarrow 2H_2O"></span></p><p>pH larutan:</p><p><span data-type="mathematics" data-latex="pH = -\\log[H^+]"></span></p>'
    }
    content.value = examples[num] || ''
}
</script>
