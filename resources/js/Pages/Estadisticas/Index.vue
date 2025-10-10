<template>
    <AdminLayout :userAuth="userAuth">
      <Head title="Estadísticas" />
  
      <GraficosEstadisticas 
      :periodoActual="periodoActual"
      :tipoGrafico="tipoGrafico"
      :tipoEstadistica="tipoEstadistica"
      :fechaInicioQuery="fechaInicioQuery"
      :fechaFinQuery="fechaFinQuery"
      :solicitudesPorPeriodo="solicitudes"
      :totalSolicitudesPorPeriodo="totalSolicitudes"
      :agruparPorDia="agruparPorDia"
      :tramitesMasSolicitados="tramitesMasSolicitados"
      :totalTramitesMasSolicitados="totalTramitesMasSolicitados"
      :elementosRanking="elementosRanking"
      :tiposPropiedad="tiposPropiedad"
      :localidadesMasSolicitadas="localidadesMasSolicitadas"
      :vieneDeDashboard="vieneDeDashboard">
      </GraficosEstadisticas>
    </AdminLayout>
  </template>

  <script setup>
// Importa componentes necesarios
import AdminLayout from '../Admin/Components/AdminLayout.vue';
import GraficosEstadisticas from './GraficosEstadisticas.vue';
import { Head } from '@inertiajs/vue3'; // Componente Head para Inertia.js

const props = defineProps({
    userAuth: { type: Object, required: true }, // Información de usuario autenticado
    periodoActual: { type: Object, required: true },
    tipoGrafico: { type: String },
    tipoEstadistica: { type: String },
    fechaInicioQuery: { type: String },
    fechaFinQuery: { type: String },
    solicitudes: Object,
    totalSolicitudes: Number,
    agruparPorDia: Boolean,
    tramitesMasSolicitados: Object,
    totalTramitesMasSolicitados: Number,
    elementosRanking: Number,
    tiposPropiedad: Object,
    localidadesMasSolicitadas: Object,
    vieneDeDashboard: Boolean
 });

</script>