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
      :elementosRanking="elementosRanking"
      :estadisticaSeleccionada="estadisticaSeleccionada"
      :localidadesMasSolicitadas="localidadesMasSolicitadas"
      :rawDataFromBackend="rawDataFromBackend"
      :rawDataLocalidades="rawDataLocalidades"
      :rawDataAreas="rawDataAreas"
      :rawDataEstatus="rawDataEstatus"
      :distribucionDiasSemana="distribucionDiasSemana"
      :kpisAtencionCiudadana="kpisAtencionCiudadana"
      :kpisSolicitudes="kpisSolicitudes"
      :promedioDineroPersona="promedioDineroPersona"
      :kpisRecursosPublicos="kpisRecursosPublicos"
      :vieneDeDashboard="vieneDeDashboard"
      :idTipoApoyo="idTipoApoyo">
      </GraficosEstadisticas>
    </AdminLayout>
  </template>

  <script setup>
// Importa componentes necesarios
import AdminLayout from '../Admin/Components/AdminLayout.vue';
import GraficosEstadisticas from './GraficosEstadisticas.vue';
import { Head } from '@inertiajs/vue3'; // Componente Head para Inertia.js

import { watchEffect } from 'vue';


const props = defineProps({
    userAuth: { type: Object, required: true }, // Información de usuario autenticado
    periodoActual: { type: Object, required: true },
    tipoGrafico: { type: String },
    tipoEstadistica: { type: String },
    fechaInicioQuery: { type: String },
    fechaFinQuery: { type: String },
    solicitudes: Object,
    rawDataFromBackend: Array,
    rawDataLocalidades: Array,
    rawDataAreas: Array,
    rawDataEstatus: Array,
    kpisAtencionCiudadana: Object,
    kpisSolicitudes: Object,
    distribucionDiasSemana: Object,
    totalSolicitudes: Number,
    agruparPorDia: Boolean,
    solicitudesPorPeriodo: Object,
    elementosRanking: Number,
    estadisticaSeleccionada: String,
    localidadesMasSolicitadas: Object,
    promedioDineroPersona: [Number, String],
    kpisRecursosPublicos: Object,
    vieneDeDashboard: Boolean,
    idTipoApoyo: [String, Number]
 });

 watchEffect(() => {
    console.log('=== ESTADISTICAS INDEX ===');
    console.log('solicitudes:', props.solicitudes);
    console.log('rawDataFromBackend:', props.rawDataFromBackend);
    console.log('rawDataLocalidades:', props.rawDataLocalidades);
    console.log('rawDataAreas:', props.rawDataAreas);
    console.log('rawDataEstatus:', props.rawDataEstatus);
    console.log('kpisRecursosPublicos:', props.kpisRecursosPublicos);
});

</script>