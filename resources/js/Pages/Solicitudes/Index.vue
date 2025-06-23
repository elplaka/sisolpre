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
        :tramites="tramites"
        :localidades="localidades"
        :nombreQuery ="nombreQuery"
        :fechaInicioQuery="fechaInicioQuery"
        :fechaFinQuery="fechaFinQuery"
        :tiposTramitesQuery="tiposTramitesQuery"
        :tramitesQuery="tramitesQuery"
        :filtroChkSolicitudes="filtroChkSolicitudes">
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
  tramites: Object, // Datos relacionados de los trámites
  localidades: Object,
  fechaInicioQuery: String,
  fechaFinQuery: String,
  tiposTramitesQuery: Object, // Datos relacionados de los tipos de trámites
  // solicitantes: Object, // Datos relacionados de los solicitantes
  // propiedades: Object, // Datos relacionados de las propiedades
  tramitesQuery: Object, // Datos relacionados de los trámites
  // estatus: Object, // Estatus de solicitudes
  nombreQuery: String, // Cadena de búsqueda
  filtroChkSolicitudes: Number, // Filtro de solicitudes
  userAuth: { type: Object, required: true }, // Información de usuario autenticado
});
</script>