<template>
    <AdminLayout :userAuth="userAuth">
      <Head title="Solicitudes" />
  
      <TablaSolicitudes
          :solicitudes="props.solicitudes.data"
          :pagination="props.solicitudes"
          :estatusSolicitud="props.estatusSolicitud"
          :fechaIngresoInicioQuery="props.fechaIngresoInicioQuery"
          :fechaIngresoFinQuery="props.fechaIngresoFinQuery"
          :totalesEstatusQuery="props.totalesEstatusQuery"
          :idRangoFechasIngresoQuery="Number(props.idRangoFechasIngresoQuery)"
          :paraNuevaSolicitud="props.paraNuevaSolicitud"
          :rangoFechasIngresoManual="props.rangoFechasIngresoManual"
          :periodoActual="props.periodoActual"
          :localidadesList="props.localidadesList"
          :areasList="props.areasList"
          :estatusList="props.estatusList"
          :totalSolicitudes="props.totalSolicitudes"
          :estatusQuery="props.estatusQuery"
          :desdeSideBar="props.desdeSideBar"
          :page="props.page"
      />
    </AdminLayout>
  </template>

  <script setup>
// Importa componentes necesarios
import AdminLayout from '../Admin/Components/AdminLayout.vue';
import TablaSolicitudes from './TablaSolicitudes.vue';
import { Head } from '@inertiajs/vue3'; // Componente Head para Inertia.js
import { onMounted, watch } from 'vue';

const props = defineProps({
  solicitudes: Object, // Datos paginados de las solicitudes
  estatusSolicitud: Object,
  localidadesQuery: Object,
  localidadesList: Array,
  fechaIngresoInicioQuery: String,
  fechaIngresoFinQuery: String,
  idRangoFechasIngresoQuery: Number,
  totalesEstatusQuery: Object,
  rangoFechasIngresoManual: Boolean, 
  areasList: Array,
  estatusList: Array,
  totalSolicitudes: Number,
  estatusQuery: String,
  desdeSideBar: {
      type: Boolean,
      default: false
  },
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