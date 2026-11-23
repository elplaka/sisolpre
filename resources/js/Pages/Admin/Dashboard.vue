
<script setup>
    import { onMounted, defineProps, markRaw, ref, nextTick } from 'vue'; // Importa markRaw
    // import { initFlowbite } from 'flowbite';
    import AdminLayout from './Components/AdminLayout.vue'; // Ajusta la ruta a tu layout
    import { router } from '@inertiajs/vue3'

    // 1. Importar todos los componentes de panel individuales
    import LatestSolicitudesPanel from '@/Pages/Dashboard/LatestSolicitudes.vue';
    import SolicitudesByMonthPanel     from '@/Pages/Dashboard/SolicitudesByMonth.vue';
    import SolicitudesByLocationPanel     from '@/Pages/Dashboard/SolicitudesByLocation.vue';
    import SolicitudesByAreaPanel     from '@/Pages/Dashboard/SolicitudesByArea.vue';
    import SolicitudesTotalesPanel     from '@/Pages/Dashboard/SolicitudesTotales.vue';
    import SolicitudesApoyoEntregadoPanel     from '@/Pages/Dashboard/SolicitudesApoyoEntregado.vue';
    import SolicitudesEnTramitePanel     from '@/Pages/Dashboard/SolicitudesEnTramite.vue';
    import SolicitudesAprobadasPanel     from '@/Pages/Dashboard/SolicitudesAprobadas.vue';
    import SolicitudesTiempoEntregaPanel     from '@/Pages/Dashboard/SolicitudesTiempoEntrega.vue';
    import SolicitudesConRezagoPanel     from '@/Pages/Dashboard/SolicitudesConRezago.vue';
    import SolicitudesCiudadanosAtendidosPanel    from '@/Pages/Dashboard/SolicitudesCiudadanosAtendidos.vue';
    import SolicitudesMontoEntregadoPanel    from '@/Pages/Dashboard/SolicitudesMontoEntregado.vue';
    import SolicitudesPeticionesPanel    from '@/Pages/Dashboard/SolicitudesPeticiones.vue';
    import Toolbar from '@/Pages/Dashboard/ToolBar.vue';

    // 2. Definir las props que este componente principal recibirá del controlador de Inertia
    const props = defineProps({
        userAuth: { type: Object, required: true },
        panelConfig: { type: Array, required: true }, // Viene del controlador, con la info de qué panel mostrar
        panelData: { type: Object, required: true },
        filters: { type: Object, required: true },
        periodOptions: Array,
        userRole: String
  // Contiene todos los datos cargados para los paneles
    });

    // Estado reactivo del periodo seleccionado (recuperado de los filtros de la URL si aplica)
    const currentPeriod = ref(props.filters?.period || 'anio_actual');

    // Función que se ejecuta al cambiar el select
    const handlePeriodChange = (newPeriod) => {
        isLoadingPeriod.value = true;
        cascadeUpdate.value = false;
        // Recarga la página enviando el nuevo filtro al backend (Laravel) de forma fluida
        router.get(
            route('dashboard'), // Ruta de tu dashboard
            { period: newPeriod },
            { 
                preserveState: true, 
                preserveScroll: true, 
                only: ['panelData'], // Solo actualiza las tarjetas para máxima velocidad
                onFinish: async () => {
                isLoadingPeriod.value = false;
                    await nextTick();
                    requestAnimationFrame(() => {
                        cascadeUpdate.value = true;
                    });
                }
            }
        );
    };

    // 3. Mapear los nombres de los componentes (como vienen en panelConfig.component_name)
    // a las referencias de los componentes Vue importados.
    // Usamos `markRaw` para evitar que Vue haga reactivo un objeto de componentes, lo cual es una optimización.
    const panelComponents = markRaw({
        LatestSolicitudesPanel: LatestSolicitudesPanel,
        SolicitudesByMonthPanel: SolicitudesByMonthPanel,
        SolicitudesByLocationPanel: SolicitudesByLocationPanel,
        SolicitudesByAreaPanel: SolicitudesByAreaPanel,
        SolicitudesTiempoEntregaPanel: SolicitudesTiempoEntregaPanel,
        SolicitudesConRezagoPanel: SolicitudesConRezagoPanel,
        SolicitudesCiudadanosAtendidosPanel: SolicitudesCiudadanosAtendidosPanel,
        SolicitudesTotalesPanel: SolicitudesTotalesPanel,
        SolicitudesApoyoEntregadoPanel: SolicitudesApoyoEntregadoPanel,
        SolicitudesEnTramitePanel: SolicitudesEnTramitePanel,
        SolicitudesAprobadasPanel: SolicitudesAprobadasPanel,
        SolicitudesMontoEntregadoPanel: SolicitudesMontoEntregadoPanel,
        SolicitudesPeticionesPanel: SolicitudesPeticionesPanel,

        // SolicitudesByPropertyTypePanel: SolicitudesByPropertyTypePanel,
        // ShortcutsPanel: ShortcutsPanel,
        // Añade aquí cualquier otro componente de panel que hayas importado
        // SomeOtherDataPanel: SomeOtherDataPanel,
    });

    // initialize components based on data attribute selectors
    onMounted(() => {
        // initFlowbite();
        document.getElementById('initial-loader')?.remove();
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

    const calculateDateRange = (periodValue) => {
        const currentYear = new Date().getFullYear();
        const previousYear = currentYear - 1;
        let inicio = '';
        let fin = '';
        let idRango = '';

        if (periodValue === 'anio_actual') {
            inicio = `${currentYear}-01-01`;
            fin = `${currentYear}-12-31`;
            idRango = 4
        } else if (periodValue === 'anio_anterior') {
            inicio = `${previousYear}-01-01`;
            fin = `${previousYear}-12-31`;
            idRango = 7;
        } else if (periodValue && periodValue.startsWith('gobierno_anio_')) {
            // Extraemos el número ordinal (ej. '1' de 'gobierno_anio_1')
            const ordinal = parseInt(periodValue.replace('gobierno_anio_', ''), 10);
            
            // Año base de inicio del periodo de gobierno actual (Noviembre 2024)
            const gobiernoInicioBase = new Date(2024, 10, 1); // Mes 10 es Noviembre en JS (0-indexado)
            
            // Calculamos el año de inicio sumando (ordinal - 1) años
            const anioStart = new Date(gobiernoInicioBase);
            anioStart.setFullYear(gobiernoInicioBase.getFullYear() + (ordinal - 1));

            // Calculamos el año de fin (un año después menos un día)
            const anioEnd = new Date(anioStart);
            anioEnd.setFullYear(anioStart.getFullYear() + 1);
            anioEnd.setDate(anioEnd.getDate() - 1);

            // Formatear a YYYY-MM-DD
            const formatDate = (date) => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            };

            inicio = formatDate(anioStart);
            fin = formatDate(anioEnd);
            idRango = 10 + ordinal
        } else {
            // Valor por defecto (Año actual)
            inicio = `${currentYear}-01-01`;
            fin = `${currentYear}-12-31`;
            idRango = 4
        }

        return { inicio, fin, idRango };
    };
    
    function abreEstadistica(name) {
        if (name == 'LatestSolicitudesPanel')
        {
            sortDirection.value = 'desc'
            sortColumn.value = 'fecha'

            router.post(
                '/solicitudes',
                {
                    sortColumn: sortColumn.value,
                    sortDirection: sortDirection.value,
                    currentPeriod: currentPeriod.value,
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
        else  if (name == 'SolicitudesTotalesPanel')
        {
            const rangoFechas = calculateDateRange(currentPeriod.value);

            router.post(
                '/solicitudes',
                {
                    sortCriteriaQuery: 'fecha_asc',
                    fechaIngresoInicioQuery: rangoFechas.inicio,
                    fechaIngresoFinQuery: rangoFechas.fin,
                    rangoFechasIngresoManual: true,
                    idRangoFechasIngresoQuery: rangoFechas.idRango,
                    estatusQuery: "todas"
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
        else if (name == 'SolicitudesApoyoEntregadoPanel')
        {
            const rangoFechas = calculateDateRange(currentPeriod.value);

            router.post(
                '/solicitudes',
                {
                    sortCriteriaQuery: 'fecha_asc',
                    fechaIngresoInicioQuery: rangoFechas.inicio,
                    fechaIngresoFinQuery: rangoFechas.fin,
                    rangoFechasIngresoManual: true,
                    idRangoFechasIngresoQuery: rangoFechas.idRango,
                    estatusQuery: "apoyoEntregado"
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
        else if (name == 'SolicitudesEnTramitePanel')
        {
            const rangoFechas = calculateDateRange(currentPeriod.value);

            router.post(
                '/solicitudes',
                {
                    sortCriteriaQuery: 'fecha_asc',
                    fechaIngresoInicioQuery: rangoFechas.inicio,
                    fechaIngresoFinQuery: rangoFechas.fin,
                    rangoFechasIngresoManual: true,
                    idRangoFechasIngresoQuery: rangoFechas.idRango,
                    estatusQuery: "enTramite"
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
        else if (name == 'SolicitudesAprobadasPanel')
        {
            const rangoFechas = calculateDateRange(currentPeriod.value);

            router.post(
                '/solicitudes',
                {
                    sortCriteriaQuery: 'fecha_asc',
                    fechaIngresoInicioQuery: rangoFechas.inicio,
                    fechaIngresoFinQuery: rangoFechas.fin,
                    rangoFechasIngresoManual: true,
                    idRangoFechasIngresoQuery: rangoFechas.idRango,
                    estatusQuery: "aprobadas"
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

    const irAEstadisticasDetalladas = () => {
        // Redirige a la ruta /estadisticas pasando por query parameters la estadística activa
        router.visit('/estadisticas', {
            method: 'get',
            data: {
                estadisticaSeleccionada: 'solicitudes_mes', // O el identificador que use tu backend/vista de destino
                currentPeriod: currentPeriod.value,
                desdeSideBar: false,
            },
            preserveState: true,
            preserveScroll: true,
        });
    };

    const irAEstadisticasAtencionCiudadana = () => {
        // Redirige a la ruta /estadisticas pasando por query parameters la estadística activa
        router.visit('/estadisticas', {
            method: 'get',
            data: {
                estadisticaSeleccionada: 'atencion_ciudadana', // O el identificador que use tu backend/vista de destino
                currentPeriod: currentPeriod.value,
                desdeSideBar: false,
            },
            preserveState: true,
            preserveScroll: true,
              onStart: () => {
                document.getElementById('initial-loader')?.classList.remove('hidden');
            },

            onFinish: () => {
                document.getElementById('initial-loader')?.classList.add('hidden');
            },
        });
    };

    const irAEstadisticasRecursosPublicos = () => {
        
        // Redirige a la ruta /estadisticas pasando por query parameters la estadística activa
        router.visit('/estadisticas', {
            method: 'get',
            data: {
                estadisticaSeleccionada: 'recursos_publicos', // O el identificador que use tu backend/vista de destino
                currentPeriod: currentPeriod.value,
                desdeSideBar: false,
            },
            preserveState: true,
            preserveScroll: true,
        });
    };

    const isLoadingPeriod = ref(false);
    const cascadeUpdate = ref(false);

</script>

<style>
.cascade-update > * {
    animation: cardCascade 450ms ease-out both;
}

.cascade-update > *:nth-child(1) {
    animation-delay: 0ms;
}

.cascade-update > *:nth-child(2) {
    animation-delay: 120ms;
}

.cascade-update > *:nth-child(3) {
    animation-delay: 240ms;
}

.cascade-update > *:nth-child(4) {
    animation-delay: 360ms;
}

@keyframes cardCascade {
    from {
        opacity: 0.35;
        transform: translateY(12px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<template>
<AdminLayout :userAuth="userAuth">
    <div class="w-full px-5 pb-5">
        <Toolbar 
            v-model="currentPeriod" 
            :options="periodOptions"
            @change="handlePeriodChange" 
        />
        <!-- Contenedor general en formato de filas (3 rows) -->
        <div
        :class="[
            userRole === 'AUXILIAR'
                ? 'flex flex-col gap-5 pt-2 pr-5 min-h-[75vh]'
                : 'flex flex-col gap-5 pt-2 pr-5',
            'transition-opacity duration-150',
        ]">
                
            <!-- ========================================== -->
            <!-- FILA 1: Paneles superiores (Ejemplo de 4 columnas) -->
            <!-- ========================================== -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
             :class="{ 'cascade-update': cascadeUpdate }">
                <template v-if="isLoadingPeriod">
                    <!-- Skeleton KPI 1 (con borde izquierdo destacado) -->
                    <div class="h-full border border-gray-200/80 dark:border-gray-700/80 rounded-xl p-4 sm:p-5 shadow-sm flex flex-col justify-between overflow-hidden animate-pulse">
                        <div class="flex items-center justify-between gap-2">
                            <div class="h-3.5 w-28 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-7 w-7 rounded-lg bg-gray-200 dark:bg-gray-700 shrink-0"></div>
                        </div>
                        <div class="flex items-center gap-3 my-3">
                            <div class="w-1.5 h-8 rounded-full bg-gray-200 dark:bg-gray-700 shrink-0"></div>
                            <div class="h-9 w-24 rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                        <div class="h-4 w-36 rounded bg-gray-100 dark:bg-gray-700/70"></div>
                    </div>

                    <!-- Skeleton KPI 2, 3 y 4 -->
                    <div
                        v-for="n in 3"
                        :key="'kpi-skel-' + n"
                        class="h-full bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-xl p-4 sm:p-5 shadow-sm flex flex-col justify-between overflow-hidden animate-pulse"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="h-3.5 w-32 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-7 w-7 rounded-lg bg-gray-200 dark:bg-gray-700 shrink-0"></div>
                        </div>
                        <div class="flex items-center gap-3 my-2">
                            <div class="w-1.5 h-8 rounded-full bg-gray-200 dark:bg-gray-700 shrink-0"></div>
                            <div class="h-9 w-20 rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                        <div class="h-3.5 w-28 rounded bg-gray-100 dark:bg-gray-700/70"></div>
                    </div>
                </template>
                <template v-else>
                <!-- Columna 1 -->
                    <div @click="abreEstadistica('SolicitudesTotalesPanel')" 
                        class="bg-gradient-to-br from-color1-50/50 via-white to-color1-50/20 dark:from-color1-950/20 dark:via-gray-800 dark:to-gray-800 border border-gray-200/80 dark:border-gray-700/80 border-l-4 border-l-color1-600 dark:border-l-color1-500 rounded-xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-color1-300 dark:hover:border-color1-700/60 transition-all duration-200 flex flex-col justify-between overflow-hidden cursor-pointer group select-none">
                        <component 
                            :is="panelComponents['SolicitudesTotalesPanel']" 
                            :panelData="panelData['solicitudes_totales']" 
                            :userAuth="userAuth" />
                    </div>

                    <div @click="abreEstadistica('SolicitudesApoyoEntregadoPanel')" 
                        class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700/60 transition-all duration-200 flex flex-col justify-between overflow-hidden cursor-pointer group select-none">
                        <component 
                            :is="panelComponents['SolicitudesApoyoEntregadoPanel']" 
                            :panelData="panelData['solicitudes_apoyo_entregado']" 
                            :userAuth="userAuth" />
                    </div>

                    <!-- Columna 3 -->
                    <div @click="abreEstadistica('SolicitudesEnTramitePanel')" 
                        class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700/60 transition-all duration-200 flex flex-col justify-between overflow-hidden cursor-pointer group select-none">
                        <component 
                            :is="panelComponents['SolicitudesEnTramitePanel']" 
                            :panelData="panelData['solicitudes_en_tramite']" 
                            :userAuth="userAuth" />

                    </div>
                    <!-- Columna 4 -->
                    <div @click="abreEstadistica('SolicitudesAprobadasPanel')" 
                        class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 rounded-xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-700/60 transition-all duration-200 flex flex-col justify-between overflow-hidden cursor-pointer group select-none">
                        <component 
                            :is="panelComponents['SolicitudesAprobadasPanel']" 
                            :panelData="panelData['solicitudes_aprobadas']" 
                            :userAuth="userAuth" />
                    </div>
                </template>
            </div>

            <!-- Fila 2: Siguientes paneles -->
            <div
                v-if="isLoadingPeriod"
                class="grid grid-cols-1 md:grid-cols-9 lg:grid-cols-9 gap-4">
                <!-- Skeleton: Solicitudes por mes -->
                <div
                    class="bg-white dark:bg-gray-800
                        border border-gray-200/80 dark:border-gray-700
                        rounded-xl p-3
                        shadow-sm
                        flex flex-col overflow-hidden
                        md:col-span-5
                        min-h-[230px]
                        animate-pulse">
                    <!-- Encabezado -->
                    <div class="flex items-center justify-between mb-5">
                        <div class="h-4 w-40 bg-gray-200 dark:bg-gray-700 rounded-md"></div>
                        <div class="h-7 w-7 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    </div>

                    <!-- Gráfica -->
                    <div class="flex-1 flex items-end gap-3 px-3 pb-2">
                        <div
                            v-for="(height, index) in [45, 70, 55, 85, 65, 95, 72, 60, 88, 68, 78, 92]"
                            :key="index"
                            class="flex-1 rounded-t-md bg-gray-200 dark:bg-gray-700"
                            :style="{ height: `${height}%` }"
                        ></div>
                    </div>

                    <!-- Etiquetas inferiores -->
                    <div class="flex justify-between px-2 mt-3">
                        <div
                            v-for="i in 6"
                            :key="i"
                            class="h-2 w-7 bg-gray-200 dark:bg-gray-700 rounded"
                        ></div>
                    </div>
                </div>

                <!-- Skeleton: Ciudadanos atendidos -->
                <div
                    class="bg-white dark:bg-gray-800
                        border border-gray-200/80 dark:border-gray-700
                        rounded-xl p-4
                        shadow-sm
                        flex flex-col justify-between overflow-hidden
                        md:col-span-2
                        min-h-[230px]
                        animate-pulse">
                    <div class="flex items-center justify-between">
                        <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    </div>

                    <div class="flex flex-col items-center justify-center flex-1 gap-3">
                        <div class="h-12 w-28 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                        <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>

                    <div class="h-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                </div>

                <!-- Skeleton: Recursos públicos -->
                <div
                    class="bg-white dark:bg-gray-800
                        border border-gray-200/80 dark:border-gray-700
                        rounded-xl p-4
                        shadow-sm
                        flex flex-col justify-between overflow-hidden
                        md:col-span-2
                        min-h-[230px]
                        animate-pulse">
                    <div class="flex items-center justify-between">
                        <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    </div>

                    <div class="flex flex-col items-center justify-center flex-1 gap-3">
                        <div class="h-12 w-28 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                        <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>

                    <div class="h-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                </div>
            </div>

            <div v-else-if="['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR'].some(role => $page.props.auth.user?.roles.includes(role))"
                class="grid grid-cols-1 md:grid-cols-9 lg:grid-cols-9 gap-4"
                :class="{ 'cascade-update': cascadeUpdate }">
                <!-- Solicitudes por mes -->
                <div 
                    @click="irAEstadisticasDetalladas"
                    class="bg-white dark:bg-gray-800
                        border border-gray-200/80 dark:border-gray-700
                        rounded-xl p-3
                        shadow-sm hover:shadow-md hover:border-color1-500/50 dark:hover:border-color1-500/50
                        transition-all duration-200
                        flex flex-col justify-between overflow-hidden group
                        cursor-pointer
                        md:col-span-5">
                    
                    <!-- Componente del Panel actual -->
                    <component
                        :is="panelComponents['SolicitudesByMonthPanel']"
                        :panelData="panelData['solicitudes_by_month']"
                        :userAuth="userAuth"/>
                </div>

                <div
                    @click="irAEstadisticasAtencionCiudadana"
                    class="bg-white dark:bg-gray-800
                        border border-gray-200/80 dark:border-gray-700
                        rounded-xl 
                        shadow-sm hover:shadow-md
                        transition-all duration-200
                        flex flex-col overflow-hidden group
                         md:col-span-2">
                    <component
                        :is="panelComponents['SolicitudesCiudadanosAtendidosPanel']"
                        :panelData="panelData['solicitudes_ciudadanos_atendidos']"
                        :userAuth="userAuth"/>
                </div>

                <div
                    @click="irAEstadisticasRecursosPublicos"
                    class="bg-white dark:bg-gray-800
                        border border-gray-200/80 dark:border-gray-700
                        rounded-xl
                        shadow-sm
                        transition-all duration-200
                        flex flex-col overflow-hidden group
                         md:col-span-2">
                    <component
                        :is="panelComponents['SolicitudesMontoEntregadoPanel']"
                        :panelData="panelData['solicitudes_monto_entregado']"
                        :userAuth="userAuth"/>
                </div>
            </div>

            <!-- Fila 3: Siguientes paneles -->
             <div
                v-if="isLoadingPeriod"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Skeleton: Peticiones frecuentes -->
                <div
                    class="bg-white dark:bg-gray-800
                        border border-gray-200/80 dark:border-gray-700
                        rounded-xl p-3 shadow-sm
                        flex flex-col overflow-hidden
                        min-h-[280px] animate-pulse">
                    <!-- Encabezado -->
                    <div class="flex items-center justify-between mb-5">
                        <div class="h-4 w-36 bg-gray-200 dark:bg-gray-700 rounded-md"></div>
                        <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    </div>

                    <!-- Filas de peticiones -->
                    <div class="flex flex-col gap-4 flex-1">
                        <div v-for="i in 6" :key="i" class="flex items-center gap-3">
                            <div class="h-6 w-6 rounded-md bg-gray-200 dark:bg-gray-700 shrink-0"></div>
                            <div class="flex-1 flex flex-col gap-2">
                                <div
                                    class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded"
                                    :style="{ width: `${90 - (i * 5)}%` }"
                                ></div>
                                <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700/60 rounded-full"></div>
                            </div>
                            <div class="h-3 w-7 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        </div>
                    </div>
                </div>

                <!-- Skeleton: Tiempo promedio de entrega -->
                <div
                    class="bg-white dark:bg-gray-800
                        border border-gray-200/80 dark:border-gray-700
                        rounded-xl p-3 shadow-sm
                        flex flex-col overflow-hidden
                        min-h-[280px] animate-pulse">
                    <!-- Encabezado -->
                    <div class="flex items-center justify-between mb-5">
                        <div class="h-4 w-44 bg-gray-200 dark:bg-gray-700 rounded-md"></div>
                        <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    </div>

                    <!-- Indicador principal -->
                    <div class="flex flex-col items-center justify-center flex-1 gap-3 py-4">
                        <div class="h-3 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-14 w-32 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                        <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>

                    <!-- Indicadores secundarios -->
                    <div class="grid grid-cols-2 gap-3 mt-3">
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/40 p-3 flex flex-col gap-2">
                            <div class="h-2 w-20 bg-gray-200 dark:bg-gray-600 rounded"></div>
                            <div class="h-5 w-12 bg-gray-200 dark:bg-gray-600 rounded"></div>
                        </div>
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/40 p-3 flex flex-col gap-2">
                            <div class="h-2 w-20 bg-gray-200 dark:bg-gray-600 rounded"></div>
                            <div class="h-5 w-12 bg-gray-200 dark:bg-gray-600 rounded"></div>
                        </div>
                    </div>
                </div>

                <!-- Skeleton: Solicitudes con rezago -->
                <div
                    class="bg-white dark:bg-gray-800
                        border border-gray-200/80 dark:border-gray-700
                        rounded-xl p-3 shadow-sm
                        flex flex-col overflow-hidden
                        min-h-[280px] animate-pulse">
                    <!-- Encabezado -->
                    <div class="flex items-center justify-between mb-5">
                        <div class="h-4 w-36 bg-gray-200 dark:bg-gray-700 rounded-md"></div>
                        <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    </div>

                    <!-- Indicador principal -->
                    <div class="flex flex-col items-center justify-center flex-1 gap-3 py-4">
                        <div class="h-3 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-14 w-24 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                        <div class="h-3 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>

                    <!-- Barra inferior -->
                    <div class="mt-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 p-3 flex items-center justify-between">
                        <div class="h-3 w-28 bg-gray-200 dark:bg-gray-600 rounded"></div>
                        <div class="h-5 w-12 bg-gray-200 dark:bg-gray-600 rounded"></div>
                    </div>
                </div>
            </div>
            <div v-else-if="['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR'].some(role => $page.props.auth.user?.roles.includes(role))" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" :class="{ 'cascade-update': cascadeUpdate }">
                <!-- Solicitudes por mes -->
                <div
                    class="bg-white dark:bg-gray-800
                        border border-gray-200/80 dark:border-gray-700
                        rounded-xl p-3
                        shadow-sm hover:shadow-md
                        transition-all duration-200
                        flex flex-col overflow-hidden group">
                    <component
                        :is="panelComponents['SolicitudesPeticionesPanel']"
                        :panelData="panelData['solicitudes_peticiones']"
                        :userAuth="userAuth"
                    />
                </div>

                <!-- Tiempo promedio de entrega -->
                <div
                    class="bg-white dark:bg-gray-800
                        border border-gray-200/80 dark:border-gray-700
                        rounded-xl p-3
                        shadow-sm hover:shadow-md
                        transition-all duration-200
                        flex flex-col overflow-hidden group">
                    <component
                        :is="panelComponents['SolicitudesTiempoEntregaPanel']"
                        :panelData="panelData['solicitudes_tiempo_entrega']"
                        :userAuth="userAuth"
                    />
                </div>

                <!-- Solicitudes con rezago -->
                <div
                    class="bg-white dark:bg-gray-800
                        border border-gray-200/80 dark:border-gray-700
                        rounded-xl p-3
                        shadow-sm hover:shadow-md
                        transition-all duration-200
                        flex flex-col overflow-hidden group">
                    <component
                        :is="panelComponents['SolicitudesConRezagoPanel']"
                        :panelData="panelData['solicitudes_con_rezago']"
                        :userAuth="userAuth"
                    />
                </div>

            </div>

            <!-- Contenedor Principal en Grid de 5 columnas para pantallas grandes -->
            <div
                v-if="isLoadingPeriod"
                class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-stretch">
                <!-- COLUMNA IZQUIERDA: Solo roles autorizados -->
                <div
                    v-if="['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR'].some(role => $page.props.auth.user?.roles?.includes(role))"
                    class="lg:col-span-2 flex flex-col gap-5 justify-between"
                >
                    <!-- Skeleton: Solicitudes por localidad -->
                    <div
                        class="bg-white dark:bg-gray-800
                            border border-gray-100 dark:border-gray-700
                            rounded-xl shadow-sm p-5
                            flex flex-col flex-1 min-h-[220px]
                            animate-pulse"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <div class="h-4 w-36 bg-gray-200 dark:bg-gray-700 rounded-md"></div>
                            <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                        </div>

                        <div class="flex flex-col gap-5 flex-1">
                            <div v-for="i in 5" :key="i" class="flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <div
                                        class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded"
                                        :style="{ width: `${45 + (i * 7)}%` }"
                                    ></div>
                                    <div class="h-2.5 w-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                </div>
                                <div class="h-2 w-full bg-gray-100 dark:bg-gray-700/60 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-gray-200 dark:bg-gray-600 rounded-full"
                                        :style="{ width: `${90 - (i * 12)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Skeleton: Solicitudes por área -->
                    <div
                        class="bg-white dark:bg-gray-800
                            border border-gray-100 dark:border-gray-700
                            rounded-xl shadow-sm p-5
                            flex flex-col flex-1 min-h-[220px]
                            animate-pulse"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <div class="h-4 w-32 bg-gray-200 dark:bg-gray-700 rounded-md"></div>
                            <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                        </div>

                        <div class="flex flex-col gap-5 flex-1">
                            <div v-for="i in 5" :key="i" class="flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <div
                                        class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded"
                                        :style="{ width: `${55 + (i * 5)}%` }"
                                    ></div>
                                    <div class="h-2.5 w-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                </div>
                                <div class="h-2 w-full bg-gray-100 dark:bg-gray-700/60 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-gray-200 dark:bg-gray-600 rounded-full"
                                        :style="{ width: `${85 - (i * 10)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Últimas solicitudes -->
                <div
                    :class="[
                        'bg-white dark:bg-gray-800',
                        'border border-gray-100 dark:border-gray-700',
                        'rounded-xl shadow-sm p-5 sm:p-6',
                        'flex flex-col overflow-hidden',
                        ['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR'].some(role => $page.props.auth.user?.roles?.includes(role))
                            ? 'lg:col-span-3'
                            : 'lg:col-span-5'
                    ]"
                >
                    <div class="flex items-center justify-between mb-6 animate-pulse">
                        <div class="flex flex-col gap-2">
                            <div class="h-4 w-40 bg-gray-200 dark:bg-gray-700 rounded-md"></div>
                            <div class="h-2.5 w-56 bg-gray-100 dark:bg-gray-700/60 rounded"></div>
                        </div>
                        <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                    </div>

                    <!-- Encabezados de tabla -->
                    <div class="hidden sm:grid grid-cols-5 gap-4 px-3 py-3 border-b border-gray-100 dark:border-gray-700 animate-pulse">
                        <div class="h-2.5 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-2.5 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-2.5 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-2.5 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-2.5 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>

                    <!-- Filas -->
                    <div class="flex flex-col flex-1 animate-pulse">
                        <div
                            v-for="i in 6"
                            :key="i"
                            class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-4 items-center px-3 py-5 border-b border-gray-100 dark:border-gray-700/70"
                        >
                            <!-- Folio -->
                            <div class="flex flex-col gap-2">
                                <div class="h-3 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                <div class="h-2 w-12 bg-gray-100 dark:bg-gray-700/60 rounded sm:hidden"></div>
                            </div>

                            <!-- Solicitante -->
                            <div class="flex flex-col gap-2">
                                <div class="h-3 w-full max-w-[110px] bg-gray-200 dark:bg-gray-700 rounded"></div>
                                <div class="h-2 w-20 bg-gray-100 dark:bg-gray-700/60 rounded"></div>
                            </div>

                            <!-- Área -->
                            <div class="hidden sm:block">
                                <div class="h-3 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
                            </div>

                            <!-- Fecha -->
                            <div class="flex flex-col gap-2">
                                <div class="h-3 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                <div class="h-2 w-12 bg-gray-100 dark:bg-gray-700/60 rounded sm:hidden"></div>
                            </div>

                            <!-- Estatus -->
                            <div class="flex justify-end sm:justify-start">
                                <div class="h-6 w-20 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Paginación / pie -->
                    <div class="flex justify-between items-center mt-4 pt-2 animate-pulse">
                        <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="flex gap-2">
                            <div class="h-7 w-7 bg-gray-200 dark:bg-gray-700 rounded-md"></div>
                            <div class="h-7 w-7 bg-gray-200 dark:bg-gray-700 rounded-md"></div>
                            <div class="h-7 w-7 bg-gray-200 dark:bg-gray-700 rounded-md"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-stretch" :class="{ 'cascade-update': cascadeUpdate }">    
                <!-- ========================================== -->
                <!-- COLUMNA IZQUIERDA (2/5 del ancho): Solo visible para roles autorizados -->
                <!-- ========================================== -->
                <div v-if="['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR'].some(role => $page.props.auth.user?.roles?.includes(role))" 
                    class="lg:col-span-2 flex flex-col gap-5 justify-between">
                    
                    <!-- Panel Izquierdo 1 (Localidades) -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group flex-1">
                        <div class="p-4 sm:p-5 w-full flex-grow">
                            <component
                                :is="panelComponents['SolicitudesByLocationPanel']"
                                :panelData="panelData['solicitudes_by_location']"
                                :userAuth="userAuth"
                            />
                        </div>
                    </div>

                    <!-- Panel Izquierdo 2 (Áreas) -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group flex-1">
                        <div class="p-4 sm:p-5 w-full flex-grow">
                            <component
                                :is="panelComponents['SolicitudesByAreaPanel']"
                                :panelData="panelData['solicitudes_by_area']"
                                :userAuth="userAuth"
                            />
                        </div>
                    </div>

                </div>

                <!-- ========================================== -->
                <!-- COLUMNA DERECHA: Ancho dinámico (lg:col-span-3 o lg:col-span-5) -->
                <!-- ========================================== -->
                <div :class="[
                        'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group',
                        {'cascade-update': cascadeUpdate},
                        ['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR'].some(role => $page.props.auth.user?.roles?.includes(role)) 
                            ? 'lg:col-span-3' 
                            : 'lg:col-span-5'
                    ]">
                    
                    <div class="p-5 sm:p-6 w-full flex-grow">
                        <component
                            :is="panelComponents['LatestSolicitudesPanel']"
                            :panelData="panelData['latest_solicitudes']"
                            :userAuth="userAuth"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</AdminLayout>
</template>




