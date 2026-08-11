<template>
  <!-- <div v-if="isGlobalLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="flex items-center">
      <svg class="animate-spin h-8 w-8 text-color1-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <span class="ml-2 text-gray-300">Cargando!!!!...</span>
    </div>
  </div> -->
  <div v-if="isGlobalLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity">
            <div class="flex items-center">
                <svg class="animate-spin h-8 w-8 text-color1-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-300">Cargando...</span>
            </div>
        </div>
  <div class="antialiased bg-gray-50 dark:bg-gray-900">
    <Head title="Inicio" />

    <!-- <Navbar :userAuth="userAuth" /> -->
    <Sidebar :userAuth="userAuth" ref="sidebar" />
    <main :style="{ marginLeft: mainMargin }">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted, nextTick } from 'vue';
import { initFlowbite } from 'flowbite';
import Navbar from './NavBar.vue';
import Sidebar from './SideBar.vue';
import { defineProps } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3'


// Variable para controlar la visibilidad del spinner
const isGlobalLoading = ref(false);

// Variable para el temporizador, para evitar el parpadeo
let loadingTimeout = null;

// Evento que se dispara al inicio de una visita de Inertia
router.on('start', (event) => {
    // 🚩 CLAVE 1: Accede al objeto de la visita a través del evento
    const visit = event.detail.visit;

    // 🚩 CLAVE 2: Verifica si la visita es una visita parcial ('only')
    // Las visitas de polling tienen un array 'only' con los nombres de los props a actualizar.
    // Si la visita tiene props 'only', es una visita parcial y no debe mostrar el spinner.
    if (visit.only && visit.only.length > 0) {
        // Ignora el spinner para visitas de polling/parciales
        return;
    }

    // 🚩 CLAVE 3: Si no es una visita parcial, muestra el spinner con un retraso
    // loadingTimeout = setTimeout(() => {
        isGlobalLoading.value = true;
    // }, 250);
});

// Evento que se dispara al final de una visita de Inertia
router.on('finish', () => {
    // Limpia el temporizador sin importar si el spinner se mostró o no
    clearTimeout(loadingTimeout);
    isGlobalLoading.value = false;
});

const sidebar = ref(null);
const windowWidth = ref(window.innerWidth);
const props = defineProps({ userAuth: { type: Object, required: true } });

onMounted(() => {
  initFlowbite();
  window.addEventListener('resize', handleResize);
  updateSidebarMarginOnMount();
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
});

const handleResize = () => {
  windowWidth.value = window.innerWidth;
};

const sidebarWidth = computed(() => {
  return sidebar.value ? sidebar.value.$el.offsetWidth + 'px' : '0px';
});

const isMdOrLarger = computed(() => windowWidth.value >= 768); // 768px es el breakpoint 'md' de Tailwind por defecto

const mainMargin = computed(() => {
  return isMdOrLarger.value ? '14rem' : sidebarWidth.value; // '13rem' es el equivalente a md:ml-52
});

const updateSidebarMarginOnMount = () => {
  nextTick(() => {
    // El ancho inicial se calculará automáticamente con el computed property
  });
};
</script>