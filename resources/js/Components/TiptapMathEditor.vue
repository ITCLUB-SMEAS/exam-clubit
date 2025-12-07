<template>
    <div class="tiptap-editor">
        <div class="tiptap-toolbar" v-if="editor">
            <!-- Text Formatting -->
            <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="{ active: editor.isActive('bold') }" title="Bold">
                <i class="fa fa-bold"></i>
            </button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="{ active: editor.isActive('italic') }" title="Italic">
                <i class="fa fa-italic"></i>
            </button>
            <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="{ active: editor.isActive('underline') }" title="Underline">
                <i class="fa fa-underline"></i>
            </button>
            
            <span class="divider"></span>
            
            <!-- Alignment -->
            <button type="button" @click="editor.chain().focus().setTextAlign('left').run()" :class="{ active: editor.isActive({ textAlign: 'left' }) }" title="Align Left">
                <i class="fa fa-align-left"></i>
            </button>
            <button type="button" @click="editor.chain().focus().setTextAlign('center').run()" :class="{ active: editor.isActive({ textAlign: 'center' }) }" title="Align Center">
                <i class="fa fa-align-center"></i>
            </button>
            <button type="button" @click="editor.chain().focus().setTextAlign('right').run()" :class="{ active: editor.isActive({ textAlign: 'right' }) }" title="Align Right">
                <i class="fa fa-align-right"></i>
            </button>
            
            <span class="divider"></span>
            
            <!-- Lists -->
            <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="{ active: editor.isActive('bulletList') }" title="Bullet List">
                <i class="fa fa-list-ul"></i>
            </button>
            <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="{ active: editor.isActive('orderedList') }" title="Numbered List">
                <i class="fa fa-list-ol"></i>
            </button>
            
            <span class="divider"></span>
            
            <!-- Media -->
            <button type="button" @click="setLink" title="Insert Link">
                <i class="fa fa-link"></i>
            </button>
            <button type="button" @click="addImage" title="Insert Image">
                <i class="fa fa-image"></i>
            </button>
            
            <span class="divider"></span>
            
            <!-- Math -->
            <button type="button" @click="showMathModal = true" class="math-btn" title="Insert Formula (LaTeX)">
                <i class="fa fa-square-root-alt"></i> f(x)
            </button>
            <button type="button" @click="insertCommonSymbol('\\frac{a}{b}')" title="Fraction">
                <span style="font-family: serif;">a/b</span>
            </button>
            <button type="button" @click="insertCommonSymbol('\\sqrt{x}')" title="Square Root">
                √
            </button>
            <button type="button" @click="insertCommonSymbol('x^{2}')" title="Superscript">
                x²
            </button>
            <button type="button" @click="insertCommonSymbol('\\int')" title="Integral">
                ∫
            </button>
            <button type="button" @click="insertCommonSymbol('\\sum')" title="Summation">
                ∑
            </button>
        </div>
        
        <editor-content :editor="editor" class="tiptap-content" />
        
        <!-- Math Modal -->
        <div v-if="showMathModal" class="math-modal-overlay" @click.self="showMathModal = false">
            <div class="math-modal">
                <div class="math-modal-header">
                    <h5><i class="fa fa-square-root-alt"></i> Insert Formula (LaTeX)</h5>
                    <button type="button" @click="showMathModal = false" class="close-btn">&times;</button>
                </div>
                <div class="math-modal-body">
                    <label>LaTeX Code:</label>
                    <textarea v-model="mathInput" class="form-control" rows="3" placeholder="Contoh: x = \frac{-b \pm \sqrt{b^2-4ac}}{2a}"></textarea>
                    
                    <div class="mt-3">
                        <label>Preview:</label>
                        <div class="math-preview" v-html="mathPreview"></div>
                    </div>
                    
                    <div class="mt-3">
                        <label>Common Symbols:</label>
                        <div class="symbol-grid">
                            <button type="button" @click="mathInput += '\\alpha'" class="symbol-btn">α (alpha)</button>
                            <button type="button" @click="mathInput += '\\beta'" class="symbol-btn">β (beta)</button>
                            <button type="button" @click="mathInput += '\\gamma'" class="symbol-btn">γ (gamma)</button>
                            <button type="button" @click="mathInput += '\\pi'" class="symbol-btn">π (pi)</button>
                            <button type="button" @click="mathInput += '\\theta'" class="symbol-btn">θ (theta)</button>
                            <button type="button" @click="mathInput += '\\infty'" class="symbol-btn">∞ (infinity)</button>
                            <button type="button" @click="mathInput += '\\leq'" class="symbol-btn">≤ (leq)</button>
                            <button type="button" @click="mathInput += '\\geq'" class="symbol-btn">≥ (geq)</button>
                            <button type="button" @click="mathInput += '\\neq'" class="symbol-btn">≠ (neq)</button>
                            <button type="button" @click="mathInput += '\\pm'" class="symbol-btn">± (pm)</button>
                            <button type="button" @click="mathInput += '\\times'" class="symbol-btn">× (times)</button>
                            <button type="button" @click="mathInput += '\\div'" class="symbol-btn">÷ (div)</button>
                        </div>
                    </div>
                </div>
                <div class="math-modal-footer">
                    <button type="button" @click="insertMath" class="btn btn-primary">Insert</button>
                    <button type="button" @click="showMathModal = false" class="btn btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import TextAlign from '@tiptap/extension-text-align'
import Underline from '@tiptap/extension-underline'
import Mathematics from '@tiptap/extension-mathematics'
import { watch, onBeforeUnmount, ref, computed } from 'vue'
import katex from 'katex'
import 'katex/dist/katex.min.css'

const props = defineProps({
    modelValue: { type: String, default: '' },
    height: { type: Number, default: 200 }
})

const emit = defineEmits(['update:modelValue'])

const showMathModal = ref(false)
const mathInput = ref('')

const mathPreview = computed(() => {
    if (!mathInput.value) return '<span class="text-muted">Preview will appear here...</span>'
    try {
        return katex.renderToString(mathInput.value, { throwOnError: false, displayMode: true })
    } catch (e) {
        return '<span class="text-danger">Invalid LaTeX syntax</span>'
    }
})

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Underline,
        Link.configure({ openOnClick: false }),
        Image,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Mathematics
    ],
    onUpdate: () => emit('update:modelValue', editor.value.getHTML())
})

watch(() => props.modelValue, (val) => {
    if (editor.value && editor.value.getHTML() !== val) {
        editor.value.commands.setContent(val, false)
    }
})

onBeforeUnmount(() => editor.value?.destroy())

const setLink = () => {
    const url = window.prompt('URL:')
    if (url) editor.value.chain().focus().setLink({ href: url }).run()
}

const addImage = () => {
    const url = window.prompt('Image URL:')
    if (url) editor.value.chain().focus().setImage({ src: url }).run()
}

const insertMath = () => {
    if (mathInput.value) {
        editor.value.chain().focus().insertContent(`<span data-type="mathematics" data-latex="${mathInput.value}"></span> `).run()
        mathInput.value = ''
        showMathModal.value = false
    }
}

const insertCommonSymbol = (latex) => {
    editor.value.chain().focus().insertContent(`<span data-type="mathematics" data-latex="${latex}"></span> `).run()
}
</script>

<style scoped>
.tiptap-editor {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}
.tiptap-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    padding: 8px;
    border-bottom: 1px solid #ced4da;
    background: #f8f9fa;
}
.tiptap-toolbar button {
    padding: 6px 10px;
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
}
.tiptap-toolbar button:hover { background: #e9ecef; }
.tiptap-toolbar button.active { background: #dee2e6; color: #0d6efd; }
.tiptap-toolbar .math-btn { font-weight: bold; }
.tiptap-toolbar .divider {
    width: 1px;
    background: #ced4da;
    margin: 0 4px;
}
.tiptap-content { padding: 12px; min-height: v-bind(height + 'px'); }
.tiptap-content :deep(.ProseMirror) { outline: none; min-height: v-bind((height - 24) + 'px'); }
.tiptap-content :deep(.ProseMirror p) { margin: 0 0 0.5em; }
.tiptap-content :deep(.ProseMirror img) { max-width: 100%; height: auto; }

/* Math Modal */
.math-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.math-modal {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}
.math-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #dee2e6;
}
.math-modal-header h5 {
    margin: 0;
    font-size: 18px;
}
.close-btn {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #6c757d;
}
.close-btn:hover { color: #000; }
.math-modal-body {
    padding: 16px;
}
.math-preview {
    padding: 16px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    min-height: 60px;
    text-align: center;
}
.symbol-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 8px;
}
.symbol-btn {
    padding: 8px;
    border: 1px solid #dee2e6;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}
.symbol-btn:hover {
    background: #e9ecef;
}
.math-modal-footer {
    padding: 16px;
    border-top: 1px solid #dee2e6;
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}
</style>
