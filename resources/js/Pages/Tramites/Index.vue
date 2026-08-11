<template>
    <AdminLayout :userAuth="userAuth">
      <Head title="Trámites" />
  
      <TablaTramites
        :tramites="tramites.data"
        :pagination="tramites"
        :todosEstatus="todosEstatus">
      </TablaTramites>
    </AdminLayout>
  </template>

  <script setup>
// Importa componentes necesarios
import AdminLayout from '../Admin/Components/AdminLayout.vue';
import TablaTramites from './TablaTramites.vue';
import { Head } from '@inertiajs/vue3'; // Componente Head para Inertia.js

const props = defineProps({
  tramites: Object, // Datos paginados de los trámites
  todosEstatus: Object,
  pagination: Object,
  userAuth: { type: Object, required: true }, // Información de usuario autenticado
});
</script>