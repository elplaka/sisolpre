
<script setup>
    import { Head, usePage } from '@inertiajs/vue3'; // Componente Head para Inertia.js
    import AdminLayout from '../Admin/Components/AdminLayout.vue';
    import Formulario from './Formulario.vue';
    import { computed } from 'vue';


    const props = defineProps({
        isPage: {
            type: Boolean,
            default: true // Por defecto actúa como página normal
        },
        nuevaSolicitud: Boolean,
        solicitud: Object,
        solicitudSeleccionada: Object,
        localidadesList: Array,
        areasList: Array,
        estatusList: Array,
        page: Number
    });

    const page = usePage();
    const userAuth = computed(() => page.props.auth.user);
</script>

<template>
     <AdminLayout :userAuth="userAuth">
      <Head title="Solicitudes" />
        <div class="max-w-8xl mx-auto py-8 px-0 sm:px-6 lg:px-8">
            <Formulario
                :isPage="true"
                :nuevaSolicitud="!solicitudSeleccionada"
                :solicitud="solicitudSeleccionada"
                :localidadesList="localidadesList"
                :areasList="areasList"
                :estatusList="estatusList"
            />
        </div>
     </AdminLayout>           
</template>