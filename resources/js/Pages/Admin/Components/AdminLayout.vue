<template>
  <div class="antialiased bg-gray-50 dark:bg-gray-900">
    <Head title="Inicio" />

    <Navbar :userAuth="userAuth" />
    <Sidebar ref="sidebar" />
    <main class="p-4 mt-20" :style="{ marginLeft: mainMargin }">
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
  return isMdOrLarger.value ? '13rem' : sidebarWidth.value; // '13rem' es el equivalente a md:ml-52
});

const updateSidebarMarginOnMount = () => {
  nextTick(() => {
    // El ancho inicial se calculará automáticamente con el computed property
  });
};
</script>