<template>
    <div class="tiptap-editor">
        <div class="tiptap-toolbar" v-if="editor">
            <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="{ active: editor.isActive('bold') }">
                <i class="fa fa-bold"></i>
            </button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="{ active: editor.isActive('italic') }">
                <i class="fa fa-italic"></i>
            </button>
            <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="{ active: editor.isActive('underline') }">
                <i class="fa fa-underline"></i>
            </button>
            <span class="divider"></span>
            <button type="button" @click="editor.chain().focus().setTextAlign('left').run()" :class="{ active: editor.isActive({ textAlign: 'left' }) }">
                <i class="fa fa-align-left"></i>
            </button>
            <button type="button" @click="editor.chain().focus().setTextAlign('center').run()" :class="{ active: editor.isActive({ textAlign: 'center' }) }">
                <i class="fa fa-align-center"></i>
            </button>
            <button type="button" @click="editor.chain().focus().setTextAlign('right').run()" :class="{ active: editor.isActive({ textAlign: 'right' }) }">
                <i class="fa fa-align-right"></i>
            </button>
            <span class="divider"></span>
            <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="{ active: editor.isActive('bulletList') }">
                <i class="fa fa-list-ul"></i>
            </button>
            <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="{ active: editor.isActive('orderedList') }">
                <i class="fa fa-list-ol"></i>
            </button>
            <span class="divider"></span>
            <button type="button" @click="setLink">
                <i class="fa fa-link"></i>
            </button>
            <button type="button" @click="addImage">
                <i class="fa fa-image"></i>
            </button>
        </div>
        <editor-content :editor="editor" class="tiptap-content" />
    </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import TextAlign from '@tiptap/extension-text-align'
import Underline from '@tiptap/extension-underline'
import { watch, onBeforeUnmount } from 'vue'

const props = defineProps({
    modelValue: { type: String, default: '' },
    height: { type: Number, default: 200 }
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Underline,
        Link.configure({ openOnClick: false }),
        Image,
        TextAlign.configure({ types: ['heading', 'paragraph'] })
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
}
.tiptap-toolbar button:hover { background: #e9ecef; }
.tiptap-toolbar button.active { background: #dee2e6; color: #0d6efd; }
.tiptap-toolbar .divider {
    width: 1px;
    background: #ced4da;
    margin: 0 4px;
}
.tiptap-content { padding: 12px; min-height: v-bind(height + 'px'); }
.tiptap-content :deep(.ProseMirror) { outline: none; min-height: v-bind((height - 24) + 'px'); }
.tiptap-content :deep(.ProseMirror p) { margin: 0 0 0.5em; }
.tiptap-content :deep(.ProseMirror img) { max-width: 100%; height: auto; }
</style>
