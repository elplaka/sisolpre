<template>
<AdminLayout :userAuth = "userAuth">
    <Head title="Usuarios" />
    <TablaUsers :users = "users.data"  
    :types =  "types"
    :dependencias = "dependencias"
    :searchQuery = "searchQuery" 
    :selectedTypes = "selectedTypes" 
    :isActive = "isActive" 
    :isInactive = "isInactive"
    :activos = "activos"
    :inactivos = "inactivos"
    :pagination="users">
    </TablaUsers>    
</AdminLayout>
</template>

<script setup>
// import { defineProps } from 'vue';
import AdminLayout from '../Admin/Components/AdminLayout.vue'
import TablaUsers from './TablaUsers.vue'
import { Head } from '@inertiajs/vue3'; // Importa el componente Head de Inertia.js

const props = defineProps({
  users: Object, // Cambia el tipo a Object para incluir datos paginados
  types: Object,
  dependencias: Object,
  searchQuery: String,
  selectedTypes: Array,
  userAuth: { type: Object, required: true },
  isActive: Boolean,
  isInactive: Boolean,
  activos: Number,
  inactivos: Number
});
</script>


