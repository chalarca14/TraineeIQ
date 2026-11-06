<script setup>
import { computed, ref, onMounted, watch, nextTick } from 'vue'
import { renderizarMarkdown } from '@/utils/markdown'

const props = defineProps({
  contenido: { type: String, default: '' }
})

// HTML ya sanitizado (ver utils/markdown.js). Es lo ÚNICO que llega al v-html.
const html = computed(() => renderizarMarkdown(props.contenido))
const raiz = ref(null)

// DOMPurify elimina <button> y onclick del HTML de la IA (es lo correcto), así que el botón
// "Copiar" NO viene del texto: lo creamos nosotros DESPUÉS de pintar, con DOM puro y sin HTML externo.
async function agregarBotonesCopiar() {
  await nextTick()
  raiz.value?.querySelectorAll('pre').forEach((pre) => {
    if (pre.querySelector('.btn-copiar')) return // ya lo tiene

    const boton = document.createElement('button')
    boton.type = 'button'
    boton.className = 'btn-copiar'
    boton.textContent = 'Copiar'
    boton.addEventListener('click', async () => {
      const codigo = pre.querySelector('code')?.textContent ?? pre.textContent
      try {
        await navigator.clipboard.writeText(codigo)
        boton.textContent = 'Copiado ✓'
      } catch {
        // El portapapeles falla en páginas sin HTTPS o si el navegador lo bloquea
        boton.textContent = 'No se pudo copiar'
      }
      setTimeout(() => { boton.textContent = 'Copiar' }, 2000)
    })
    pre.appendChild(boton)
  })
}

onMounted(agregarBotonesCopiar)
// Si el contenido cambia, v-html repinta y los botones se pierden: se vuelven a poner
watch(html, agregarBotonesCopiar)
</script>

<template>
  <!-- v-html es seguro aquí SOLO porque `html` pasa siempre por DOMPurify -->
  <div ref="raiz" class="md" v-html="html"></div>
</template>

<style scoped>
/* :deep porque el HTML lo genera marked y no lleva el atributo scoped de Vue */
.md { line-height: 1.5; overflow-wrap: anywhere; }
.md > :deep(:first-child) { margin-top: 0; }
.md > :deep(:last-child) { margin-bottom: 0; }
.md :deep(p) { margin: .5rem 0; }
.md :deep(h1), .md :deep(h2), .md :deep(h3), .md :deep(h4) { margin: .9rem 0 .4rem; line-height: 1.25; }
.md :deep(h1) { font-size: 1.25rem; }
.md :deep(h2) { font-size: 1.12rem; }
.md :deep(h3), .md :deep(h4) { font-size: 1rem; }
.md :deep(ul), .md :deep(ol) { margin: .5rem 0; padding-left: 1.4rem; }
.md :deep(li) { margin: .2rem 0; }
.md :deep(a) { color: var(--color-primary-light); }
.md :deep(blockquote) {
  margin: .6rem 0;
  padding: .1rem .8rem;
  border-left: 3px solid var(--color-primary);
  color: var(--color-text-secondary);
}
.md :deep(hr) { border: none; border-top: 1px solid var(--color-border); margin: .8rem 0; }

/* Código en línea */
.md :deep(code) {
  padding: .1rem .35rem;
  border-radius: 6px;
  background: var(--color-background);
  font-size: .88em;
}

/* Bloque de código: position relative para colocar el botón Copiar en la esquina */
.md :deep(pre) {
  position: relative;
  margin: .6rem 0;
  padding: 2.2rem .9rem .8rem;
  overflow-x: auto;
  border-radius: 10px;
  background: var(--color-background);
  scrollbar-width: thin;
  scrollbar-color: var(--color-text-secondary) transparent;
}
.md :deep(pre code) { padding: 0; background: none; font-size: .85rem; overflow-wrap: normal; white-space: pre; }

.md :deep(.btn-copiar) {
  position: absolute;
  top: .4rem;
  right: .5rem;
  padding: .2rem .6rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  background: var(--color-surface);
  color: var(--color-text-secondary);
  font-size: .72rem;
  cursor: pointer;
}
.md :deep(.btn-copiar:hover) { color: var(--color-text); border-color: var(--color-primary); }

.md :deep(table) { border-collapse: collapse; margin: .6rem 0; display: block; overflow-x: auto; }
.md :deep(th), .md :deep(td) { padding: .35rem .6rem; border: 1px solid var(--color-border); }
</style>
