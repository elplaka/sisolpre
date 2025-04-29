<template>
    <div class="antialiased bg-gray-50 dark:bg-gray-900">
      <Head title="Inicio" />

        <!-- navbar start -->
        <Navbar :userAuth = "userAuth" />
        <!-- end -->
        <!-- Sidebar -->
        <Sidebar />
        <!-- end -->
        <main class="p-4 md:ml-52 h-auto pt-20">
            <slot/>
        </main>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { initFlowbite } from 'flowbite'
import Navbar from './NavBar.vue'
import Sidebar from './SideBar.vue'
import { defineProps } from 'vue'; 
import { Head } from '@inertiajs/vue3'; // Importa el componente Head de Inertia.js

// initialize components based on data attribute selectors
onMounted(() => {
    initFlowbite();
})
const props = defineProps({ userAuth: { type: Object, required: true } });
</script>