<script setup>
// Importamos layouts e Inertia tools
import Formulario from './Formulario.vue'; // Ajusta la ruta a donde esté tu formulario
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../Admin/Components/AdminLayout.vue';


// Recibimos los props que manda el PropiedadController
const props = defineProps({
    propiedad: Object,
    tramite: Object,
    tiposPropiedad: Array,
    desdeTramite: Boolean,
    tipoTramite: String,
    campoEspecifico: String,
    tramiteData: { type: Object },
    userAuth: { type: Object, required: true },
    origenTramite: [String, Number] // El parámetro que mandamos desde el SweetAlert
});

// Manejar el botón "Cancelar" del formulario para regresar al trámite de origen
const handleCancel = () => {
    if (props.origenTramite) {
        // Si venía de un trámite, regresamos allá (ajusta la URL a tu formato de rutas)
        router.get(`/constancias-numero-oficial/edit/${props.origenTramite}`);
    } else {
        // Respaldo por si entraron directo sin origen
        router.get('/propiedades'); 
    }
};

// Manejar el guardado exitoso
const handleSave = () => {
    if (props.origenTramite) {
        router.get(`/constancias-numero-oficial/edit/${props.origenTramite}`);
    } else {
        router.get('/propiedades');
    }
};
</script>

<template>
    <AdminLayout :userAuth="userAuth">

    <Head title="Propiedades" />

    <Formulario
        :selectedPropiedad="props.propiedad"
        :tiposPropiedad="props.tiposPropiedad"
        :tramite="props.tramite"
        :desdeTramite="props.desdeTramite"
        :tipoTramite="props.tipoTramite"
        :campoEspecifico="props.campoEspecifico"
        :tramiteData="props.tramiteData"
        :isPage="true"
        @cancel="handleCancel" 
        @save="handleSave"/>

    </AdminLayout>
</template>