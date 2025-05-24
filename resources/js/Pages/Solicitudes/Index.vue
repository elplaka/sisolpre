<template>
    <AdminLayout :userAuth="userAuth">
      <Head title="Solicitudes" />
  
      <TablaSolicitudes
        :solicitudes="solicitudes.data"
        :pagination="solicitudes"
        :estatusSolicitud="estatusSolicitud"
        :tiposPropiedades="tiposPropiedades"
        :destinosObras="destinosObras"
        :tiposTramites="tiposTramites"
        :localidades="localidades">
      </TablaSolicitudes>
    </AdminLayout>
  </template>

  <script setup>
// Importa componentes necesarios
import AdminLayout from '../Admin/Components/AdminLayout.vue';
import TablaSolicitudes from './TablaSolicitudes.vue';
import { Head } from '@inertiajs/vue3'; // Componente Head para Inertia.js

const props = defineProps({
  solicitudes: Object, // Datos paginados de las solicitudes
  estatusSolicitud: Object,
  tiposPropiedades: Object,
  destinosObras: Object,
  tiposTramites: Object,
  localidades: Object,
  // solicitantes: Object, // Datos relacionados de los solicitantes
  // propiedades: Object, // Datos relacionados de las propiedades
  // tramites: Object, // Datos relacionados de los trámites
  // estatus: Object, // Estatus de solicitudes
  // searchQuery: String, // Cadena de búsqueda
  userAuth: { type: Object, required: true }, // Información de usuario autenticado
});
</script>