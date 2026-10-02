<template>
    <AdminLayout :userAuth="userAuth">
      <Head title="Solicitudes" />
  
      <TablaSolicitudes
        :solicitudes="solicitudes.data"
        :pagination="solicitudes"
        :estatusSolicitud="estatusSolicitud"
        :localidadesQuery="localidadesQuery"
        :numQuery ="numQuery"
        :nombreQuery ="nombreQuery"
        :fechaIngresoInicioQuery="props.fechaIngresoInicioQuery"
        :fechaIngresoFinQuery="props.fechaIngresoFinQuery"
        :totalesEstatusQuery="props.totalesEstatusQuery"
        :idRangoFechasIngresoQuery="props.idRangoFechasIngresoQuery"
        :sortColumn="sortColumn"
        :sortDirection="sortDirection"
        :paraNuevaSolicitud="paraNuevaSolicitud"
        :filtroChkSolicitudes="filtroChkSolicitudes"
        :periodoActual="periodoActual"
        :localidadesList="localidadesList"
        :areasList="areasList"
        :estatusList="estatusList"
        :totalSolicitudes="totalSolicitudes"
        :page="page">
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
  localidadesQuery: Object,
  localidadesList: Array,
  fechaIngresoInicioQuery: String,
  fechaIngresoFinQuery: String,
  idRangoFechasIngresoQuery: Number,
  totalesEstatusQuery: Object,
  numQuery: String, // Cadena de búsqueda
  nombreQuery: String, // Cadena de búsqueda
  filtroChkSolicitudes: Number, // Filtro de solicitudes
  sortColumn: String, // Columna por la que se ordena
  sortDirection: String, // Dirección del ordenamiento (asc/desc)
  areasList: Array,
  estatusList: Array,
  totalSolicitudes: Number,
  page: Number,
  paraNuevaSolicitud: {
            type: [Boolean, String], // Acepta Boolean O String
            validator: (value) => {
                // El validador se encarga de convertir y validar.
                if (typeof value === 'string') {
                    return value === '1' || value === 'true' || value === '0' || value === 'false';
                }
                return typeof value === 'boolean';
            }
        },
  userAuth: { type: Object, required: true }, // Información de usuario autenticado
  periodoActual: { type: Object, required: true }
});
</script>