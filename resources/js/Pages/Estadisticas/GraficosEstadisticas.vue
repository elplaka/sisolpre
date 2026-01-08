<script setup>
    import { ref, onMounted, computed, onUnmounted, watch, nextTick } from 'vue'
    import { router } from '@inertiajs/vue3'
    import SolicitudesPorPeriodo from './SolicitudesPorPeriodo.vue';
    import SolicitudesPorPeriodoTable from './SolicitudesPorPeriodoTable.vue';
    import TramitesMasSolicitados from './TramitesMasSolicitados.vue';
    import TramitesMasSolicitadosTable from './TramitesMasSolicitadosTable.vue';
    import TiposPropiedadRequeridos from './TiposPropiedadRequeridos.vue';
    import TiposPropiedadRequeridosTable from './TiposPropiedadRequeridosTable.vue';
    import LocalidadesMasSolicitadas from './LocalidadesMasSolicitadas.vue';
    import LocalidadesMasSolicitadasTable from './LocalidadesMasSolicitadasTable.vue';

    const updateText = () => {
        const spanElement = document.getElementById('selected-option-text');
        // const spanElement2 = document.getElementById('selected-option-text2');

        
        if (spanElement) 
        {
            if (window.innerWidth < 768) 
            { // Cambia el texto en pantallas pequeñas
                if (!tipoEstadistica) spanElement.textContent = 'Sele...';
                // spanElement2.textContent = 'Sele...';

            } else 
            { // Regresa al texto original en pantallas grandes
                if (!tipoEstadistica) spanElement.textContent = 'Selecciona un Gráfico';
                // spanElement2.textContent = 'Selecciona una Estadística';
            }
        }
    };
    
    const props = defineProps({
        periodoActual: Object,
        tipoGrafico: String,
        tipoEstadistica: String,
        fechaInicioQuery: String,
        fechaFinQuery: String,
        solicitudesPorPeriodo: Array,
        totalSolicitudesPorPeriodo: Number,
        agruparPorDia: Boolean,
        tramitesMasSolicitados: Array,
        totalTramitesMasSolicitados: Number,
        elementosRanking: Number,
        tiposPropiedad: Array,
        localidadesMasSolicitadas: Array,
        vieneDeDashboard: {
            type: Boolean,
            default: false,
        },
    })

    const rangoFechasQuery = ref([])
    const rangoFechasManual = ref(false)
    // const fechaInicioQuery = ref('')
    // const fechaFinQuery = ref('')
    const periodoActual = ref(props.periodoActual || {})
    const estadisticaName = ref('')
    const estadisticaLabel = ref('')
    const inicializarRango = ref(false)
    const verTabla = ref(false)

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

        inicializarRango.value = false
    };

    const graficos = [
            { id: '1', name: 'Barras', svg: '<svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor" class="size-6"><path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75ZM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 0 1-1.875-1.875V8.625ZM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 0 1 3 19.875v-6.75Z" /></svg>' },
            // { id: '2', name: 'Pastel', svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"><path fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 0 1 8.25-8.25.75.75 0 0 1 .75.75v6.75H18a.75.75 0 0 1 .75.75 8.25 8.25 0 0 1-16.5 0Z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M12.75 3a.75.75 0 0 1 .75-.75 8.25 8.25 0 0 1 8.25 8.25.75.75 0 0 1-.75.75h-7.5a.75.75 0 0 1-.75-.75V3Z" clip-rule="evenodd" /></svg>' },
            { id: '3', name: 'Dona', svg: '<svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 -960 960 960" width="20px" fill="currentColor"><path d="M441-82Q287-97 184-211T81-480q0-155 103-269t257-129v120q-104 14-172 93t-68 185q0 106 68 185t172 93v120Zm80 0v-120q94-12 159-78t79-160h120q-14 143-114.5 243.5T521-82Zm238-438q-14-94-79-160t-159-78v-120q143 14 243.5 114.5T879-520H759Z"/></svg>' },
            { id: '4', name: 'Líneas', svg: '<svg xmlns="http://www.w3.org/2000/svg" class="size-5" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="M120-240q-33 0-56.5-23.5T40-320q0-33 23.5-56.5T120-400h10.5q4.5 0 9.5 2l182-182q-2-5-2-9.5V-600q0-33 23.5-56.5T400-680q33 0 56.5 23.5T480-600q0 2-2 20l102 102q5-2 9.5-2h21q4.5 0 9.5 2l142-142q-2-5-2-9.5V-640q0-33 23.5-56.5T840-720q33 0 56.5 23.5T920-640q0 33-23.5 56.5T840-560h-10.5q-4.5 0-9.5-2L678-420q2 5 2 9.5v10.5q0 33-23.5 56.5T600-320q-33 0-56.5-23.5T520-400v-10.5q0-4.5 2-9.5L420-522q-5 2-9.5 2H400q-2 0-20-2L198-340q2 5 2 9.5v10.5q0 33-23.5 56.5T120-240Z"/></svg>' },
            { id: '5', name: 'Radial', svg: '<svg  xmlns="http://www.w3.org/2000/svg"  class="size-5" viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="1.5"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-chart-radar"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l9.5 7l-3.5 11h-12l-3.5 -11z" /><path d="M12 7.5l5.5 4l-2.5 5.5h-6.5l-2 -5.5z" /><path d="M2.5 10l9.5 3l9.5 -3" /><path d="M12 3v10l6 8" /><path d="M6 21l6 -8" /></svg>' },
    ];

    const estadisticas_graficos = [
        { 
            estadisticaId: '1', 
            estadisticaName: 'Solicitudes por periodo',
            estadisticaLabel: 'Solicitudes',
            graficos: ['4'] // Líneas
        },
        { 
            estadisticaId: '2', 
            estadisticaName: 'Trámites más solicitados',
            estadisticaLabel: 'Trámites más solicitados',
            graficos: ['1'] // Barras
        },
        { 
            estadisticaId: '3', 
            estadisticaName: 'Tipos de propiedad requeridos',
            estadisticaLabel: 'Tipos de propiedad requeridos',
            graficos: ['3'] // Dona
        },
        { 
            estadisticaId: '4', 
            estadisticaName: 'Localidades con mayor demanda',
            estadisticaLabel: 'Localidades con mayor demanda',
            graficos: ['5'] // Dona
        },
    ];

    const selectedOption = ref('')

    function selectOption(option) 
    {
        if (selectedOption.value && selectedOption.value.estadisticaId === option.estadisticaId) {
            toggleDropdown();
            return; 
        }

        if (!option)
        {
            return;
        }

        selectedOption.value = option;

        const svgGrafico = graficos.find(grafico => grafico.id === option?.graficos[0]);
        document.getElementById('selected-option-svg').innerHTML = svgGrafico?.svg
        document.getElementById('selected-option-text').innerHTML = option?.estadisticaName


        estadisticaLabel.value = option.estadisticaLabel
        if (rangoFechasManual.value)
        {
            estadisticaName.value = option.estadisticaLabel + ' entre ' + selectedShortcutDescription.value
        }
        else
        {
            estadisticaName.value = option.estadisticaLabel + ' en ' + selectedShortcutDescription.value
        }

        tipoEstadistica.value = option.estadisticaId

        fetchEstadisticas(true);

        isDropdownOpen.value = false;
    }

   onMounted(() => {
        establecerRangoInicial()

        if (props.fechaInicioQuery != null)   //Si se abrió la estadísticas desde los paneles
        { 
            rangoFechasQuery.value[0] = props.fechaInicioQuery
            rangoFechasQuery.value[1] = props.fechaFinQuery

            tipoEstadistica.value = props.tipoEstadistica

            selectOption(estadisticas_graficos[tipoEstadistica.value])
        }

        document.addEventListener('click', closeDropdown);

        window.addEventListener('resize', updateText);

        inicializarRango.value = false
    })

    const selectedShortcutDescription = ref('General');

    watch(rangoFechasQuery, (newRange) => {
        // 1. Manejar casos donde el rango sea nulo, indefinido o vacío
        if (!newRange || newRange.length === 0) {
            if (inicializarRango.value) 
            {
                selectedShortcutDescription.value = 'General';
                rangoFechasManual.value = false 
                return;         
            }
            else 
            {
                selectedShortcutDescription.value = 'el Mes Actual';
                inicializarRango.value = true
                rangoFechasManual.value = false 
                return;
            }
        }

        // 2. Convertir las cadenas a objetos Date, ahora correctamente como UTC
        const start = new Date(newRange[0] + 'T00:00:00.000Z');
        const end = new Date(newRange[1] + 'T00:00:00.000Z');

        if (isNaN(start.getTime()) || isNaN(end.getTime())) {
            selectedShortcutDescription.value = '-----';
            return;
        }
        
        // 3. Encontrar el atajo coincidente comparando componentes de fecha en UTC
        const matchedShortcut = shortcuts.value.find(shortcut => {
            const shortcutRange = shortcut.value();

            if (!Array.isArray(shortcutRange) || shortcutRange.length < 2) {
                return false;
            }

            const shortcutRangeStart = shortcutRange[0];
            const shortcutRangeEnd = shortcutRange[1];

            // Usar los métodos UTC para la comparación
            const startMatches = start.getUTCFullYear() === shortcutRangeStart.getUTCFullYear() &&
                                start.getUTCMonth() === shortcutRangeStart.getUTCMonth() &&
                                start.getUTCDate() === shortcutRangeStart.getUTCDate();

            const endMatches = end.getUTCFullYear() === shortcutRangeEnd.getUTCFullYear() &&
                            end.getUTCMonth() === shortcutRangeEnd.getUTCMonth() &&
                            end.getUTCDate() === shortcutRangeEnd.getUTCDate();

            return startMatches && endMatches;
        });

        // 4. Actualizar la descripción
        if (matchedShortcut) 
        {
            selectedShortcutDescription.value = matchedShortcut.descripcion;
            rangoFechasManual.value = false 
        } 
        else 
        {
            if (inicializarRango.value) 
            {
                const fechaInicioFormateada = formatDate(rangoFechasQuery.value[0]);
                const fechaFinFormateada = formatDate(rangoFechasQuery.value[1]);
                selectedShortcutDescription.value =  fechaInicioFormateada + ' y ' + fechaFinFormateada;
                rangoFechasManual.value = true // 'Rango personalizado';
            }
            else 
            {
                selectedShortcutDescription.value = 'el Mes Actual';
                inicializarRango.value = true
                rangoFechasManual.value = false 
            }
        }
    }, { immediate: true });

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
        const fullMonthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']; // 🎯 Nuevo array de nombres completos
        const quarterStartMonths = { 'Q1': 0, 'Q2': 3, 'Q3': 6, 'Q4': 9 };
        const quarterNames = ['Trim 1', 'Trim 2', 'Trim 3', 'Trim 4'];
        const fullQuarterNames = ['Primer', 'Segundo', 'Tercer', 'Cuarto'];

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
                year: year, // 🎯 Asegúrate de agregar esta línea para que 'year' esté disponible.
                text: `${year}`,
                value: () => {
                    const startOfYear = new Date(year, 0, 1);
                    const endOfYear = new Date(year, 11, 31);
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

        // Mapeamos a la estructura final y agregamos IDs y descripciones
        finalShortcuts = finalShortcuts.map((sc, index) => {
            let descripcion = '';
            // Genera la descripción basada en el tipo de atajo
            switch (sc.type) {
                case 'year':
                    // Ahora 'sc.year' estará definido correctamente
                    descripcion = `${sc.year}`;
                    break;
                case 'monthly':
                    descripcion = `${fullMonthNames[sc.month]} de ${sc.year}`;
                    break;
                case 'quarterly':
                    descripcion = `el ${fullQuarterNames[sc.quarter]} Trimestre de ${sc.year}`;
                    break;
                default:
                    descripcion = sc.text;
            }
            return {
                id: `sc-${sc.year}-${sc.type}-${index}`,
                text: sc.text,
                value: sc.value,
                descripcion: descripcion,
            };
        });

        return [...staticShortcuts, ...finalShortcuts];
    });

      const isDropdownOpen = ref(false);
      const dropdownRef = ref(null);
      const isLoading = ref(false)
      const tipoGrafico = ref('')
      const tipoEstadistica = ref('') 
      const elementosRanking = ref(props.elementosRanking || 3)
      const estadisticasCargadas = ref(true)

        // Funciones
        const toggleDropdown = () => {
            isDropdownOpen.value = !isDropdownOpen.value;
        };

        const closeDropdown = (event) => {
            if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
                isDropdownOpen.value = false;
            }
        };

        onUnmounted(() => {
            document.removeEventListener('click', closeDropdown);

            window.removeEventListener('resize', updateText);
        });

        async function fetchEstadisticas(onMounted, inputDate = false) 
        {
            if (rangoFechasQuery.value === null) {
                establecerRangoInicial();

                // Esperamos a que Vue actualice el DOM y el valor
                await nextTick();
            }

            let showLoaderTimeout = null

            if (inputDate) estadisticasCargadas.value = true
            else estadisticasCargadas.value = false

            // isLoading.value = true //Muestra la pantalla de Cargando...

            if (onMounted) 
            {
                //Si está cargando al montar el componente
                // isLoading.value = true //Muestra la pantalla de Cargando...
            } 

            const filtros = {
                tipoGrafico: tipoGrafico.value,
                tipoEstadistica: tipoEstadistica.value,
                fechaInicioQuery: rangoFechasQuery.value[0] || '',
                fechaFinQuery: rangoFechasQuery.value[1] || '',
                solicitudes: props.solicitudesPorPeriodo,
                tramitesMasSolicitados : props.tramitesMasSolicitados,
                elementosRanking: elementosRanking.value,
                tiposPropiedad: props.tiposPropiedad,
                localidadesMasSolicitadas: props.localidadesMasSolicitadas
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

            if (props.vieneDeDashboard)
            {
                // Realiza la solicitud GET con los parámetros de búsqueda
                router.post(
                    '/estadisticas',
                    formData,
                    {
                        preserveState: true,
                        replace: true,
                        onStart: () => { // <--- Inicia el estado de carga
                            setTimeout(() => {
                                isLoading.value = true;
                                // Aquí iría la lógica para cargar los datos de los gráficos
                            }, 100); // 1.5 segundos de retraso simulado
                        },
                        onSuccess: () => { // <--- Inicia el estado de carga
                            isLoading.value = false
                        }, 
                        onFinish: () => {
                            if (onMounted) 
                            {
                                isLoading.value = false
                                estadisticasCargadas.value = true
                                if (estadisticaLabel.value) 
                                {
                                    if (rangoFechasManual.value)
                                    {
                                        estadisticaName.value = estadisticaLabel.value + ' entre ' + selectedShortcutDescription.value
                                    }
                                    else
                                    {
                                        estadisticaName.value = estadisticaLabel.value + ' en ' + selectedShortcutDescription.value
                                    }
                                }
                            } 
                            else 
                            {
                                clearTimeout(showLoaderTimeout)
                            }
                        },
                    }
                )
            }
            else 
            {
                // Realiza la solicitud GET con los parámetros de búsqueda
                router.post(
                    '/estadisticas',
                    formData,
                    {
                        preserveState: true,
                        replace: true,
                        onStart: () => { // <--- Inicia el estado de carga
                            setTimeout(() => {
                                // isLoading.value = true;
                                // Aquí iría la lógica para cargar los datos de los gráficos
                            }, 100); // 1.5 segundos de retraso simulado
                        },
                        onSuccess: () => { // <--- Inicia el estado de carga
                            isLoading.value = false
                        }, 
                        onFinish: () => {
                            if (onMounted) 
                            {
                                isLoading.value = false
                                estadisticasCargadas.value = true
                                if (estadisticaLabel.value) 
                                {
                                    if (rangoFechasManual.value)
                                    {
                                        estadisticaName.value = estadisticaLabel.value + ' entre ' + selectedShortcutDescription.value
                                    }
                                    else
                                    {
                                        estadisticaName.value = estadisticaLabel.value + ' en ' + selectedShortcutDescription.value
                                    }
                                }
                            } 
                            else 
                            {
                                clearTimeout(showLoaderTimeout)
                            }
                        },
                    }
                )
            }
        }

    function svgEstadistica(id) {
        const grafico = graficos.find(g => g.id === id);
        return grafico ? grafico.svg : ''; // Retorna el SVG o una cadena vacía si no se encuentra
    }

    watch(elementosRanking, () => {
        if (elementosRanking.value < 2) {
            elementosRanking.value = 2;
        } else if (elementosRanking.value > 10) {
            elementosRanking.value = 10;
        }
    });

    const formatDate = (dateString) => {
    if (!dateString) {
            return '';
        }
        // Divide la cadena 'yyyy-MM-dd' en sus componentes
        const [year, month, day] = dateString.split('-');
        // Reorganiza los componentes al formato 'dd/MM/yyyy'
        return `${day}/${month}/${year}`;
    };
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
            <div class="flex items-center space-x-4">
                <h1 class="text-2xl font-bold">Estadísticas </h1>
            </div>
        </div>
        <br>        
        <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden flex flex-col flex-grow">
            <div class="flex flex-col md:flex-row md:items-center md:justify-start p-2">
                <div class="w-full md:w-[250px]">
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
                        @change="fetchEstadisticas(true, true)"/>
                </div>
                <form class="w-full md:w-auto">
                    <div class="flex w-full space-x-2 md:w-auto md:flex-none">
                        <div class="relative flex-1 md:w-auto md:flex-none">
                            <!-- <button @click.prevent.stop="toggleDropdown" id="states-button"  class="h-[38px] w-full shrink-0 z-10 inline-flex items-center justify-between px-2 text-[15px] font-normal text-center text-gray-500 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 focus:ring-1 focus:ring-color1-500 focus:border-color1-500 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700 dark:text-white dark:border-gray-600" type="button"> -->
                           <button @click.prevent.stop="toggleDropdown" id="states-button" :class="{ 'ring-1 ring-color1-500 border-color1-500': isDropdownOpen }" class="h-[38px] md:ml-2 w-full mt-2 md:mt-0 shrink-0 z-10 inline-flex items-center justify-between px-2 text-[15px] font-normal text-center text-gray-500 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700 dark:text-white dark:border-gray-600" type="button"> 
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
                                    v-if="!tipoEstadistica"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-chart-bubble mr-0.5">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M6 16m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                    <path d="M16 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                    <path d="M14.5 7.5m-4.5 0a4.5 4.5 0 1 0 9 0a4.5 4.5 0 1 0 -9 0" />
                                </svg>
                                <span id="selected-option-svg" class="flex items-center ml-2 text-color1-700 transform scale-x-[-1]">
                                </span>
                                <span id="selected-option-text" class="flex items-center ml-2 mr-2 text-color3-600">
                                    Selecciona una opción
                                </span>
                                </div>
                                <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                </svg>
                            </button>

                            <div id="dropdown-states" ref="dropdownRef" v-if="isDropdownOpen" class="absolute top-full mt-1 left-0 z-20 bg-white divide-y divide-gray-100 rounded-lg shadow-xl w-72 dark:bg-gray-700">
                                <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                                    <li v-for="(option, index) in estadisticas_graficos" :key="index">
                                        <a 
                                            href="#" 
                                            class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                                            @click.prevent="selectOption(option)">
                                            <div v-html="svgEstadistica(option.graficos[0])" class="me-2 transform scale-x-[-1]"></div>
                                            {{ option.estadisticaName }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>             
                    </div>
                </form>
                <div v-if="tipoEstadistica == '2'" class="md:ml-3 mt-2 md:mt-0 relative">
                    <el-tooltip content="Elementos del Ranking" placement="top" popper-class="dark-tooltip">
                    <input
                        type="number"
                        id="elementos_ranking"
                        class="custom-input pl-10 block w-full md:w-20 p-2 text-sm text-gray-700 border border-gray-300 rounded-lg bg-gray-50 focus:ring-color1-500 focus:border-color1-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500"
                        v-model="elementosRanking"
                        min="2"
                        max="10"
                        @input="fetchEstadisticas(true, true)"/>
                    </el-tooltip>
                    
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-color1-700">
                        <svg xmlns="http://www.w3.org/2000/svg" height="18px" stroke="currentColor"  stroke-width="0.25" viewBox="0 -960 960 960" width="24px"  fill="currentColor">
                            <path d="M480-160q75 0 127.5-52.5T660-340q0-75-52.5-127.5T480-520q-75 0-127.5 52.5T300-340q0 75 52.5 127.5T480-160ZM363-572q20-11 42.5-17.5T451-598L350-800H250l113 228Zm234 0 114-228H610l-85 170 19 38q14 4 27 8.5t26 11.5ZM256-208q-17-29-26.5-62.5T220-340q0-36 9.5-69.5T256-472q-42 14-69 49.5T160-340q0 47 27 82.5t69 49.5Zm448 0q42-14 69-49.5t27-82.5q0-47-27-82.5T704-472q17 29 26.5 62.5T740-340q0 36-9.5 69.5T704-208ZM480-80q-40 0-76.5-11.5T336-123q-9 2-18 2.5t-19 .5q-91 0-155-64T80-339q0-87 58-149t143-69L120-880h280l80 160 80-160h280L680-559q85 8 142.5 70T880-340q0 92-64 156t-156 64q-9 0-18.5-.5T623-123q-31 20-67 31.5T480-80Zm0-260ZM363-572 250-800l113 228Zm234 0 114-228-114 228ZM406-230l28-91-74-53h91l29-96 29 96h91l-74 53 28 91-74-56-74 56Z" />
                        </svg>
                    </div>
                </div>
                <div v-if="tipoEstadistica == '4'" class="md:ml-3 mt-2 md:mt-0 relative">
                    <el-tooltip content="Elementos del Ranking" placement="top" popper-class="dark-tooltip">
                    <input
                        type="number"
                        id="elementos_ranking"
                        class="custom-input pl-10 block w-full md:w-20 p-2 text-sm text-gray-700 border border-gray-300 rounded-lg bg-gray-50 focus:ring-color1-500 focus:border-color1-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-color1-500 dark:focus:border-color1-500"
                        v-model="elementosRanking"
                        min="2"
                        max="10"
                        @input="fetchEstadisticas(true, true)"/>
                    </el-tooltip>
                    
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-color1-700">
                        <svg xmlns="http://www.w3.org/2000/svg" height="18px" stroke="currentColor"  stroke-width="0.25" viewBox="0 -960 960 960" width="24px"  fill="currentColor">
                            <path d="M480-160q75 0 127.5-52.5T660-340q0-75-52.5-127.5T480-520q-75 0-127.5 52.5T300-340q0 75 52.5 127.5T480-160ZM363-572q20-11 42.5-17.5T451-598L350-800H250l113 228Zm234 0 114-228H610l-85 170 19 38q14 4 27 8.5t26 11.5ZM256-208q-17-29-26.5-62.5T220-340q0-36 9.5-69.5T256-472q-42 14-69 49.5T160-340q0 47 27 82.5t69 49.5Zm448 0q42-14 69-49.5t27-82.5q0-47-27-82.5T704-472q17 29 26.5 62.5T740-340q0 36-9.5 69.5T704-208ZM480-80q-40 0-76.5-11.5T336-123q-9 2-18 2.5t-19 .5q-91 0-155-64T80-339q0-87 58-149t143-69L120-880h280l80 160 80-160h280L680-559q85 8 142.5 70T880-340q0 92-64 156t-156 64q-9 0-18.5-.5T623-123q-31 20-67 31.5T480-80Zm0-260ZM363-572 250-800l113 228Zm234 0 114-228-114 228ZM406-230l28-91-74-53h91l29-96 29 96h91l-74 53 28 91-74-56-74 56Z" />
                        </svg>
                    </div>
                </div>
                <div v-if="tipoEstadistica" class="flex flex-col items-end w-full md:w-auto ml-8 mt-2">
                    <el-switch
                    v-model="verTabla"
                    class="custom-switch w-full md:w-auto"
                    size="large"
                    inactive-text="Ver gráfico"
                    active-text="Ver tabla"/>
                </div>
            </div>
            <span id="badge-estadistica" v-if="estadisticasCargadas" class="flex justify-center w-full mt-2 px-2 text-xl font-medium text-gray-700 ">
                {{ estadisticaName }}
            </span>
            <!-- <div class="flex-grow flex flex-col overflow-hidden px-10">                   
                <div class="flex-grow flex flex-col">              
                    <SolicitudesPorPeriodo class="pb-1" v-if="tipoEstadistica == '1' && props.solicitudesPorPeriodo && props.solicitudesPorPeriodo.length > 0" :solicitudes="props.solicitudesPorPeriodo" />
                    <TramitesMasSolicitados class="pb-1" v-if="tipoEstadistica == '2' && props.tramitesMasSolicitados && props.tramitesMasSolicitados.length > 0" :tramitesMasSolicitados="props.tramitesMasSolicitados" />
                    <TiposPropiedadRequeridos class="pb-1" v-if="tipoEstadistica == '3' && props.tiposPropiedad && props.tiposPropiedad.length > 0" :tiposPropiedad="props.tiposPropiedad" />
                    <LocalidadesMasSolicitadas class="pb-1" v-if="tipoEstadistica == '4' && props.localidadesMasSolicitadas && props.localidadesMasSolicitadas.length > 0" :localidadesMasSolicitadas="props.localidadesMasSolicitadas" />
                </div>
            </div> -->
            <div class="flex-grow flex flex-col overflow-y-auto px-10">
                <div class="flex-grow flex flex-col">
                    <SolicitudesPorPeriodo class="pb-1 h-full" v-if="tipoEstadistica == '1' && !verTabla && props.solicitudesPorPeriodo && props.solicitudesPorPeriodo.length > 0" :solicitudes="props.solicitudesPorPeriodo" />
                    <SolicitudesPorPeriodoTable class="pb-1" v-if="tipoEstadistica == '1' && verTabla && props.solicitudesPorPeriodo && props.solicitudesPorPeriodo.length > 0" :solicitudes="props.solicitudesPorPeriodo" :totalSolicitudes="totalSolicitudesPorPeriodo" :agruparPorDia="props.agruparPorDia" />
                    
                    <TramitesMasSolicitados class="pb-1 h-full" v-if="tipoEstadistica == '2' && !verTabla && props.tramitesMasSolicitados && props.tramitesMasSolicitados.length > 0" :tramitesMasSolicitados="props.tramitesMasSolicitados" />
                    <TramitesMasSolicitadosTable class="pb-1 h-full" v-if="tipoEstadistica == '2' && verTabla && props.tramitesMasSolicitados && props.tramitesMasSolicitados.length > 0" :tramitesMasSolicitados="props.tramitesMasSolicitados" :totalTramitesMasSolicitados="props.totalTramitesMasSolicitados"/>
                    
                    <TiposPropiedadRequeridos class="pb-1 h-full" v-if="tipoEstadistica == '3' && !verTabla && props.tiposPropiedad && props.tiposPropiedad.length > 0" :tiposPropiedad="props.tiposPropiedad" />
                    <TiposPropiedadRequeridosTable class="pb-1 h-full" v-if="tipoEstadistica == '3' && verTabla && props.tiposPropiedad && props.tiposPropiedad.length > 0" :tiposPropiedad="props.tiposPropiedad" :totalSolicitudes="totalSolicitudesPorPeriodo"/>

                    <div v-if="tipoEstadistica == '4' && props.localidadesMasSolicitadas && props.localidadesMasSolicitadas.length > 0" class="pt-3 pb-2">
                        <LocalidadesMasSolicitadas class="pb-2" v-if="!verTabla" :localidadesMasSolicitadas="props.localidadesMasSolicitadas" />
                        <LocalidadesMasSolicitadasTable v-if="verTabla" :localidadesMasSolicitadas="props.localidadesMasSolicitadas" :totalSolicitudes="totalSolicitudesPorPeriodo"/>
                        
                    </div>                    
                </div>
                <div v-if="props.solicitudesPorPeriodo && props.solicitudesPorPeriodo.length == 0" class="flex-grow flex flex-col items-center justify-start">
                    <div class="max-w-md w-full bg-color1-50 dark:bg-gray-800 rounded-lg shadow-md p-6 text-center">
                        <h3 class="text-xl font-semibold text-color1-700 dark:text-gray-100 mb-2">
                            No se encontraron solicitudes
                        </h3>
                        <p class="text-sm text-color1-900 dark:text-gray-400">
                            Intenta ajustar el rango de fechas para ver los resultados.
                        </p>
                    </div>
                </div>
                <div v-if="props.tiposPropiedad && props.tiposPropiedad.length == 0" class="flex-grow flex flex-col items-center justify-start">
                    <div class="max-w-md w-full bg-color1-50 dark:bg-gray-800 rounded-lg shadow-md p-6 text-center">
                        <h3 class="text-xl font-semibold text-color1-700 dark:text-gray-100 mb-2">
                            No se encontraron solicitudes
                        </h3>
                        <p class="text-sm text-color1-900 dark:text-gray-400">
                            Intenta ajustar el rango de fechas para ver los resultados.
                        </p>
                    </div>
                </div>
                <div v-if="props.tramitesMasSolicitados && props.tramitesMasSolicitados.length == 0" class="flex-grow flex flex-col items-center justify-start">
                    <div class="max-w-md w-full bg-color1-50 dark:bg-gray-800 rounded-lg shadow-md p-6 text-center">
                        <h3 class="text-xl font-semibold text-color1-700 dark:text-gray-100 mb-2">
                            No se encontraron trámites
                        </h3>
                        <p class="text-sm text-color1-900 dark:text-gray-400">
                            Intenta ajustar el rango de fechas para ver los resultados.
                        </p>
                    </div>
                </div>
                <div v-if="props.localidadesMasSolicitadas && props.localidadesMasSolicitadas.length == 0" class="flex-grow flex flex-col items-center justify-start">
                    <div class="max-w-md w-full bg-color1-50 dark:bg-gray-800 rounded-lg shadow-md p-6 text-center">
                        <h3 class="text-xl font-semibold text-color1-700 dark:text-gray-100 mb-2">
                            No se encontraron solicitudes
                        </h3>
                        <p class="text-sm text-color1-900 dark:text-gray-400">
                            Intenta ajustar el rango de fechas para ver los resultados.
                        </p>
                    </div>
                </div>
            </div>
            <div v-if="tipoEstadistica == '1'" class="inline-flex items-center gap-x-1 bg-gray-100 px-2 py-1 text-sm font-medium text-gray-600 ring-1 ring-inset ring-gray-200">
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
                        Total de Solicitudes: <strong> {{ totalSolicitudesPorPeriodo }} </strong>
                    </div>
                </div>
            </div>
            <div v-if="tipoEstadistica == '2'" class="inline-flex items-center gap-x-1 bg-gray-100 px-2 py-1 text-sm font-medium text-gray-600 ring-1 ring-inset ring-gray-200">
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
                        Total de Trámites: <strong> {{ totalTramitesMasSolicitados }} </strong>
                    </div>
                </div>
            </div>
            <div v-if="tipoEstadistica == '3'" class="inline-flex items-center gap-x-1 bg-gray-100 px-2 py-1 text-sm font-medium text-gray-600 ring-1 ring-inset ring-gray-200">
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
                        Total de Solicitudes: <strong> {{ totalSolicitudesPorPeriodo }} </strong>
                    </div>
                </div>
            </div>
            <div v-if="tipoEstadistica == '4'" class="inline-flex items-center gap-x-1 bg-gray-100 px-2 py-1 text-sm font-medium text-gray-600 ring-1 ring-inset ring-gray-200">
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
                        Total de Solicitudes: <strong> {{ totalSolicitudesPorPeriodo }} </strong>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>