<style>
input[type="search"]::-webkit-search-cancel-button {
    cursor: pointer;
}
</style>


<script setup>
    import { router } from '@inertiajs/vue3'
    import { ref, computed, watch, onMounted, onUnmounted, onBeforeUnmount, markRaw, nextTick, defineEmits } from 'vue'
    import Pagination from '@/Components/Pagination.vue'
    import Formulario from '@/Pages/Solicitudes/Formulario.vue';
    import Swal from 'sweetalert2'
    import CalendarUp from '@/Components/UI/Icons/CalendarUp.vue';
    import CalendarDown from '@/Components/UI/Icons/CalendarDown.vue';

    const emit = defineEmits(['update:showModal']);

    const showModal = ref(false);
    const solicitudSeleccionada = ref(null);
    const searchQuery = ref(null)
    const searchQueryConfirmado = ref('');
    const estatusQuery =  ref('todas')
    const areasQuery = ref(null)

    const realizarBusqueda = () => {
        
        router.post(
            route('solicitudes'), // O la ruta específica de tu backend, ej: route('tu.ruta.index')
            {
                searchQuery: searchQuery.value ? searchQuery.value.trim() : null,
                fechaIngresoInicioQuery: rangoFechasIngresoQuery.value?.[0] || null,
                fechaIngresoFinQuery: rangoFechasIngresoQuery.value?.[1] || null,
                estatusQuery: estatusQuery.value,
                areasQuery: areasQuery.value,
                page: 1, // Reiniciar a la página 1
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const props = defineProps({
        solicitudes: Array,
        estatusSolicitud: Array,
        pagination: Object,
        localidadesList: Array,
        localidadesQuery: Array,
        numQuery: String,
        nombreQuery: String,
        fechaIngresoInicioQuery: String,
        fechaIngresoFinQuery: String,
        fechaAceptacionInicioQuery: String,
        fechaAceptacionFinQuery: String,
        idRangoFechasIngresoQuery: Number,
        idRangoFechasAceptacionQuery: Number,
        totalesEstatusQuery: Object,
        areasList: Array,
        estatusList: Array,
        totalSolicitudes: Number,
        isPage: Boolean,
        filtroChkSolicitudes: [Number, String],
        sortDirection: String,
        sortColumn: String,
        periodoActual: Object,
        page: {
            type: [Number, String],
            default: 1
        }
    })

   
    const intervalo = ref(null)
    const intervaloMs = 30000 // 👈 ajusta aquí la frecuencia del polling

    //Parámetros para filtrar
    const numQuery = ref('')
    const folioQuery = ref('')
    const nombreQuery = ref('')
    const fechaIngresoInicioQuery = ref('')
    const fechaIngresoFinQuery = ref('')
    const fechaAceptacionInicioQuery = ref('')
    const fechaAceptacionFinQuery = ref('')
    const claveCatastralQuery = ref('')
    const rangoFechasIngresoQuery = ref([])
    const rangoFechasAceptacionQuery = ref([])
    const tiposTramitesQuery = ref([])
    const tramitesQuery = ref([])
    const localidadesQueryFiltradas = ref([])
    const localidadesSelectQuery = ref([])
    const filtroChkSolicitudes = ref(0)
    const rangoFechasIngresoManual = ref(false)
    const rangoFechasAceptacionManual = ref(false)
    const rangoFechasShortcut = ref(false)
    const rangoFechasAceptacionShortcut = ref(false)
    const idRangoFechasIngresoQuery = ref(99)
    const idRangoFechasAceptacionQuery = ref(99)
    const pickerWrapper = ref(null)
    const periodoActual = ref(props.periodoActual || {})

    const sortColumn = ref(props.sortColumn || 'fecha_ingreso');
    const sortDirection = ref(props.sortDirection || 'asc');
    const dropdownVisible = ref(null)

    const isLoading = ref(false)
    const isSearching = ref(false)

    const abreModalSolicitud = async () => {
        solicitudSeleccionada.value = null
        showModal.value = true;
    }

    const abreModalEditarSolicitud = async (solicitud) => {
        solicitudSeleccionada.value = solicitud;
        showModal.value = true;
    }

    // Expresión regular para validar CURP
    const curpRegex =
        /^([A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d])(\d)$/

    // Función para iniciar el temporizador.
    const iniciarIntervalo = () => {
        if (!intervalo.value) { // Solo si no está ya iniciado
            intervalo.value = setInterval(() => {
            fetchSolicitudes(false, true);
            }, intervaloMs);
        }
    };

    onMounted(() => {
        fechaIngresoInicioQuery.value = props.fechaIngresoInicioQuery
        fechaIngresoFinQuery.value = props.fechaIngresoFinQuery
        idRangoFechasIngresoQuery.value = props.idRangoFechasIngresoQuery

        sortColumn.value = props.sortColumn || 'fecha';
        sortDirection.value = props.sortDirection || 'asc';

        busquedaIndexada.value = false;

        rangoFechasIngresoQuery.value = [
            fechaIngresoInicioQuery.value, // formato 'YYYY-MM-DD'
            fechaIngresoFinQuery.value
        ]

        rangoFechasAceptacionQuery.value = [
            fechaAceptacionInicioQuery.value, // formato 'YYYY-MM-DD'
            fechaAceptacionFinQuery.value
        ]

        filtroChkSolicitudes.value = 0

        document.addEventListener('click', handleClickOutside)
        window.addEventListener('resize', updateSize)

        nextTick(() => {
            const inputs = pickerWrapper.value?.querySelectorAll('input')
            if (inputs?.length === 2) {
                inputs.forEach((input) => {
                    input.addEventListener('input', (event) => {
                        if (event.isTrusted) {
                            rangoFechasIngresoManual.value = true
                            onDateChange(rangoFechasIngresoQuery.value)
                        }
                    })
                })
            }
        })


        intervalo.value = setInterval(() => {
            fetchSolicitudes(false, true)
        }, intervaloMs)


        iniciarIntervalo();
    })

    onBeforeUnmount(() => {
        document.removeEventListener('click', handleClickOutside)
    })


    function buildQueryParams(page = 1) {
        const urlParams = new URLSearchParams(window.location.search)

        if (folioQuery.value) {
            urlParams.set('folioQuery', folioQuery.value)
        } else {
            urlParams.delete('folioQuery')
        }

        if (nombreQuery.value) {
            urlParams.set('nombreQuery', nombreQuery.value)
        } else {
            urlParams.delete('nombreQuery')
        }

        if (claveCatastralQuery.value) {
            urlParams.set('claveCatastralQuery', claveCatastralQuery.value)
        } else {
            urlParams.delete('claveCatastralQuery')
        }

        urlParams.set('fechaIngresoInicioQuery', fechaIngresoInicioQuery.value)
        urlParams.set('fechaIngresoFinQuery', fechaIngresoFinQuery.value)
        urlParams.set('rangoFechasIngresoManual', rangoFechasIngresoManual.value)

        urlParams.set('fechaAceptacionInicioQuery', fechaAceptacionInicioQuery.value)
        urlParams.set('fechaAceptacionFinQuery', fechaAceptacionFinQuery.value)
        urlParams.set('rangoFechasAceptacionManual', rangoFechasAceptacionManual.value)

        urlParams.set('page', page)

        return Object.fromEntries(urlParams.entries())
    }

    function fetchSolicitudes(onMounted, polling = false) {
        const page = router.page.props.solicitudes?.current_page ?? 1
        const paramsObject = buildQueryParams(page)

        let showLoaderTimeout = null

        if (onMounted) 
        {
            sortColumn.value = 'fecha_ingreso'
            sortDirection.value = 'asc'
        } 
        else 
        {
            if (!polling) {
                showLoaderTimeout = setTimeout(() => {
                    isSearching.value = true
                }, 200)
            }
        }

        const filtros = {
            fechaIngresoInicioQuery: fechaIngresoInicioQuery.value,
            fechaIngresoFinQuery: fechaIngresoFinQuery.value,
            fechaAceptacionInicioQuery: fechaAceptacionInicioQuery.value,
            fechaAceptacionFinQuery: fechaAceptacionFinQuery.value,
            searchQuery: searchQueryConfirmado.value,
            estatusQuery: estatusQuery.value,
            areasQuery: areasQuery.value,
            idRangoFechasIngresoQuery: idRangoFechasIngresoQuery.value,
            rangoFechasIngresoManual: rangoFechasIngresoManual.value,
            idRangoFechasAceptacionQuery: idRangoFechasAceptacionQuery.value,
            rangoFechasAceptacionManual: rangoFechasAceptacionManual.value,
            filtroChkSolicitudes: filtroChkSolicitudes.value,
            sortColumn: sortColumn.value,
            sortDirection: sortDirection.value
        }

        // 1. Prepara el objeto de datos de la forma de manera segura
        const formData = new FormData()
        for (const key in filtros) 
        {
            const value = filtros[key]
            if (Array.isArray(value)) 
            {
                value.forEach((item, index) => {
                    formData.append(`${key}[${index}]`, item)
                })
            } 
            else 
            {
                formData.append(key, value ?? '')
            }
        }
        formData.append('page', Number(paramsObject.page) || 1)

        // 2. Define las opciones base de la solicitud
        const requestOptions = {
            preserveState: true,
            replace: true,
            preserveScroll: true,
            onStart: () => {
                // Lógica común de inicio
            },
            onFinish: () => {
                const currentPage = router.page.props.solicitudes.current_page;
                const lastPage = router.page.props.solicitudes.last_page;

                if (currentPage > lastPage)   //Si la página actual excede al número de páginas obtenidas
                {
                    const newFilters = { 
                        ...router.page.props.filters, 
                        page: Number(1)
                    };

                    //Vuelve a cargar la página con la página #1
                    router.get(
                        router.page.url.split('?')[0],
                        newFilters,
                        { preserveState: true }
                    );
                }

                if (onMounted) {
                    isLoading.value = false
                } else {
                    clearTimeout(showLoaderTimeout)
                    isSearching.value = false
                }
                filtroChkSolicitudes.value = router.page.props.filtroChkSolicitudes
            }
        }

        // 3. Añade la propiedad 'only' solo si es polling
        if (polling) {
            requestOptions.only = ['solicitudes']
        }

        router.post('/solicitudes', formData, requestOptions)
    }

    function onDateChange(fechaSeleccionada) {
      function resetToLocalMidnight(dateStringOrDate) {
            const d = typeof dateStringOrDate === 'string'
                ? new Date(dateStringOrDate + 'T00:00:00') // fuerza hora local
                : new Date(dateStringOrDate)
            d.setHours(0, 0, 0, 0)
            return d
        }

        const coincideConShortcut = shortcuts.some(sc => {
            const [start, end] = sc.value()

            const seleccionadoStart = resetToLocalMidnight(fechaSeleccionada[0])
            const seleccionadoEnd = resetToLocalMidnight(fechaSeleccionada[1])

            return resetToLocalMidnight(start).getTime() === seleccionadoStart.getTime() &&
                resetToLocalMidnight(end).getTime() === seleccionadoEnd.getTime()
        })

        // Si no coincide, asumimos que fue manual
        rangoFechasIngresoManual.value = !coincideConShortcut
        rangoFechasShortcut.value = coincideConShortcut

        if (rangoFechasIngresoManual.value)
        {
            fechaIngresoInicioQuery.value = rangoFechasIngresoQuery.value[0]
            fechaIngresoFinQuery.value = rangoFechasIngresoQuery.value[1]
        }

        estatusQuery.value = 'todas';
        fetchSolicitudes(false)
    }

    function onDateChangeAceptacion(fechaSeleccionada) {
      function resetToLocalMidnight(dateStringOrDate) {
            const d = typeof dateStringOrDate === 'string'
                ? new Date(dateStringOrDate + 'T00:00:00') // fuerza hora local
                : new Date(dateStringOrDate)
            d.setHours(0, 0, 0, 0)
            return d
        }

        const coincideConShortcut = shortcuts.some(sc => {
            const [start, end] = sc.value()

            const seleccionadoStart = resetToLocalMidnight(fechaSeleccionada[0])
            const seleccionadoEnd = resetToLocalMidnight(fechaSeleccionada[1])

            return resetToLocalMidnight(start).getTime() === seleccionadoStart.getTime() &&
                resetToLocalMidnight(end).getTime() === seleccionadoEnd.getTime()
        })

        // Si no coincide, asumimos que fue manual
        rangoFechasAceptacionManual.value = !coincideConShortcut
        rangoFechasAceptacionShortcut.value = coincideConShortcut

        if (rangoFechasAceptacionManual.value)
        {
            fechaAceptacionInicioQuery.value = rangoFechasAceptacionQuery.value[0]
            fechaAceptacionFinQuery.value = rangoFechasAceptacionQuery.value[1]
        }

        fetchSolicitudes(false)
    }

    const formatDate = (dateString) => {
        // 1. Validación: Retorna 'S/F' si la fecha no existe.
        if (!dateString) {
            return 'N/A' 
        }

        // 2. Manejo de fecha y hora
        // Extraemos solo la parte de la fecha (todo antes del primer espacio, si existe)
        // Ejemplo: '2025-09-26 13:58:37' -> '2025-09-26'
        const dateOnly = dateString.split(' ')[0]

        // Verificamos si la extracción resultó en algo vacío (por si dateString era solo espacios)
        if (!dateOnly) {
            return 'N/A'
        }

        // 3. Proceso de formateo de la fecha limpia
        // Usamos la fecha limpia 'YYYY-MM-DD'
        const [year, month, day] = dateOnly.split('-') // Divide la fecha ISO en partes

        // 4. Retorna la fecha en formato "dd/mm/yyyy"
        return `${day}/${month}/${year}`
    }

   
    function closeDropdown() {
        dropdownVisible.value = null
    }

    function handleClickOutside(event) 
    {
        const dropdown = document.getElementById(`dropdown-${dropdownVisible.value}`)
        const button = document.getElementById(`dropdown-button-${dropdownVisible.value}`)
        if (dropdownVisible.value && dropdown && button && !dropdown.contains(event.target) && !button.contains(event.target)) {
            closeDropdown()
        }
    }

    const isMobile = ref(window.innerWidth <= 450)

    const updateSize = () => {
        isMobile.value = window.innerWidth <= 450
    }

    onUnmounted(() => {
        window.removeEventListener('resize', updateSize)
        // window.Echo.leaveChannel('solicitudes')
        clearInterval(intervalo.value)
    })

    const shortcuts = [
        // {
        //     id: 1,
        //     text: 'Hoy',
        //     value: () => {
        //         const today = new Date()
        //         idRangoFechasQuery.value = 1
        //         rangoFechasShortcut.value = true
        //         rangoFechasIngresoManual.value = false

        //         return [today, today]
        //     }
        // },
        {
            id: 2,
            text: 'Sem. Actual',
            value: () => {
                const today = new Date()
                const dayOfWeek = today.getDay() // 0 (domingo) - 6 (sábado)
                const mondayOffset = dayOfWeek === 0 ? 6 : dayOfWeek - 1 // Si es domingo, retrocede 6 días; si no, retrocede (día - 1)
                const start = new Date(today)
                start.setDate(today.getDate() - mondayOffset)
                idRangoFechasIngresoQuery.value = 2
                idRangoFechasAceptacionQuery.value = 2
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, today]
            }
        },
        {
            id: 3,
            text: 'Mes Actual',
            value: () => {
                const today = new Date()
                const start = new Date(today.getFullYear(), today.getMonth(), 1) // Primer día del mes actual
                idRangoFechasIngresoQuery.value = 3
                idRangoFechasAceptacionQuery.value = 3
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, today]
            }
        },
        {
            id: 4,
            text: 'Año Actual',
            value: () => {
                const today = new Date()
                const start = new Date(today.getFullYear(), 0, 1) // 0 = enero, 1 = día 1
                idRangoFechasIngresoQuery.value = 4
                idRangoFechasAceptacionQuery.value = 4
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, today]
            }
        },
        {
            id: 5,
            text: 'Sem. Pasada',
            value: () => {
                const today = new Date()
                const dayOfWeek = today.getDay() // 0 (domingo) - 6 (sábado)
                const currentMondayOffset = dayOfWeek === 0 ? 6 : dayOfWeek - 1

                // Lunes pasado = lunes de esta semana - 7 días
                const start = new Date(today)
                start.setDate(today.getDate() - currentMondayOffset - 7)

                // Domingo pasado = lunes pasado + 6 días
                const end = new Date(start)
                end.setDate(start.getDate() + 6)
                idRangoFechasIngresoQuery.value = 5
                idRangoFechasAceptacionQuery.value = 5
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, end]
            }
        },
        {
            id: 6,
            text: 'Mes Pasado',
            value: () => {
                const today = new Date()

                // Primer día del mes pasado
                const start = new Date(today.getFullYear(), today.getMonth() - 1, 1)

                // Último día del mes pasado
                const end = new Date(today.getFullYear(), today.getMonth(), 0) // Día 0 del mes actual = último del anterior
                idRangoFechasIngresoQuery.value = 6
                idRangoFechasAceptacionQuery.value = 6
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, end]
            }
        },
        {
            id: 7,
            text: 'Año Pasado',
            value: () => {
                const start = new Date(new Date().getFullYear() - 1, 0, 1) // 1 de enero del año anterior
                const end = new Date(new Date().getFullYear() - 1, 11, 31) // 31 de diciembre del año anterior
                idRangoFechasIngresoQuery.value = 7
                idRangoFechasAceptacionQuery.value = 7
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, end]
            }
        },
        {
            id: 8,
            text: 'Últ. Semana',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setTime(start.getTime() - 3600 * 1000 * 24 * 7)
                idRangoFechasIngresoQuery.value = 8
                idRangoFechasAceptacionQuery.value = 8
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, end]
            }
        },
        {
            id: 9,
            text: 'Últ. Mes',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setMonth(start.getMonth() - 1)
                idRangoFechasIngresoQuery.value = 9
                idRangoFechasAceptacionQuery.value = 9
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, end]
            }
        },
        {
            id: 10,
            text: 'Últ. Año',
            value: () => {
                const end = new Date()
                const start = new Date()
                start.setFullYear(start.getFullYear() - 1)
                idRangoFechasIngresoQuery.value = 10
                idRangoFechasAceptacionQuery.value = 10
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [start, end]
            }
        },
        // En tu objeto de shorcuts
        {
            id: 11,
            text: '1er Año Gob',
            value: () => {
                // Obtenemos el año de la fecha de inicio del periodo actual.
                const startYear = new Date(periodoActual.value.inicio).getFullYear();
                
                // Se establece en el 1 de noviembre del año de inicio del periodo.
                const start = periodoActual.value.inicio; 

                // Creamos la fecha de fin para el 1er año de gobierno.
                const end = new Date(startYear + 1, 9, 31); // El mes 9 es octubre (0-indexado)

                idRangoFechasIngresoQuery.value = 11;
                idRangoFechasAceptacionQuery.value = 11
                rangoFechasShortcut.value = true;
                rangoFechasIngresoManual.value = false;
                rangoFechasAceptacionManual.value = false

                return [start, end];
            },
        },
        {
            id: 12, 
            text: '2do Año Gob',
            value: () => {
                // Agregamos 'T00:00:00' para que JavaScript interprete la fecha en la zona horaria local.
                const inicioStringLocal = periodoActual.value.inicio + 'T00:00:00';

                // Creamos la fecha base a partir de la cadena de texto local.
                const baseDate = new Date(inicioStringLocal);
                
                // Ahora, ajustamos el año según lo que necesites para el 2do año de gobierno
                const startYear = baseDate.getFullYear() + 1; // 2024 + 1 = 2025
                
                // Creamos la fecha de inicio del segundo año
                const start = new Date(startYear, baseDate.getMonth(), baseDate.getDate());
                
                // Creamos la fecha de fin (1 año después de la fecha de inicio menos un día)
                const end = new Date(start);
                end.setFullYear(end.getFullYear() + 1);
                end.setDate(end.getDate() - 1); 

                // Actualiza tus refs
                idRangoFechasIngresoQuery.value = 11;
                idRangoFechasAceptacionQuery.value = 11
                rangoFechasShortcut.value = true;
                rangoFechasIngresoManual.value = false;
                rangoFechasAceptacionManual.value = false
                
                return [start, end];
            }
        },
        {
            id: 13, 
            text: '3er Año Gob',
            value: () => {
                // Agregamos 'T00:00:00' para que JavaScript interprete la fecha en la zona horaria local.
                const inicioStringLocal = periodoActual.value.inicio + 'T00:00:00';

                // Creamos la fecha base a partir de la cadena de texto local.
                const baseDate = new Date(inicioStringLocal);
                
                // Ahora, ajustamos el año según lo que necesites para el 2do año de gobierno
                const startYear = baseDate.getFullYear() + 2; // 2024 + 1 = 2025
                
                // Creamos la fecha de inicio del segundo año
                const start = new Date(startYear, baseDate.getMonth(), baseDate.getDate());
                
                // Creamos la fecha de fin (1 año después de la fecha de inicio menos un día)
                const end = new Date(start);
                end.setFullYear(end.getFullYear() + 1);
                end.setDate(end.getDate() - 1); 

                // Actualiza tus refs
                idRangoFechasIngresoQuery.value = 11;
                idRangoFechasAceptacionQuery.value = 11
                rangoFechasShortcut.value = true;
                rangoFechasIngresoManual.value = false;
                rangoFechasAceptacionManual.value = false
                
                return [start, end];
            }
        },
    ]

    watch(rangoFechasIngresoQuery, (nuevoValor) => {
        if (Array.isArray(nuevoValor) && nuevoValor.length === 2 && nuevoValor[0] && nuevoValor[1]) {
            fechaIngresoInicioQuery.value = nuevoValor[0]
            fechaIngresoFinQuery.value = nuevoValor[1]
            rangoFechasAceptacionQuery.value[0] = null
            rangoFechasAceptacionQuery.value[1] = null
            fechaAceptacionInicioQuery.value = null
            fechaAceptacionFinQuery.value = null
            onDateChange(nuevoValor)
        }
    })

    watch(rangoFechasAceptacionQuery, (nuevoValor) => {
        if (Array.isArray(nuevoValor) && nuevoValor.length === 2 && nuevoValor[0] && nuevoValor[1]) {
            fechaAceptacionInicioQuery.value = nuevoValor[0]
            fechaAceptacionFinQuery.value = nuevoValor[1]
            rangoFechasIngresoQuery.value[0] = null
            rangoFechasIngresoQuery.value[1] = null
            fechaIngresoInicioQuery.value = null
            fechaIngresoFinQuery.value = null
            onDateChangeAceptacion(nuevoValor)
        }
    })

    function handlePageChange(page) {
        router.post('/solicitudes', {
            fechaIngresoInicioQuery: fechaIngresoInicioQuery.value,
            fechaIngresoFinQuery: fechaIngresoFinQuery.value,
            fechaAceptacionInicioQuery: fechaAceptacionInicioQuery.value,
            fechaAceptacionFinQuery: fechaAceptacionFinQuery.value,
            numQuery: numQuery.value,
            folioQuery: folioQuery.value,
            nombreQuery: nombreQuery.value,
            claveCatastralQuery: claveCatastralQuery.value,
            tiposTramitesQuery: tiposTramitesQuery.value,
            tramitesQuery: tramitesQuery.value,
            localidadesQueryFiltradas: localidadesQueryFiltradas.value,
            estatusQuery: estatusQuery.value,
            filtroChkSolicitudes: filtroChkSolicitudes.value,
            rangoFechasIngresoManual: rangoFechasIngresoManual.value,
            rangoFechasAceptacionManual: rangoFechasAceptacionManual.value,
            sortColumn: sortColumn.value,
            sortDirection: sortDirection.value,
            page: page
        }, {
            preserveState: true,
            replace: true,
            onFinish: () => {
                isLoading.value = false;
                filtroChkSolicitudes.value = router.page.props.filtroChkSolicitudes;
            }
        });
    }

    const CustomCalendarUp = CalendarUp; 
    const CustomCalendarDown = CalendarDown; 

    const busquedaIndexada = ref(false); // Inicialmente en false

    watch(folioQuery, (newValue) => {
        // Si el valor cambia, activamos la bandera.
        busquedaIndexada.value = true;
        
        if (newValue === '') {
            busquedaIndexada.value = false;
        }

        if (folioQuery.value == 'n' || folioQuery.value == 'N')  //Muestra las solicitudes sin folio
        {
            busquedaIndexada.value = false
        }
    });

        const getStatusColor = (colorKey) => {
            const colors = {
                success: '#10B981', // Verde
                warning: '#F59E0B', // Amarillo / Naranja
                danger: '#EF4444',  // Rojo
                info: '#3B82F6',    // Azul
                primary: '#6366F1', // Índigo / Tu color principal
                secondary: '#6B7280' // Gris
            };

            // Si viene un hex o rgb directo, lo respeta; si viene una palabra, la busca; si no hay nada, usa gris por defecto.
            return colors[colorKey] || colorKey || '#9CA3AF';
        };

        // En tus métodos o computed properties de Vue:
    const esTerminal = (idEstatus) => {
        const estatusTerminales = [3, 4]; // Ajusta según los IDs reales de tu base de datos
        return estatusTerminales.includes(Number(idEstatus));
    };

    const manejarClickCard = (solicitud) => {
        // Si ya es terminal, no hacemos nada (la tarjeta es de solo lectura)
        if (esTerminal(solicitud.id_estatus)) {
            return;
        }
        
        // Si está activa, abrimos el modal de edición normal
        abreModalEditarSolicitud(solicitud);
    };
</script>

<template>
    <!-- <Head title="Gestión de Solicitudes" /> -->
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- 1. VERSIÓN COMPUTADORA (Escritorio): Usa el Dialog de Element Plus con encabezado personalizado -->
        <div class="hidden lg:block">
            <el-dialog
                :model-value="showModal"
                @update:model-value="(val) => showModal = val"
                width="70%"
                align-center
                destroy-on-close
                style="border-radius: 1rem; overflow: hidden;">
                <!-- Slot de Encabezado Personalizado para Computadora -->
                <template #header>
                    <div class="pl-2 pt-3 pr-2">
                        <!-- Breadcrumb -->
                        <div class="flex items-center flex-wrap gap-1.5 text-[12px] font-semibold uppercase tracking-wider text-color1-700 dark:text-color1-400 mb-0.5">
                                <span>Módulo de Gestión</span>
                                <span class="text-gray-400">/</span>
                                <span>Solicitudes</span>
                            </div>

                            <!-- Título y Folio -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5">
                                <!-- Lado Izquierdo: Título Limpio -->
                                <div>
                                    <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white leading-tight">
                                        {{ solicitudSeleccionada ? 'Editar Solicitud' : 'Nueva Solicitud' }}
                                    </h2>
                                </div>

                                <!-- Lado Derecho: Badge de Estado + Folio Juntos -->
                                <div class="flex items-center gap-3 flex-wrap self-start sm:self-center">
                                    
                                    <!-- Badge de Estado (Captura / Edición) -->
                                    <span 
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium ring-1 ring-inset"
                                        :class="!solicitudSeleccionada 
                                            ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400 ring-amber-600/25' 
                                            : 'bg-color1-50 text-color1-700 dark:bg-color1-950/50 dark:text-color1-400 ring-color1-600/25'"
                                    >
                                        {{ !solicitudSeleccionada ? 'Modo Captura' : 'Modo Edición' }}
                                    </span>

                                    <!-- Bloque de Folio (Solo si existe ID) -->
                                    <div 
                                        v-if="solicitudSeleccionada?.id"
                                        class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400"
                                    >
                                        <span class="text-gray-400 dark:text-gray-500 uppercase tracking-wider font-medium">
                                            Folio:
                                        </span>
                                        <span class="font-medium text-color1-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-md px-2.5 py-1 ring-1 ring-inset ring-gray-200 dark:ring-gray-700">
                                            #{{ String(solicitudSeleccionada.id).slice(-5).padStart(6, '0') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                </template>

                <!-- Cuerpo del Modal -->
                <div class="w-full max-h-[75vh] overflow-y-auto px-8">
                    <Formulario
                        :isPage="false"
                        :nuevaSolicitud="!solicitudSeleccionada"
                        v-model:solicitud="solicitudSeleccionada"
                        :localidadesList="localidadesList"
                        :areasList="areasList"
                        :estatusList="estatusList"
                        :searchQuery="searchQuery"
                        :fechaIngresoInicioQuery = "fechaIngresoInicioQuery"
                        :fechaIngresoFinQuery = "fechaIngresoFinQuery"
                        :estatusQuery = "estatusQuery"
                        :areasQuery = "areasQuery"
                        :page = "page"
                        @close="showModal = false"
                    />
                </div>
            </el-dialog>
        </div>

        <!-- 2. VERSIÓN CELULAR: Bottom Sheet con el mismo encabezado -->
        <div class="lg:hidden">
            <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 transition-opacity flex items-end justify-center" @click="emit('update:showModal', false)">
                <div 
                    class="w-full max-h-[90vh] bg-white dark:bg-gray-900 rounded-t-2xl p-6 shadow-2xl overflow-y-auto transform transition-transform duration-300 flex flex-col"
                    @click.stop>
                    <!-- Indicador visual (Grabber) -->
                    <div class="w-12 h-1.5 bg-gray-300 dark:bg-gray-700 rounded-full mx-auto mb-4"></div>
                        <!-- Cabecera para Celular -->
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <div class="flex items-center flex-wrap gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-color1-700 dark:text-color1-400 mb-0.5">
                                    <span>Módulo de Gestión</span>
                                    <span class="text-gray-400">/</span>
                                    <span>Solicitudes</span>
                                </div>

                                <div class="flex items-center justify-between gap-4">
                                <h2 class="text-xl font-semibold tracking-tight
                                        text-gray-900 dark:text-white leading-tight">
                                    {{ solicitudSeleccionada
                                        ? 'Editar Solicitud'
                                        : 'Nueva Solicitud' }}
                                </h2>

                                <!-- Identificador de la solicitud -->
                                <div v-if="solicitudSeleccionada?.id"
                                    class="flex items-center gap-2 shrink-0
                                            text-xs text-gray-500 dark:text-gray-400">
                                    <span class="text-gray-400 dark:text-gray-500">
                                        Folio
                                    </span>

                                    <span class="font-medium
                                                text-color1-700 dark:text-gray-300
                                                bg-gray-50 dark:bg-gray-800
                                                
                                                rounded-md px-2 py-1">
                                        #{{ String(solicitudSeleccionada.id)
                                            .slice(-5).padStart(6, '0') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Botón rápido para cerrar (X) -->
                        <button 
                            @click="showModal=false"
                            type="button"
                            class="shrink-0 py-1 px-1 sm:py-1.5 sm:px-3 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-hidden focus:ring-2 focus:ring-color1-500 transition-colors flex items-center justify-center"
                            aria-label="Cerrar">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <hr class="border-gray-100 dark:border-gray-800 mb-4" />

                    <!-- Cuerpo del Formulario para Celular -->
                    <div class="w-full overflow-y-auto px-1 flex-1">
                        <Formulario
                            :isPage="false"
                            :nuevaSolicitud="!solicitudSeleccionada"
                            v-model:solicitud="solicitudSeleccionada"
                            :localidadesList="localidadesList"
                            :areasList="areasList"
                            :estatusList="estatusList"
                            :searchQuery="searchQuery"
                            :estatusQuery = "estatusQuery"
                            :areasQuery = "areasQuery"
                            :fechaIngresoInicioQuery = "fechaIngresoInicioQuery"
                            :fechaIngresoFinQuery = "fechaIngresoFinQuery"
                            :page = "page"
                            @close="showModal = false"
                        />
                    </div>
                </div>
            </div>
        </div>


        <!-- Encabezado -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center
                    sm:justify-between gap-4 pb-3
                    dark:border-gray-800">

            <div>
            <div v-if="isPage" class="flex items-center flex-wrap gap-1.5
                            text-[11px] sm:text-xs font-semibold
                            uppercase tracking-wider
                            text-color1-700 dark:text-color1-400 mb-1">
                    <span>Módulo de Gestión</span>
                    <span class="text-gray-400">/</span>
                    <span>Solicitudes</span>
                </div>

                <h1 class="text-xl sm:text-2xl font-bold tracking-tight
                            text-gray-900 dark:text-white leading-tight">
                    Gestión de Solicitudes
                </h1>

                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Consulta y administra las solicitudes ciudadanas.
                </p>
            </div>

            <button
                v-if="totalSolicitudes"
                ref="nuevaBtn"
                @click="abreModalSolicitud"
                type="button"
                class="inline-flex items-center justify-center gap-2
                        rounded-full bg-color1-800 hover:bg-color1-700
                        text-white text-sm font-semibold px-4 py-2.5
                        shadow-lg transition-colors
                        focus:outline-none focus:ring-2
                        focus:ring-color1-500 focus:ring-offset-2
                        w-full sm:w-auto">

                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4" />
                </svg>

                Nueva Solicitud
            </button>
        </div>

        <div class="mb-5 space-y-3 bg-white dark:bg-gray-900/50 border border-gray-200 dark:border-gray-800 shadow-sm p-3.5 sm:p-4 rounded-xl">
            <!-- Fila 1: Contenedor Adaptativo de Filtros -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:flex xl:flex-row items-stretch xl:items-center justify-between gap-3">
                <!-- 1. Rango de Fechas con Element Plus -->
                <div class="space-y-1 w-full xl:w-auto z-10">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Periodo de Solicitudes</span>
                    <el-date-picker
                        v-model="rangoFechasIngresoQuery"
                        type="daterange"
                        unlink-panels
                        range-separator="-"
                        start-placeholder="-"
                        end-placeholder="-"
                        :shortcuts="shortcuts"
                        class="custom-date-picker !w-full xl:w-[260px]"
                        value-format="YYYY-MM-DD"
                        format="DD/MM/YYYY"
                        @change="onDateChange"
                        :prefix-icon="CustomCalendarUp"
                        :disabled="busquedaIndexada"
                        :clearable="false"
                        style="border-radius: 16px!important"
                    />
                </div>

                <!-- 4. Buscador de Texto Libre (Toma el espacio restante en desktop) -->
                <form @submit.prevent="estatusQuery = 'todas'; searchQueryConfirmado = searchQuery; realizarBusqueda();" class="space-y-1 w-full xl:flex-1">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Búsqueda rápida</span>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            type="search" 
                            v-model="searchQuery"
                            @search="searchQueryConfirmado = searchQuery; estatusQuery = 'todas'; realizarBusqueda();"
                            placeholder="Buscar por folio, solicitante o petición..."
                            style="border-radius: 16px!important; min-height: 38px !important;"
                            class="block  w-full pl-9 pr-3 py-2 text-xs sm:text-sm rounded-lg border border-gray-300 shadow-sm dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-color1 focus:border-transparent transition-all"
                        />
                    </div>
                </form>
            </div>

            <div class="pt-2 pb-4 sm:pb-0 pr-1 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 overflow-x-visible sm:overflow-x-hidden">
                <!-- Contenedor de las píldoras (scroll horizontal independiente si hace falta) -->
                <div class="flex pl-1 pr-1 pt-3 items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar shrink-0">
                    <button
                        type="button"
                        :disabled="totalesEstatusQuery.todas === 0"
                        @click="estatusQuery = 'todas'; realizarBusqueda();"
                        :class="[
                            'px-2.5 py-2 sm:px-3 sm:py-2 text-[11px] sm:text-xs uppercase tracking-wider rounded-full transition-all duration-200 ease-out flex items-center gap-1.5 shrink-0',
                            'focus:outline-none focus-visible:ring-2 focus-visible:ring-color1 focus-visible:ring-offset-1',
                            totalesEstatusQuery.todas === 0
                                ? 'opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 border border-gray-200 dark:border-gray-800'
                                : estatusQuery === 'todas'
                                    ? 'cursor-pointer bg-gray-800 text-white dark:bg-white dark:text-gray-900 shadow-sm font-bold border border-transparent hover:-translate-y-0.5 hover:shadow-md'
                                    : 'cursor-pointer bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-800 hover:border-gray-400 dark:hover:border-gray-600 hover:-translate-y-0.5 hover:shadow-md hover:text-gray-900 dark:hover:text-white font-semibold'
                        ]"
                    >
                        TODAS
                        <span
                            :class="
                                estatusQuery === 'todas'
                                    ? 'bg-black/20 text-white dark:bg-black/30'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'
                            "
                            class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full font-semibold"
                        >
                            {{ totalesEstatusQuery.todas }}
                        </span>
                    </button>

                    <!-- EN TRÁMITE -->
                    <button
                        type="button"
                        :disabled="totalesEstatusQuery.enTramite === 0"
                        @click="estatusQuery = 'enTramite'; realizarBusqueda();"
                        :style="
                            estatusQuery === 'enTramite' && totalesEstatusQuery.enTramite > 0
                                ? { backgroundColor: getStatusColor('warning'), borderColor: getStatusColor('warning') }
                                : {}
                        "
                        :class="[
                            // Añadimos flex-wrap o control responsivo para evitar alturas excesivas
                            'px-2.5 py-2 sm:px-3 sm:py-2 text-[11px] sm:text-xs uppercase tracking-wider rounded-full transition-all duration-200 ease-out flex flex-wrap sm:flex-nowrap items-center gap-1.5 shrink-0',
                            'focus:outline-none focus-visible:ring-2 focus-visible:ring-color1 focus-visible:ring-offset-1',
                            totalesEstatusQuery.enTramite === 0
                                ? 'opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 border border-gray-200 dark:border-gray-800'
                                : estatusQuery === 'enTramite'
                                    ? 'cursor-pointer text-white shadow-sm font-bold border hover:-translate-y-0.5 hover:shadow-md'
                                    : 'cursor-pointer bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-800 hover:border-gray-400 dark:hover:border-gray-600 hover:-translate-y-0.5 hover:shadow-md hover:text-gray-900 dark:hover:text-white font-semibold'
                        ]"
                    >
                        <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: estatusQuery === 'enTramite' ? '#ffffff' : getStatusColor('warning') }"></span>
                        EN TRÁMITE
                        <span :class="estatusQuery === 'enTramite' ? 'bg-black/20 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'" class="text-[10px] ml-1 px-1.5 py-0.5 rounded-full font-semibold">
                            {{ totalesEstatusQuery.enTramite }}
                        </span>
                    </button>

                    <!-- APROBADAS -->
                     <button
                        type="button"
                        :disabled="totalesEstatusQuery.aprobadas === 0"
                        @click="estatusQuery = 'aprobadas'; realizarBusqueda();"
                        :style="
                            estatusQuery === 'aprobadas' && totalesEstatusQuery.aprobadas > 0
                                ? { backgroundColor: getStatusColor('primary'), borderColor: getStatusColor('primary') }
                                : {}
                        "
                        :class="[
                            'px-2.5 py-2 sm:px-3 sm:py-2 text-[11px] sm:text-xs uppercase tracking-wider rounded-full transition-all duration-200 ease-out flex items-center gap-1.5 shrink-0',
                            'focus:outline-none focus-visible:ring-2 focus-visible:ring-color1 focus-visible:ring-offset-1',
                            totalesEstatusQuery.aprobadas === 0
                                ? 'opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 border border-gray-200 dark:border-gray-800'
                                : estatusQuery === 'aprobadas'
                                    ? 'cursor-pointer text-white shadow-sm font-bold border hover:-translate-y-0.5 hover:shadow-md'
                                    : 'cursor-pointer bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-800 hover:border-gray-400 dark:hover:border-gray-600 hover:-translate-y-0.5 hover:shadow-md hover:text-gray-900 dark:hover:text-white font-semibold'
                        ]"
                    >
                        <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: estatusQuery === 'aprobadas' ? '#ffffff' : getStatusColor('primary') }"></span>
                        APROBADAS
                        <span :class="estatusQuery === 'aprobadas' ? 'bg-black/20 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'" class="text-[10px] ml-1 px-1.5 py-0.5 rounded-full font-semibold">
                            {{ totalesEstatusQuery.aprobadas }}
                        </span>
                    </button>

                    <!-- APOYO ENTREGADO -->
                    <button
                        type="button"
                        :disabled="totalesEstatusQuery.apoyoEntregado === 0"
                        @click="estatusQuery = 'apoyoEntregado'; realizarBusqueda();"
                        :style="
                            estatusQuery === 'apoyoEntregado' && totalesEstatusQuery.apoyoEntregado > 0
                                ? { backgroundColor: getStatusColor('success'), borderColor: getStatusColor('success') }
                                : {}
                        "
                        :class="[
                            'px-2.5 py-2 sm:px-3 sm:py-2 text-[11px] sm:text-xs uppercase tracking-wider rounded-full transition-all duration-200 ease-out flex items-center gap-1.5 shrink-0',
                            'focus:outline-none focus-visible:ring-2 focus-visible:ring-color1 focus-visible:ring-offset-1',
                            totalesEstatusQuery.apoyoEntregado === 0
                                ? 'opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 border border-gray-200 dark:border-gray-800'
                                : estatusQuery === 'apoyoEntregado'
                                    ? 'cursor-pointer text-white shadow-sm font-bold border hover:-translate-y-0.5 hover:shadow-md'
                                    : 'cursor-pointer bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-800 hover:border-gray-400 dark:hover:border-gray-600 hover:-translate-y-0.5 hover:shadow-md hover:text-gray-900 dark:hover:text-white font-semibold'
                        ]"
                    >
                        <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: estatusQuery === 'apoyoEntregado' ? '#ffffff' : getStatusColor('success') }"></span>
                        APOYO ENTREGADO
                        <span :class="estatusQuery === 'apoyoEntregado' ? 'bg-black/20 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'" class="text-[10px] ml-1 px-1.5 py-0.5 rounded-full font-semibold">
                            {{ totalesEstatusQuery.apoyoEntregado }}
                        </span>
                    </button>

                    <!-- CANCELADAS -->
                    <button
                        type="button"
                        :disabled="totalesEstatusQuery.canceladas === 0"
                        @click="estatusQuery = 'canceladas'; realizarBusqueda();"
                        :style="
                            estatusQuery === 'canceladas' && totalesEstatusQuery.canceladas > 0
                                ? { backgroundColor: getStatusColor('danger'), borderColor: getStatusColor('danger') }
                                : {}
                        "
                        :class="[
                            'px-2.5 py-2 sm:px-3 sm:py-2 text-[11px] sm:text-xs uppercase tracking-wider rounded-full transition-all duration-200 ease-out flex items-center gap-1.5 shrink-0',
                            'focus:outline-none focus-visible:ring-2 focus-visible:ring-color1 focus-visible:ring-offset-1',
                            totalesEstatusQuery.canceladas === 0
                                ? 'opacity-50 cursor-not-allowed bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 border border-gray-200 dark:border-gray-800'
                                : estatusQuery === 'canceladas'
                                    ? 'cursor-pointer text-white shadow-sm font-bold border hover:-translate-y-0.5 hover:shadow-md'
                                    : 'cursor-pointer bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-800 hover:border-gray-400 dark:hover:border-gray-600 hover:-translate-y-0.5 hover:shadow-md hover:text-gray-900 dark:hover:text-white font-semibold'
                        ]"
                    >
                        <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: estatusQuery === 'canceladas' ? '#ffffff' : getStatusColor('danger') }"></span>
                        CANCELADAS
                        <span :class="estatusQuery === 'canceladas' ? 'bg-black/20 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'" class="text-[10px] ml-1 px-1.5 py-0.5 rounded-full font-semibold">
                            {{ totalesEstatusQuery.canceladas }}
                        </span>
                    </button>
                </div>

                <!-- Select de Áreas (Abajo en móviles, a la derecha en escritorio) -->
                <el-select
                    v-model="areasQuery"
                    class="custom-select-gray pt-2 w-full sm:w-auto sm:ml-12"
                    collapse-tags
                    collapse-tags-tooltip
                    :max-collapse-tags="1"
                    multiple
                    placeholder="Buscar por área(s)"
                    value-key="value"
                    @change="realizarBusqueda()">
                    <template #prefix>
                        <div class="hidden sm:flex items-center ml-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2 text-gray-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M11 19h-6a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h4l3 3h7a2 2 0 0 1 2 2v2.5" />
                                <path d="M18 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                <path d="M20.2 20.2l1.8 1.8" />
                            </svg>
                        </div>
                    </template>
                    <el-option v-for="item in areasList" :key="item.id" :label="item.nombre" :value="item.id">
                        <div class="flex items-center">
                            <span class="w-4 h-5 mr-2"></span>
                            <span class="text-xs">{{ item.nombre }}</span>
                        </div>
                    </el-option>
                </el-select>
            </div>
        </div>


        <!-- Contenedor de solicitudes -->
        <div class="bg-white dark:bg-gray-900/50 border border-gray-200 shadow-sm p-3 sm:p-4 rounded-xl">
            <!-- Grid adaptable tipo SaaS Dashboard (gap más ajustado) -->
            <div v-if="solicitudes && solicitudes.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <div
                    v-for="solicitud in solicitudes"
                    :key="solicitud.id"
                    @click="manejarClickCard(solicitud)"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-xl border bg-white shadow-sm transition-all duration-200 dark:bg-gray-800/90"
                    :class="{
                        /* Clases e interactividad SOLO si está ACTIVA */
                        'border-gray-300/60 hover:border-2 hover:border-color1-600 hover:shadow-md dark:border-gray-800 dark:hover:border-color1-500 cursor-pointer': !esTerminal(solicitud.id_estatus),
                        
                        /* Clases estáticas y sin hover si es TERMINAL */
                        'border-gray-200 dark:border-gray-800/80 cursor-default': esTerminal(solicitud.id_estatus)
                    }"
                >
                    <div class="flex items-center justify-between gap-2 px-3 py-2.5 border-b border-gray-100 dark:border-gray-700/60">
                        <!-- Badge / Pastilla Neutral -->
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-700/60 text-gray-900 dark:text-white">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-400">
                                Solicitud
                            </span>
                            <span class="text-xs font-bold tracking-tight">
                                #&thinsp;{{ solicitud.id ? (solicitud.id % 100000).toString().padStart(5, '0') : '-' }}
                            </span>
                        </div>

                        <!-- Botones de Acción Rápida -->
                        <div class="flex items-center gap-1 shrink-0" @click.stop>
                            <!-- Tus botones aquí -->
                        </div>
                    </div>

                    <!-- Contenido Principal (Espaciado interno más compacto) -->
                    <div class="flex-1 p-3 space-y-2.5 text-left">
                        <!-- Solicitante -->
                        <div class="flex items-start gap-2">
                            <div class="mt-0.5 p-1.5 rounded-lg bg-gray-100 dark:bg-gray-700/50 text-color1 shrink-0">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1"
                            :class="{ 'opacity-50 grayscale-[30%]': Number(solicitud.id_estatus) === 4 }">
                                <!-- Nombre tachado condicionalmente -->
                                <p class="text-xs mb-0 font-bold text-gray-900 dark:text-white truncate" :class="{ 'line-through': Number(solicitud.id_estatus) === 4 }">
                                    {{ solicitud.solicitante?.nombre }} {{ solicitud.solicitante?.apellidos }}
                                </p>
                                <p v-if="solicitud.solicitante?.curp" class="text-[11px] mt-0 text-gray-700 dark:text-white truncate" :class="{ 'line-through': Number(solicitud.id_estatus) === 4 }">
                                    {{ solicitud.solicitante?.curp }}
                                </p>

                                <!-- Localidad y Teléfono -->
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400 mt-0.5" :class="{ 'line-through': Number(solicitud.id_estatus) === 4 }">
                                    <span v-if="solicitud.solicitante?.id_localidad" class="truncate">
                                        {{ solicitud.solicitante.localidad?.nombre }}
                                    </span>
                                    <span v-if="solicitud.solicitante?.id_localidad && solicitud.solicitante.numero_telefonico" class="text-gray-300 dark:text-gray-700">
                                        •
                                    </span>
                                    <span v-if="solicitud.solicitante?.numero_telefonico" class="truncate">
                                        {{ solicitud.solicitante.numero_telefonico }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Metadatos (Caja interna más compacta) -->
                        <div class="rounded-xl bg-gray-50/80 dark:bg-gray-900/40 p-3 space-y-2 border border-gray-100 dark:border-gray-800/80 text-xs">
                            <!-- Fecha -->
                            <div class="flex items-center justify-between gap-3"
                            :class="{ 'opacity-50 grayscale-[30%] line-through': Number(solicitud.id_estatus) === 4 }">
                                <span class="text-gray-500 dark:text-gray-400 font-medium flex items-center gap-1.5">
                                    Fecha de solicitud
                                </span>
                                <span class="text-gray-800 dark:text-gray-200 font-semibold">
                                    {{ formatDate(solicitud.fecha) }}
                                </span>
                            </div>

                            <!-- Área -->
                            <div v-if="solicitud.id_area" class="flex items-center justify-between gap-3"
                            :class="{ 'opacity-50 grayscale-[30%] line-through': Number(solicitud.id_estatus) === 4 }">
                                <span class="text-gray-500 dark:text-gray-400 font-medium flex items-center gap-1.5">
                                    Área
                                </span>
                                <span class="text-gray-800 dark:text-gray-200 font-semibold truncate max-w-[180px]" :title="solicitud.area?.nombre">
                                    {{ solicitud.area?.nombre }}
                                </span>
                            </div>

                            <!-- Estatus (Se le quita el tachado explícitamente para que el badge se mantenga intacto) -->
                            <div class="flex items-center justify-between gap-3 no-underline" style="isolation: isolate;">
                                <span :class="{ 'opacity-50 grayscale-[30%]': Number(solicitud.id_estatus) === 4 }" class="text-gray-500 dark:text-gray-400 font-medium">Estatus</span>
                                <span
                                    class="no-underline inline-flex items-center gap-1.5 rounded-md px-2.5 py-0.5 text-[11px] font-semibold tracking-wide"
                                    :style="{
                                        color: getStatusColor(solicitud.estatus.color),
                                        backgroundColor: `${getStatusColor(solicitud.estatus.color)}15`,
                                        borderColor: `${getStatusColor(solicitud.estatus.color)}30`,
                                        opacity: '1 !important'
                                    }"
                                    style="border-width: 1px;"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :style="{ backgroundColor: getStatusColor(solicitud.estatus.color) }"></span>
                                    {{ solicitud.estatus.nombre }}
                                </span>
                            </div>

                            <!-- Monto aprobado -->
                            <div v-if="solicitud.cantidad_aprobada" class="flex items-center justify-between gap-3 pt-1.5 border-t border-gray-200/60 dark:border-gray-800"
                            :class="{ 'opacity-50 grayscale-[30%] line-through': Number(solicitud.id_estatus) === 4 }">
                                <span class="text-gray-600 dark:text-gray-300 font-semibold">Cant. aprobada</span>
                                <span class="font-bold tracking-tight text-sm" :style="{ color: getStatusColor(solicitud.estatus.color) }">
                                    $ {{ Number(solicitud.cantidad_aprobada).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} 
                                    <span class="text-[10px] font-normal opacity-75 relative inline-block">
                                        MXN
                                        <span v-if="Number(solicitud.id_estatus) === 4" class="absolute inset-x-0 top-1/2 h-[1px] bg-current -translate-y-1/2"></span>
                                    </span>
                                </span>
                            </div>
                        </div>

                        <!-- Petición -->
                        <div v-if="solicitud.peticion" class="space-y-0.5">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500" :class="{ 'line-through opacity-60 grayscale-[30%]': Number(solicitud.id_estatus) === 4 }">
                                Petición
                            </span>
                            <p class="text-[11px] leading-snug text-gray-600 dark:text-gray-300 line-clamp-2 italic" :class="{ 'line-through opacity-60 grayscale-[30%]': Number(solicitud.id_estatus) === 4 }">
                                {{ solicitud.peticion }}
                            </p>
                        </div>

                        <div v-if="solicitud.observaciones" class="rounded-lg bg-gray-200/60 dark:bg-gray-950/20 px-2.5 py-1.5 border border-gray-200/50 dark:border-gray-900/40 text-[11px] space-y-0.5" :class="{ 'opacity-70 grayscale-[30%]' : Number(solicitud.id_estatus) === 4 }">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400" :class="{ 'line-through opacity-60 grayscale-[30%]': Number(solicitud.id_estatus) === 4 }">
                                Observaciones
                            </span>
                            <p class="leading-snug text-gray-600 dark:text-gray-300 line-clamp-2 italic" :class="{ 'line-through opacity-60 grayscale-[30%]': Number(solicitud.id_estatus) === 4 }">
                                {{ solicitud.observaciones }}
                            </p>
                        </div>

                
                    </div>

                    <!-- Pie de Tarjeta (Más delgado) -->
                    <div class="px-3 py-2 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 truncate pr-2">
                            <template v-if="solicitud.id_estatus == 1 && solicitud.user">
                                Actualizada por <strong class="font-medium text-gray-600 dark:text-gray-300">{{ solicitud.user.nickname }}</strong>
                            </template>
                            <template v-else-if="solicitud.id_estatus == 2 && solicitud.user">
                                Aprobada por <strong class="font-medium text-gray-600 dark:text-gray-300">{{ solicitud.user.nickname }}</strong>
                            </template>
                            <template v-if="solicitud.id_estatus == 3 && solicitud.user">
                                Se entregó el apoyo el <strong class="font-medium text-gray-600 dark:text-gray-300"> {{ formatDate(solicitud.fecha_resolucion) }} </strong> por <strong class="font-medium text-gray-600 dark:text-gray-300">{{ solicitud.user.nickname }}</strong>
                            </template>
                            <template v-else-if="solicitud.id_estatus == 4 && solicitud.user">
                                Cancelada el <strong class="font-medium text-gray-600 dark:text-gray-300"> {{ formatDate(solicitud.fecha_resolucion) }} </strong>  por <strong class="font-medium text-gray-600 dark:text-gray-300">{{ solicitud.user.nickname }}</strong>
                            </template>
                        </span>

                       <!-- Indicador visual condicional -->
                        <span 
                            v-if="!esTerminal(solicitud.id_estatus)" 
                            class="text-gray-400 group-hover:text-color1-500 transition-colors shrink-0">
                            <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    </div>
                </div>

            </div>
            <!-- Estado vacío dinámico -->
            <div v-else class="flex flex-col items-center justify-center py-12 px-4 text-center">
                <div class="p-3 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 mb-3">
                    <!-- SVG de Lupa (Cuando hay una búsqueda activa y no hay resultados) -->
                    <svg v-if="searchQuery || areasQuery" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>

                    <!-- SVG de Bandeja Vacía / Documentos (Cuando la lista está vacía por defecto) -->
                    <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    {{ totalSolicitudes ? 'No se encontraron resultados para tu búsqueda' : 'No hay solicitudes registradas' }}
                </p>
                <p class="text-xs mb-6 text-gray-400 dark:text-gray-500 mt-1">
                    {{ totalSolicitudes ? 'Intenta con otros términos o limpia los filtros.' : 'Las solicitudes que vayas agregando aparecerán aquí.' }}
                </p>
                 <button
                @click="abreModalSolicitud"
                v-if="!totalSolicitudes"
                type="button"
                class="inline-flex items-center justify-center gap-2
                        rounded-full bg-color1-600 hover:bg-color1-500
                        text-white text-xs font-semibold px-4 py-2.5
                        shadow-lg transition-colors
                        focus:outline-none focus:ring-2
                        focus:ring-color1-500 focus:ring-offset-2
                        w-full sm:w-auto">

                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4" />
                </svg>

                Nueva Solicitud
            </button>
            </div>
        </div>

        <!-- Conserva la paginación y el indicador de carga originales -->
        <Pagination
            :data="pagination"
            @page-changed="handlePageChange"
            item-name="solicitud"
            plural-name="solicitudes"
        />

    </div>
</template>


