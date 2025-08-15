<template>
    <AdminLayout :userAuth="userAuth">
      <Head title="Estadísticas" />
  
      <GraficosEstadisticas>
      </GraficosEstadisticas>
    </AdminLayout>
  </template>

  <script setup>
// Importa componentes necesarios
import AdminLayout from '../Admin/Components/AdminLayout.vue';
import GraficosEstadisticas from './GraficosEstadisticas.vue';
import { Head } from '@inertiajs/vue3'; // Componente Head para Inertia.js

const props = defineProps({
//   solicitudes: Object, // Datos paginados de las solicitudes
//   estatusSolicitud: Object,
//   tiposPropiedades: Object,
//   destinosObras: Object,
//   tiposTramites: Object,
//   tramites: Object, // Datos relacionados de los trámites
//   localidades: Object,
//   fechaInicioQuery: String,
//   fechaFinQuery: String,
//   idRangoFechasQuery: Number,
//   tiposTramitesQuery: Object, // Datos relacionados de los tipos de trámites
//   tramitesQuery: Object, // Datos relacionados de los trámites
//   nombreQuery: String, // Cadena de búsqueda
//   filtroChkSolicitudes: Number, // Filtro de solicitudes
//   sortColumn: String, // Columna por la que se ordena
//   sortDirection: String, // Dirección del ordenamiento (asc/desc)
//   paraNuevaSolicitud: Boolean, // Indica si es para una nueva solicitud
   userAuth: { type: Object, required: true }, // Información de usuario autenticado
//   periodoActual: { type: Object, required: true }
 });
</script>