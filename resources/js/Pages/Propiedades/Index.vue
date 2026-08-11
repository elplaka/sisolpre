<template>
    <AdminLayout :userAuth="userAuth">
      <Head title="Propiedades" />
  
      <TablaPropiedades
        :propiedades="propiedades.data"
        :pagination="propiedades"
        :tiposPropiedad="tiposPropiedad">
      </TablaPropiedades>
    </AdminLayout>
  </template>

  <script setup>
// Importa componentes necesarios
import AdminLayout from '../Admin/Components/AdminLayout.vue';
import TablaPropiedades from './TablaPropiedades.vue';
import { Head } from '@inertiajs/vue3'; // Componente Head para Inertia.js

const props = defineProps({
  propiedades: Object, // Datos paginados de las propiedades
  tiposPropiedad: Object,
  userAuth: { type: Object, required: true }, // Información de usuario autenticado
});
</script>