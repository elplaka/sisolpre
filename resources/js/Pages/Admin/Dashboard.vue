
<script setup>
    import { onMounted, defineProps, markRaw, ref } from 'vue'; // Importa markRaw
    // import { initFlowbite } from 'flowbite';
    import AdminLayout from './Components/AdminLayout.vue'; // Ajusta la ruta a tu layout
    import { router } from '@inertiajs/vue3'

    // 1. Importar todos los componentes de panel individuales
    // ¡Asegúrate de que las rutas y los nombres de los archivos sean correctos!
    import LatestSolicitudesPanel from '@/Pages/Dashboard/LatestSolicitudes.vue';
    import SolicitudesByMonthPanel     from '@/Pages/Dashboard/SolicitudesByMonth.vue';
    import TramitesByYearPanel     from '@/Pages/Dashboard/TramitesByYear.vue';
    import SolicitudesByLocationPanel     from '@/Pages/Dashboard/SolicitudesByLocation.vue';
    import PeopleSolicitudesPanel     from '@/Pages/Dashboard/PeopleSolicitudes.vue';
    import SolicitudesByPropertyTypePanel     from '@/Pages/Dashboard/SolicitudesByPropertyType.vue';
    import ShortcutsPanel     from '@/Pages/Dashboard/Shortcuts.vue';

    // 2. Definir las props que este componente principal recibirá del controlador de Inertia
    const props = defineProps({
        userAuth: { type: Object, required: true },
        panelConfig: { type: Array, required: true }, // Viene del controlador, con la info de qué panel mostrar
        panelData: { type: Object, required: true },
        userRole: String
  // Contiene todos los datos cargados para los paneles
    });

    // 3. Mapear los nombres de los componentes (como vienen en panelConfig.component_name)
    // a las referencias de los componentes Vue importados.
    // Usamos `markRaw` para evitar que Vue haga reactivo un objeto de componentes, lo cual es una optimización.
    const panelComponents = markRaw({
        LatestSolicitudesPanel: LatestSolicitudesPanel,
        SolicitudesByMonthPanel: SolicitudesByMonthPanel,
        TramitesByYearPanel: TramitesByYearPanel,
        SolicitudesByLocationPanel: SolicitudesByLocationPanel,
        PeopleSolicitudesPanel: PeopleSolicitudesPanel,
        SolicitudesByPropertyTypePanel: SolicitudesByPropertyTypePanel,
        ShortcutsPanel: ShortcutsPanel,
        // Añade aquí cualquier otro componente de panel que hayas importado
        // SomeOtherDataPanel: SomeOtherDataPanel,
    });

    // initialize components based on data attribute selectors
    onMounted(() => {
        // initFlowbite();
    });

    const sortDirection = ref('')
    const sortColumn = ref('')
    const isLoading = ref(false);
    const anioActual = new Date().getFullYear();

    // Crea un objeto Date para el 1 de enero del año actual
    const fechaInicio = ref(new Date(anioActual, 0, 1));

    // Crea un objeto Date para el 31 de diciembre del año 2025
    const fechaFin = ref(new Date(anioActual, 11, 31));

    const fechaInicioFormatoMySQL = fechaInicio.value.toLocaleDateString('en-CA');
    const fechaFinFormatoMySQL = fechaFin.value.toLocaleDateString('en-CA')
    
    function abreEstadistica(name) {
        if (name == 'LatestSolicitudesPanel')
        {
            sortDirection.value = 'desc'
            sortColumn.value = 'fecha_ingreso'

            router.post(
                '/solicitudes',
                {
                    sortColumn: sortColumn.value,
                    sortDirection: sortDirection.value,
                },
                {
                    onStart: () => { // <--- Inicia el estado de carga
                        isLoading.value = true;
                    },
                    onFinish: () => { // <--- Termina el estado de carga
                        isLoading.value = false;
                    }
                }
            )
        }
        else if (name == 'SolicitudesByMonthPanel')
        {
            router.post(
                '/estadisticas',
                {
                    tipoEstadistica: '0',
                    fechaInicioQuery: fechaInicioFormatoMySQL,
                    fechaFinQuery : fechaFinFormatoMySQL,
                    vieneDeDashboard: true
                },
                {
                    onStart: () => { // <--- Inicia el estado de carga
                        isLoading.value = true;
                    },
                    onFinish: () => { // <--- Termina el estado de carga
                        isLoading.value = false;
                    }
                }
            )
        }
        else if (name == 'TramitesByYearPanel')
        {
            router.post(
                '/estadisticas',
                {
                    tipoEstadistica: '1',
                    fechaInicioQuery: fechaInicioFormatoMySQL,
                    fechaFinQuery : fechaFinFormatoMySQL,
                    vieneDeDashboard: true
                },
                {
                    onStart: () => { // <--- Inicia el estado de carga
                        isLoading.value = true;
                    },
                    onFinish: () => { // <--- Termina el estado de carga
                        isLoading.value = false;
                    }
                }
            )
        }
        else if (name == 'SolicitudesByPropertyTypePanel')
        {
            router.post(
                '/estadisticas',
                {
                    tipoEstadistica: '2',
                    fechaInicioQuery: fechaInicioFormatoMySQL,
                    fechaFinQuery : fechaFinFormatoMySQL,
                    vieneDeDashboard: true
                },
                {
                    onStart: () => { // <--- Inicia el estado de carga
                        isLoading.value = true;
                    },
                    onFinish: () => { // <--- Termina el estado de carga
                        isLoading.value = false;
                    }
                }
            )
        }
        else if (name == 'SolicitudesByLocationPanel')
        {
            router.post(
                '/estadisticas',
                {
                    tipoEstadistica: '3',
                    fechaInicioQuery: fechaInicioFormatoMySQL,
                    fechaFinQuery : fechaFinFormatoMySQL,
                    vieneDeDashboard: true
                },
                {
                    onStart: () => { // <--- Inicia el estado de carga
                        isLoading.value = true;
                    },
                    onFinish: () => { // <--- Termina el estado de carga
                        isLoading.value = false;
                    }
                }
            )
        }
    }

</script>

<template>
    <AdminLayout :userAuth="userAuth">
        <!-- <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50">
            <div class="p-6 flex flex-col items-center">
                <svg class="animate-spin h-10 w-10 text-color1-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-50" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-50" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-white">Cargando...</p>
            </div>
        </div> -->
        <div class="w-full pl-5 pb-5 mt-5">
            <div :class="[
                userRole === 'AUXILIAR' 
                ? 'flex flex-col md:flex-row gap-4 pt-4 pr-4 h-[calc(85vh-2rem)]' 
                : 'grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 pr-4 h-full']">
                
                <!-- Elementos ocultos (sin cambios) -->
                <div class="bg-color1-600 hidden"></div>
                <div class="bg-color2-600 hidden"></div>

                <!-- Paneles -->
                <div 
                    v-for="(panel, index) in userRole === 'AUXILIAR' ? panelConfig.slice(0, 3) : panelConfig" 
                    :key="index"
                    :class="[
                        'bg-white rounded-lg shadow-md p-0 flex flex-col justify-between w-full overflow-hidden',
                        userRole === 'AUXILIAR' 
                        ? 'h-full' 
                        : 'md:min-h-[270px] md:max-h-[270px]']">
                    <component
                        :is="panelComponents[panel.component_name]"
                        :panelData="panelData[panel.data_key]"
                        class="p-4 sm:p-5 md:p-6 w-full pb-0"
                        :userAuth="userAuth"/>
                   <div 
                        @click="abreEstadistica(panel.component_name)"
                        v-if="panel.component_name !== 'ShortcutsPanel' && panel.component_name !== 'PeopleSolicitudesPanel' "
                        class="bg-gray-100 text-gray-800 font-bold py-3 px-6 text-sm flex items-center justify-between cursor-pointer border-t border-gray-200
                            transition-colors duration-200 ease-in-out hover:bg-gray-200">
                        <span>Ver más...</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>


