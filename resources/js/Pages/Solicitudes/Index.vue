<template>
    <AdminLayout :userAuth="userAuth">
      <Head title="Solicitudes" />
  
      <TablaSolicitudes
        :solicitudes="solicitudes.data"
        :pagination="solicitudes"
        :estatusSolicitud="estatusSolicitud"
        :tiposPropiedades="tiposPropiedades"
        :destinosObras="destinosObras"
        :sectores ="sectores"
        :tiposTramites="tiposTramites"
        :tramites="tramites"
        :localidades="localidades"
        :localidadesQuery="localidadesQuery"
        :numQuery ="numQuery"
        :folioQuery ="folioQuery"
        :nombreQuery ="nombreQuery"
        :fechaIngresoInicioQuery="props.fechaIngresoInicioQuery"
        :fechaIngresoFinQuery="props.fechaIngresoFinQuery"
        :claveCatastralQuery="claveCatastralQuery"
        :idRangoFechasIngresoQuery="props.idRangoFechasIngresoQuery"
        :tiposTramitesQuery="tiposTramitesQuery"
        :tramitesQuery="tramitesQuery"
        :sortColumn="sortColumn"
        :sortDirection="sortDirection"
        :paraNuevaSolicitud="paraNuevaSolicitud"
        :filtroChkSolicitudes="filtroChkSolicitudes"
        :periodoActual="periodoActual">
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
  sectores: Object, // Datos relacionados de los sectores
  tramites: Object, // Datos relacionados de los trámites
  localidadesQuery: Object,
  localidades: Object,
  fechaIngresoInicioQuery: String,
  fechaIngresoFinQuery: String,
  idRangoFechasIngresoQuery: Number,
  tiposTramitesQuery: Object, // Datos relacionados de los tipos de trámites
  tramitesQuery: Object, // Datos relacionados de los trámites
  numQuery: String, // Cadena de búsqueda
  folioQuery: String, // Cadena de búsqueda
  nombreQuery: String, // Cadena de búsqueda
  claveCatastralQuery: String, // Cadena de búsqueda
  filtroChkSolicitudes: Number, // Filtro de solicitudes
  sortColumn: String, // Columna por la que se ordena
  sortDirection: String, // Dirección del ordenamiento (asc/desc)
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