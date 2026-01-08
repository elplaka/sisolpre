<template>
    <AdminLayout :userAuth="userAuth">
      <Head title="Requisitos" />
  
      <TablaRequisitos
        :requisitos="requisitos.data"
        :pagination="requisitos"
        :requisitosSelect="requisitosSelect"
        :tramitesSelect="tramitesSelect"
        :nomRequisitosQuery ="nomRequisitosQuery"
        :nomTramitesQuery ="nomTramitesQuery"
        :obligatorioQuery ="obligatorioQuery"
        :agruparTramites="agruparTramites"
        :tramites="tramites">
      </TablaRequisitos>
    </AdminLayout>
  </template>

  <script setup>
// Importa componentes necesarios
import AdminLayout from '../Admin/Components/AdminLayout.vue';
import TablaRequisitos from './TablaRequisitos.vue';
import { Head } from '@inertiajs/vue3'; // Componente Head para Inertia.js

const props = defineProps({
  requisitos: Object, // Datos paginados de las solicitudes
  requisitosSelect: Object, // Datos paginados de las solicitudes
  tramitesSelect: Object, // Datos paginados de las solicitudes
  nomRequisitosQuery: Object,
  nomTramitesQuery: Object,
  obligatorioQuery: Boolean,
  agruparTramites: Boolean,
  tramites: Object,
  userAuth: { type: Object, required: true }, // Información de usuario autenticado
});
</script>

