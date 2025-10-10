<script setup>
    import { ref, onMounted, computed, onUnmounted, toRefs } from 'vue'
    import { router } from '@inertiajs/vue3'
    import SolicitudesPorPeriodo from './SolicitudesPorPeriodo.vue';

    const updateText = () => {
        const spanElement = document.getElementById('selected-option-text');
        const spanElement2 = document.getElementById('selected-option-text2');

        
        if (spanElement) 
        {
            if (window.innerWidth < 768) 
            { // Cambia el texto en pantallas pequeñas
                spanElement.textContent = 'Sele...';
                spanElement2.textContent = 'Sele...';

            } else 
            { // Regresa al texto original en pantallas grandes
                spanElement.textContent = 'Selecciona un Gráfico';
                spanElement2.textContent = 'Selecciona una Estadística';
            }
        }
    };
    
    const props = defineProps({
        periodoActual: Object,
        tipoGrafico: String,
        tipoEstadistica: String,
        fechaInicioQuery: String,
        fechaFinQuery: String,
        solicitudes: Array,
        totalSolicitudes: Number
    })

    const rangoFechasQuery = ref([])
    // const fechaInicioQuery = ref('')
    // const fechaFinQuery = ref('')
    const periodoActual = ref(props.periodoActual || {})

    // Lógica para establecer el rango de fechas al cargar el componente
    const establecerRangoInicial = () => {
        const hoy = new Date();

        // Obtenemos el año, mes y día de hoy
        const yearHoy = hoy.getFullYear();
        const monthHoy = String(hoy.getMonth() + 1).padStart(2, '0'); // Los meses van de 0 a 11, por eso se suma 1
        const dayHoy = String(hoy.getDate()).padStart(2, '0');

        // Obtenemos el año, mes y día del primer día del mes
        const primerDiaDelMes = new Date(yearHoy, hoy.getMonth(), 1);
        const yearPrimerDia = primerDiaDelMes.getFullYear();
        const monthPrimerDia = String(primerDiaDelMes.getMonth() + 1).padStart(2, '0');
        const dayPrimerDia = String(primerDiaDelMes.getDate()).padStart(2, '0');

        // Construimos las cadenas de fecha en el formato 'YYYY-MM-DD'
        const fechaHoyFormateada = `${yearHoy}-${monthHoy}-${dayHoy}`;
        const primerDiaDelMesFormateado = `${yearPrimerDia}-${monthPrimerDia}-${dayPrimerDia}`;

        // Asignamos el array de fechas formateadas a la variable reactiva
        rangoFechasQuery.value = [primerDiaDelMesFormateado, fechaHoyFormateada];
    };

    const options = [
            { id: '1', name: 'Barras', svg: ' <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"><path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75ZM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 0 1-1.875-1.875V8.625ZM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 0 1 3 19.875v-6.75Z" /></svg>' },
            // { id: '2', name: 'Pastel', svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"><path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 0 1 8.25-8.25.75.75 0 0 1 .75.75v6.75H18a.75.75 0 0 1 .75.75 8.25 8.25 0 0 1-16.5 0Z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M12.75 3a.75.75 0 0 1 .75-.75 8.25 8.25 0 0 1 8.25 8.25.75.75 0 0 1-.75.75h-7.5a.75.75 0 0 1-.75-.75V3Z" clip-rule="evenodd" /></svg>' },
            { id: '3', name: 'Dona', svg: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M441-82Q287-97 184-211T81-480q0-155 103-269t257-129v120q-104 14-172 93t-68 185q0 106 68 185t172 93v120Zm80 0v-120q94-12 159-78t79-160h120q-14 143-114.5 243.5T521-82Zm238-438q-14-94-79-160t-159-78v-120q143 14 243.5 114.5T879-520H759Z"/></svg>' },
            { id: '4', name: 'Líneas', svg: '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M120-240q-33 0-56.5-23.5T40-320q0-33 23.5-56.5T120-400h10.5q4.5 0 9.5 2l182-182q-2-5-2-9.5V-600q0-33 23.5-56.5T400-680q33 0 56.5 23.5T480-600q0 2-2 20l102 102q5-2 9.5-2h21q4.5 0 9.5 2l142-142q-2-5-2-9.5V-640q0-33 23.5-56.5T840-720q33 0 56.5 23.5T920-640q0 33-23.5 56.5T840-560h-10.5q-4.5 0-9.5-2L678-420q2 5 2 9.5v10.5q0 33-23.5 56.5T600-320q-33 0-56.5-23.5T520-400v-10.5q0-4.5 2-9.5L420-522q-5 2-9.5 2H400q-2 0-20-2L198-340q2 5 2 9.5v10.5q0 33-23.5 56.5T120-240Z"/></svg>' },
            { id: '5', name: 'Radial', svg: '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-chart-radar"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l9.5 7l-3.5 11h-12l-3.5 -11z" /><path d="M12 7.5l5.5 4l-2.5 5.5h-6.5l-2 -5.5z" /><path d="M2.5 10l9.5 3l9.5 -3" /><path d="M12 3v10l6 8" /><path d="M6 21l6 -8" /></svg>' },
        ];

        const estadisticas = [
            { id: '1', name: 'Solicitudes Por Periodo', value: 'solicitudes_por_periodo' },
            { id: '2', name: 'Trámites más Solicitados', value: 'tramites_mas_solicitados' },
            { id: '3', name: 'Solicitudes Por Tipo', value: 'solicitudes_por_tipo' },
            { id: '4', name: 'Solicitudes Por Usuario', value: 'solicitudes_por_usuario' },
            { id: '5', name: 'Solicitudes Por Prioridad', value: 'solicitudes_por_prioridad' },
            { id: '6', name: 'Tiempos de Respuesta', value: 'tiempos_de_respuesta' },
            { id: '7', name: 'Satisfacción del Cliente', value: 'satisfaccion_del_cliente' },
            { id: '8', name: 'Análisis de Tendencias', value: 'analisis_de_tendencias' },
            { id: '9', name: 'Rendimiento del Equipo', value: 'rendimiento_del_equipo' },
            { id: '10', name: 'Costos Operativos', value: 'costos_operativos' },
        ]
        

   onMounted(() => {
        establecerRangoInicial()

        document.addEventListener('click', closeDropdown);
        document.addEventListener('click', closeDropdown2);

        tipoGrafico.value = 1
        tipoEstadistica.value = 2

        selectOption(options[tipoGrafico.value-1])
        selectOption2(tipoEstadistica.value-1)

        window.addEventListener('resize', updateText);
    })

    const shortcuts = computed(() => {
        // Shortcuts estáticos que siempre van al inicio
        const staticShortcuts = [
            // {
            //     id: 10,
            //     text: 'Últ. Año',
            //     value: () => {
            //         const end = new Date();
            //         const start = new Date();
            //         start.setFullYear(start.getFullYear() - 1);
            //         return [start, end];
            //     },
            // },
        ];

        if (!periodoActual.value) {
            return staticShortcuts;
        }

        // Mapa para agrupar los shortcuts por año.
        const groupedShortcuts = new Map();

        const monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        const quarterStartMonths = { 'Q1': 0, 'Q2': 3, 'Q3': 6, 'Q4': 9 };
        const quarterNames = ['Trim 1', 'Trim 2', 'Trim 3', 'Trim 4'];

        const inicioPeriodo = new Date(periodoActual.value.inicio + 'T00:00:00');
        const today = new Date();

        // Función auxiliar para agregar un shortcut al grupo de su año
        const addShortcut = (shortcut) => {
            const year = shortcut.year;
            if (!groupedShortcuts.has(year)) {
                groupedShortcuts.set(year, []);
            }
            groupedShortcuts.get(year).push(shortcut);
        };

        // --- Generar shortcuts mensuales ---
        let monthlyDate = new Date(today.getFullYear(), today.getMonth(), 1);
        while (monthlyDate >= inicioPeriodo) {
            const startOfMonth = new Date(monthlyDate.getFullYear(), monthlyDate.getMonth(), 1);
            const endOfMonth = new Date(monthlyDate.getFullYear(), monthlyDate.getMonth() + 1, 0);

            addShortcut({
                type: 'monthly',
                year: monthlyDate.getFullYear(),
                month: monthlyDate.getMonth(),
                text: `${monthlyDate.getFullYear().toString()} - ${monthNames[monthlyDate.getMonth()]}`,
                value: () => [startOfMonth, endOfMonth],
            });
            monthlyDate.setMonth(monthlyDate.getMonth() - 1);
        }

        // --- Generar shortcuts trimestrales ---
        let startQuarterMonth = inicioPeriodo.getMonth();
        let startQuarterYear = inicioPeriodo.getFullYear();
        if (startQuarterMonth < quarterStartMonths['Q4']) {
            if (startQuarterMonth < quarterStartMonths['Q3']) {
                if (startQuarterMonth < quarterStartMonths['Q2']) {
                    startQuarterMonth = quarterStartMonths['Q1'];
                } else {
                    startQuarterMonth = quarterStartMonths['Q2'];
                }
            } else {
                startQuarterMonth = quarterStartMonths['Q3'];
            }
        } else {
            startQuarterMonth = quarterStartMonths['Q4'];
        }

        let currentQuarterDate = new Date(startQuarterYear, startQuarterMonth, 1);
        
        while (currentQuarterDate <= today) {
            const month = currentQuarterDate.getMonth();
            const quarterIndex = Math.floor(month / 3);
            const startOfQuarter = new Date(currentQuarterDate.getFullYear(), month, 1);
            const endOfQuarter = new Date(startOfQuarter);
            endOfQuarter.setMonth(endOfQuarter.getMonth() + 3);
            endOfQuarter.setDate(endOfQuarter.getDate() - 1);

            const finalStart = startOfQuarter < inicioPeriodo ? inicioPeriodo : startOfQuarter;

            addShortcut({
                type: 'quarterly',
                year: currentQuarterDate.getFullYear(),
                quarter: quarterIndex,
                text: `${currentQuarterDate.getFullYear().toString()} - ${quarterNames[quarterIndex]}`,
                value: () => [finalStart, endOfQuarter],
            });

            currentQuarterDate.setMonth(currentQuarterDate.getMonth() + 3);
        }

        // --- Ordenar, aplanar y agregar el atajo de año ---
        let finalShortcuts = [];
        const sortedYears = [...groupedShortcuts.keys()].sort((a, b) => b - a);

        sortedYears.forEach(year => {
            // Agregamos un atajo para el año completo
            finalShortcuts.push({
                type: 'year',
                text: `${year}`,
                value: () => {
                    const startOfYear = new Date(year, 0, 1);
                    const endOfYear = new Date(year, 11, 31);
                    // Si el año es el primero del periodo, ajustamos el inicio
                    const finalStart = startOfYear < inicioPeriodo ? inicioPeriodo : startOfYear;
                    return [finalStart, endOfYear];
                },
            });

            const yearShortcuts = groupedShortcuts.get(year);
            const quarterly = yearShortcuts.filter(sc => sc.type === 'quarterly');
            const monthly = yearShortcuts.filter(sc => sc.type === 'monthly');
            quarterly.sort((a, b) => b.quarter - a.quarter);
            monthly.sort((a, b) => b.month - a.month);
            finalShortcuts = finalShortcuts.concat(monthly, quarterly);
        });

        // Mapeamos a la estructura final y agregamos IDs
        finalShortcuts = finalShortcuts.map((sc, index) => {
            return {
                id: `sc-${sc.year}-${sc.type}-${index}`,
                text: sc.text,
                value: sc.value,
            };
        });
        
            return [...staticShortcuts, ...finalShortcuts];
    });

      const isDropdownOpen = ref(false);
      const isDropdownOpen2 = ref(false);
      const dropdownRef = ref(null);
      const dropdownRef2 = ref(null);
      const isLoading = ref(false)
      const tipoGrafico = ref('')
      const tipoEstadistica = ref('')      

        function selectOption(option) 
        {
            document.getElementById('selected-option-text').innerHTML = option.svg
            tipoGrafico.value = option.id

            fetchEstadisticas(true);

            isDropdownOpen.value = false;
        }

        function selectOption2(index) 
        {
            document.getElementById('selected-option-text2').textContent = estadisticas[index].name;
            tipoEstadistica.value = estadisticas[index].id

            fetchEstadisticas(true);

            isDropdownOpen2.value = false;
        }

        // Funciones
        const toggleDropdown = () => {
            isDropdownOpen.value = !isDropdownOpen.value;
        };

        const closeDropdown = (event) => {
            if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
                isDropdownOpen.value = false;
            }
        };

        const toggleDropdown2 = () => {
            isDropdownOpen2.value = !isDropdownOpen2.value;
        };

        const closeDropdown2 = (event) => {
            if (dropdownRef2.value && !dropdownRef2.value.contains(event.target)) {
                isDropdownOpen2.value = false;
            }
        };

        onUnmounted(() => {
            document.removeEventListener('click', closeDropdown);
            document.removeEventListener('click', closeDropdown2);

            window.removeEventListener('resize', updateText);
        });

        function fetchEstadisticas(onMounted, polling = false) 
        {
            let showLoaderTimeout = null

            if (onMounted) 
            {
                //Si está cargando al montar el componente
                isLoading.value = true //Muestra la pantalla de Cargando...
            } 

            const filtros = {
                tipoGrafico: tipoGrafico.value,
                tipoEstadistica: tipoEstadistica.value,
                fechaInicioQuery: rangoFechasQuery.value[0] || '',
                fechaFinQuery: rangoFechasQuery.value[1] || '',
                solicitudes: props.solicitudes
            }

            const formData = new FormData()

            for (const key in filtros) {
                const value = filtros[key]

                // Si es un array, agregar cada elemento con el mismo nombre
                if (Array.isArray(value)) {
                    value.forEach((item, index) => {
                        formData.append(`${key}[${index}]`, item)
                    })
                } else {
                    formData.append(key, value ?? '')
                }
            }

            // Realiza la solicitud GET con los parámetros de búsqueda
            router.post(
                '/estadisticas',
                formData,
                {
                    preserveState: true,
                    replace: true,
                    onFinish: () => {
                        if (onMounted) 
                        {
                            isLoading.value = false
                        } 
                        else 
                        {
                            clearTimeout(showLoaderTimeout)
                        }
                    }
                }
            )
        }

        const requestsByDayOfWeek = computed(() => {
            // Si no hay datos, retorna un objeto vacío o un valor por defecto
            if (!props.solicitudes || props.solicitudes.length === 0) {
                return {};
            }

            const dayTotals = {
                'Lunes': 0,
                'Martes': 0,
                'Miércoles': 0,
                'Jueves': 0,
                'Viernes': 0,
                'Sábado': 0,
                'Domingo': 0
            };

            // Recorre cada solicitud y suma al día correspondiente
            props.solicitudes.forEach(solicitud => {
                // Obtiene el nombre del día a partir de la fecha
                const date = new Date(solicitud.year, solicitud.month - 1, solicitud.day);
                const dayName = new Intl.DateTimeFormat('es-ES', { weekday: 'long' }).format(date);
                
                // El formato de la primera letra debe ser mayúscula
                const capitalizedDayName = dayName.charAt(0).toUpperCase() + dayName.slice(1);
                
                if (dayTotals[capitalizedDayName] !== undefined) {
                    dayTotals[capitalizedDayName] += solicitud.total;
                }
            });

            return dayTotals;
        });
</script>

<template>
    <section class="bg-gray-50 dark:bg-gray-900 p-3 sm:p-5 h-screen overflow-hidden flex flex-col">
        <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity">
            <div class="flex items-center">
                <svg class="animate-spin h-8 w-8 text-color1-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-gray-300">Cargando...</span>
            </div>
        </div>
        <div class="mx-auto max-w-screen-xl lg:px-0 w-[100%] sm:w-[100%] md:w-[100%] lg:w-[100%] mt-20">
            <h1 class="text-2xl font-bold">Estadísticas </h1>
        </div>  
        <br/>    
        <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden flex flex-col flex-grow">
            <div class="flex flex-col md:flex-row md:items-center md:justify-start p-2">
                <div class="w-full md:w-[250px] py-2">
                    <el-date-picker
                        v-model="rangoFechasQuery"
                        type="daterange"
                        unlink-panels
                        range-separator="-"
                        start-placeholder="Fecha Inicial"
                        end-placeholder="Fecha Final"
                        :shortcuts="shortcuts"
                        class="custom-date-picker w-full"
                        value-format="YYYY-MM-DD"
                        format="DD/MM/YYYY"
                        @change="fetchEstadisticas"/>
                </div>
                <form class="w-full md:w-auto">
                    <div class="flex w-full space-x-2 md:w-auto md:flex-none">
                        <div class="relative flex-1 md:w-auto md:flex-none">
                            <button @click.prevent.stop="toggleDropdown2" id="states-button"  class="md:ml-2 h-[38px] w-full shrink-0 z-10 inline-flex justify-between items-center px-2 text-[15px] font-normal text-center text-gray-500 border border-gray-300 rounded-s-lg hover:bg-gray-200 focus:ring-1 focus:ring-color1-500 focus:border-color1-500 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700 dark:text-white dark:border-gray-600" type="button">
                                <div class="flex items-center text-color1-700">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    v-if="!tipoEstadistica"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-chart-candle mr-0.5">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 18l-2 -4l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5l-3.328 9.217" /><path d="M19 16v6" /><path d="M22 19l-3 3l-3 -3" />
                                    </svg>
                                    <span id="selected-option-text2" class="ml-2 text-color3-600">
                                        Selecciona una Estadística
                                    </span>
                                </div>
                                <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                </svg>
                            </button>

                            <div id="dropdown-states" ref="dropdownRef2" v-if="isDropdownOpen2" class="absolute top-full mt-1 left-0 z-20 bg-white divide-y divide-gray-100 rounded-lg shadow-xl w-56 dark:bg-gray-700">
                                <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                                    <li v-for="(estadistica, index) in estadisticas" :key="index">
                                        <a 
                                            href="#" 
                                            class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                                            @click.prevent="selectOption2(index)">
                                            <div class="me-2"></div>
                                            {{ estadistica.name }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="relative flex-1 md:w-auto md:flex-none">
                            <button @click.prevent.stop="toggleDropdown" id="states-button"  class="h-[38px] w-full shrink-0 z-10 inline-flex items-center justify-between px-2 text-[15px] font-normal text-center text-gray-500 bg-gray-100 border border-gray-300 rounded-e-lg hover:bg-gray-200 focus:ring-1 focus:ring-color1-500 focus:border-color1-500 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700 dark:text-white dark:border-gray-600" type="button">
                                <div class="flex items-center text-color1-700">
                                <svg 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    width="16" 
                                    height="16" 
                                    viewBox="0 0 24 24" 
                                    fill="none" 
                                    stroke="currentColor" 
                                    stroke-width="2" 
                                    stroke-linecap="round" 
                                    stroke-linejoin="round"
                                    v-if="!tipoGrafico"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-chart-bubble mr-0.5">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M6 16m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                    <path d="M16 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                    <path d="M14.5 7.5m-4.5 0a4.5 4.5 0 1 0 9 0a4.5 4.5 0 1 0 -9 0" />
                                </svg>
                                <span id="selected-option-text" class="ml-2 text-color3-600">
                                    Selecciona un Gráfico
                                </span>
                                </div>
                                <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                </svg>
                            </button>

                            <div id="dropdown-states" ref="dropdownRef" v-if="isDropdownOpen" class="absolute top-full mt-1 left-0 z-20 bg-white divide-y divide-gray-100 rounded-lg shadow-xl w-28 dark:bg-gray-700">
                                <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                                    <li v-for="(option, index) in options" :key="index">
                                        <a 
                                            href="#" 
                                            class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                                            @click.prevent="selectOption(option)">
                                            <div v-html="option.svg" class="me-2"></div>
                                            {{ option.name }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>             
                    </div>
                </form>
            </div>
            <div class="flex-grow flex flex-col overflow-hidden px-10">
                <div class="flex-grow flex flex-col p-2">
                    <SolicitudesPorPeriodo v-if="props.solicitudes && props.solicitudes.length > 0" :solicitudes="props.solicitudes" />
                    <div v-else>
                        Cargando datos del gráfico...
                    </div>
                </div>
            </div>
            <div class="inline-flex items-center gap-x-1 bg-gray-100 px-2 py-1 text-sm font-medium text-gray-600 ring-1 ring-inset ring-gray-200">
                <div class="flex items-center ml-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-text-scan-2 h-5 w-5">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M4 8v-2a2 2 0 0 1 2 -2h2" />
                        <path d="M4 16v2a2 2 0 0 0 2 2h2" />
                        <path d="M16 4h2a2 2 0 0 1 2 2v2" />
                        <path d="M16 20h2a2 2 0 0 0 2 -2v-2" />
                        <path d="M8 12h8" />
                        <path d="M8 9h6" />
                        <path d="M8 15h4" />
                    </svg>
                    <div class="ml-2">
                        Total de Solicitudes: <strong> {{ totalSolicitudes }} </strong>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>