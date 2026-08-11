<script setup>
import { computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import FormularioConstanciaNumeroOficial from '../../Tramites/FormularioConstanciaNumeroOficial.vue';
import AdminLayout from '../../Admin/Components/AdminLayout.vue';

// Recibimos los props que manda el controlador
const props = defineProps({
    initialData: Object,
    documentoData: [Object, String, null],
    propiedad: Object,
    data: Object,
    tiposPropiedad: Array,
    desdePropiedad: Boolean,
    todosEstatus: Array,
    isPage: Boolean,
    userAuth: { type: Object, required: true },
    origenTramite: [String, Number] 
});


// Manejar el botón "Cancelar" del formulario para regresar al trámite de origen
const handleCancel = () => {
    router.get('/tramites'); 
};

// Manejar el guardado exitoso
const handleSave = () => {
    if (props.origenTramite) {
        router.get(`/constancias-numero-oficial/edit/${props.origenTramite}`);
    } else {
        router.get('/propiedades');
    }
};

// 🚀 SOLUCIÓN 2: Eliminado el error ReferenceError usando props.tramite directamente
const destinatarios = computed(() => {
    if (!props.data) return [];

    const data = props.data;
    const solicitante = data.solicitud?.contacto || data.contacto || {};
    const propietario = data.propiedad?.contacto || {};
    const razonSocial = data.solicitud || data || {};

    // Función auxiliar para determinar género basándose en CURP
    const obtenerGenero = (curp, tipoBase) => {
        if (!curp || curp.length < 10) return tipoBase;
        const letraGenero = curp.charAt(10).toUpperCase();

        if (tipoBase === 'SOLICITANTE') {
            return letraGenero === 'M' ? 'SOLICITANTE' : 'SOLICITANTE';
        }
        
        if (tipoBase === 'PROPIETARIO') {
            return letraGenero === 'M' ? 'PROPIETARIA' : 'PROPIETARIO';
        }
        
        return tipoBase;
    };

    // 1. Creamos la lista base con los datos del trámite inyectado
    const listaBase = [
        { 
            tipo: obtenerGenero(solicitante.persona?.curp, 'SOLICITANTE'), 
            nombre: `${solicitante?.persona?.nombre || ''} ${solicitante?.persona?.apellidos || ''}`.trim() 
        },
        { 
            tipo: obtenerGenero(propietario.contacto?.persona?.curp, 'PROPIETARIO'), 
            nombre: `${propietario?.persona?.nombre || ''} ${propietario?.persona?.apellidos || ''}`.trim() 
        },
        { 
            tipo: 'RAZÓN SOCIAL', 
            nombre: razonSocial.solicitud?.razon_social?.nombre || '' 
        }
    ].filter(item => item.nombre && item.nombre !== '' && item.nombre !== 'undefined undefined');


    // 2. Quitamos duplicados priorizando PROPIETARIO/A
    const unicos = {};
    
    listaBase.forEach(item => {
        const esPropietario = item.tipo === 'PROPIETARIO' || item.tipo === 'PROPIETARIA';
        
        if (!unicos[item.nombre] || esPropietario) {
            unicos[item.nombre] = item;
        }
    });


    return Object.values(unicos);
});

    onMounted(() => {

    });

    // Opción B: Verlo cada vez que cambie por si el trámite se actualiza
    watch(destinatarios, (nuevoValor) => {
    }, { immediate: true }); // immediate asegura que se ejecute también al arrancar

    // const initialData = computed(() => props.tramite);
    // const documentoData = computed(() => props.tramite?.documento_generado);
    // const todosEstatus = computed(() => props.todosEstatus);
</script>


<template>
    <AdminLayout :userAuth="userAuth">

        <Head title="Trámites" />

        <!-- <FormularioConstanciaNumeroOficial
            :initialData="props.tramite"
            :documentoData="props.tramite?.documento_generado"
            :destinatarios="destinatarios"
            :todosEstatus="props.todosEstatus"
            :isPage="false"
            @cancel="handleCancel" 
            @save="handleSave"/> -->

            <FormularioConstanciaNumeroOficial
            :initialData="initialData"
            :documentoData="documentoData"
            :destinatarios="destinatarios"
            :todosEstatus="todosEstatus"
            :isPage="props.isPage"
            @cancel="handleCancel" 
            @save="handleSave"/>

    </AdminLayout>
</template>