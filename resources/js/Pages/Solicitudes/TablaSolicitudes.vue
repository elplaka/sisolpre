<style>
input[type="search"]::-webkit-search-cancel-button {
    cursor: pointer;
}

    .sin-solicitudes {
        background-color: #f9fafc !important;
        color: rgba(17, 24, 39, 0.7) !important; /* Tu color deseado */
        cursor: not-allowed !important;
        opacity: 40%;
        /* Usamos position relative para poner una capa invisible que bloquee los clics pero conserve los estilos */
    }

    /* Truco definitivo: Ponemos un pseudo-elemento transparente encima que absorba los clics (bloquea el select) pero deje ver los estilos y el cursor */
    .sin-solicitudes {
        position: relative;
    }

    /* Creamos una capa invisible que bloquea el clic */
    .sin-solicitudes::after {
        content: '';
        position: absolute;
        inset: 0;
        cursor: not-allowed !important;
        z-index: 10;
    }

    /* Soporte para modo oscuro */
    .dark .sin-solicitudes {
        background-color: rgba(31, 41, 55, 0.7) !important;
        color: rgba(199, 199, 199, 0.7) !important;
    }

   /* Color clarito permanente únicamente para la opción de "SELECCIONE UN ÁREA" */
    .option-placeholder {
        color: #9ca3af !important; /* text-gray-400 */
    }

    /* Control del select cuando está en su estado inicial vacío */
    .select-placeholder-activo {
        color: #9ca3af !important;
    }

    /* Aseguramos que las demás opciones tengan su color normal al desplegar */
    select option:not(.option-placeholder) {
        color: #111827 !important; /* text-gray-900 */
        background-color: #ffffff !important;
    }

    /* Soporte para modo oscuro */
    .dark select option:not(.option-placeholder) {
        color: #ffffff !important;
        background-color: #1f2937 !important;
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
    const sortCriteriaQuery = ref('fecha_asc');  // 'asc' o 'desc'

    const realizarBusqueda = () => {
        router.post(
            route('solicitudes'), // O la ruta específica de tu backend, ej: route('tu.ruta.index')
            {
                searchQuery: searchQuery.value ? searchQuery.value.trim() : null,
                fechaIngresoInicioQuery: rangoFechasIngresoQuery.value?.[0] || null,
                fechaIngresoFinQuery: rangoFechasIngresoQuery.value?.[1] || null,
                estatusQuery: estatusQuery.value,
                areasQuery: areasQuery.value,
                sortCriteriaQuery: sortCriteriaQuery.value,
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
        fechaIngresoInicioQuery: String,
        fechaIngresoFinQuery: String,
        fechaAceptacionInicioQuery: String,
        fechaAceptacionFinQuery: String,
        idRangoFechasIngresoQuery: Number,
        idRangoFechasAceptacionQuery: Number,
        rangoFechasIngresoManual: Boolean,
        estatusQuery: String,
        totalesEstatusQuery: Object,
        areasList: Array,
        estatusList: Array,
        totalSolicitudes: Number,
        isPage: Boolean,
        periodoActual: Object,
        desdeSideBar: {
            type: Boolean,
            default: false
        },
        page: {
            type: [Number, String],
            default: 1
        }
    })

    const intervalo = ref(null)
    const intervaloMs = 10000 // 👈 ajusta aquí la frecuencia del polling

    //Parámetros para filtrar
    const folioQuery = ref('')
    const nombreQuery = ref('')
    const fechaIngresoInicioQuery = ref('')
    const fechaIngresoFinQuery = ref('')
    const fechaAceptacionInicioQuery = ref('')
    const fechaAceptacionFinQuery = ref('')
    const claveCatastralQuery = ref('')
    const rangoFechasIngresoQuery = ref([])
    const rangoFechasAceptacionQuery = ref([])
    const rangoFechasIngresoManual = ref(false)
    const rangoFechasAceptacionManual = ref(false)
    const rangoFechasShortcut = ref(false)
    const rangoFechasAceptacionShortcut = ref(false)
    const idRangoFechasIngresoQuery = ref(null)
    const idRangoFechasAceptacionQuery = ref(99)
    const periodoActual = ref(props.periodoActual || {})

    const dropdownVisible = ref(null)

    const isLoading = ref(false)
    const isSearching = ref(false)

    const abreModalSolicitud = async () => {
        solicitudSeleccionada.value = null
        showModal.value = true;
    }

    const soloLectura = ref(false);

    const abreModalEditarSolicitud = async (solicitud) => {
        solicitudSeleccionada.value = solicitud;
        if (solicitud.id_estatus === 3)
        {
            soloLectura.value = true
        }
            
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

    // Función para limpiar/detener el intervalo
    const detenerIntervalo = () => {
        if (intervalo.value) {
            clearInterval(intervalo.value);
            intervalo.value = null;
        }
    };

    // Vigilar el estado de la ventana modal
    watch(() => showModal.value, (isOpen) => {
        if (isOpen) {
            // Si el modal se ABRE, detenemos las peticiones automáticas
            detenerIntervalo();
        } else {
            // Si el modal se CIERRA, volvemos a arrancar el intervalo
            iniciarIntervalo();
        }
    });

    onMounted(() => {
        fechaIngresoInicioQuery.value = props.fechaIngresoInicioQuery
        fechaIngresoFinQuery.value = props.fechaIngresoFinQuery
        idRangoFechasIngresoQuery.value = Number(props.idRangoFechasIngresoQuery)
        rangoFechasIngresoManual.value = props.rangoFechasIngresoManual

        estatusQuery.value = props.estatusQuery ?? 'todas'

        rangoFechasIngresoQuery.value = [
            props.fechaIngresoInicioQuery,
            props.fechaIngresoFinQuery,
        ]

        rangoFechasAceptacionQuery.value = [
            props.fechaAceptacionInicioQuery ?? null,
            props.fechaAceptacionFinQuery ?? null,
        ]

        document.addEventListener('click', handleClickOutside)
        window.addEventListener('resize', updateSize)

        // Un solo intervalo de actualización automática.
        iniciarIntervalo()

        document.getElementById('initial-loader')?.remove();
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

        urlParams.set('estatusQuery', estatusQuery.value)


        urlParams.set('page', page)

         return Object.fromEntries(urlParams.entries())
    }

    function fetchSolicitudes(onMounted, polling = false) {
        const page = router.page.props.solicitudes?.current_page ?? 1
        const paramsObject = buildQueryParams(page)

        let showLoaderTimeout = null

        if (onMounted) 
        {
        } 
        else 
        {
            if (!polling) {
                showLoaderTimeout = setTimeout(() => {
                    isSearching.value = true
                }, 200)
            }
        }

        // 1. Definimos los filtros directamente como un objeto plano (JSON)
        const filtrosData = {
            fechaIngresoInicioQuery: fechaIngresoInicioQuery.value,
            fechaIngresoFinQuery: formatFechaLocal(fechaIngresoFinQuery.value),
            fechaAceptacionInicioQuery: fechaAceptacionInicioQuery.value,
            fechaAceptacionFinQuery: fechaAceptacionFinQuery.value,
            searchQuery: searchQueryConfirmado.value,
            estatusQuery: estatusQuery.value,
            areasQuery: areasQuery.value,
            sortCriteriaQuery: sortCriteriaQuery.value,
            idRangoFechasIngresoQuery: idRangoFechasIngresoQuery.value,
            rangoFechasIngresoManual: rangoFechasIngresoManual.value,
            idRangoFechasAceptacionQuery: idRangoFechasAceptacionQuery.value,
            rangoFechasAceptacionManual: rangoFechasAceptacionManual.value,
            page: Number(paramsObject.page) || 1
        }

        console.log('FILTROS DATA', filtrosData)

        // 2. Define las opciones base de la solicitud
        const requestOptions = {
            preserveState: true,
            replace: true,
            preserveScroll: true,
            onStart: () => {
                // Lógica común de inicio
            },
            onFinish: () => {
                if (polling) {
                    window.__solicitudesPolling = false;
                }

                const currentPage = router.page.props.solicitudes.current_page;
                const lastPage = router.page.props.solicitudes.last_page;

                if (currentPage > lastPage)   // Si la página actual excede al número de páginas obtenidas
                {
                    const newFilters = { 
                        ...router.page.props.filters, 
                        page: Number(1)
                    };

                    const dataPeticion = {
                        ...newFilters,
                        _background: true
                    };

                    router.get(
                        router.page.url.split('?')[0],
                        dataPeticion,
                        {
                            preserveState: true,
                            preserveScroll: true,
                            showProgress: false
                        }
                    );
                }

                if (onMounted) {
                    isLoading.value = false
                } else {
                    clearTimeout(showLoaderTimeout)
                    isSearching.value = false
                }
            }
        }

        // 3. Añade la propiedad 'only' solo si es polling
        if (polling) {
            requestOptions.only = ['solicitudes', 'totalesEstatusQuery']
        }

        if (polling) {
            window.__solicitudesPolling = true;
        }

        // 4. Enviamos el objeto plano como segundo parámetro
        router.post('/solicitudes', filtrosData, requestOptions)
    }

   function onDateChange(fechaSeleccionada) {
        function resetToLocalMidnight(dateStringOrDate) {
            const d = typeof dateStringOrDate === 'string'
                ? new Date(dateStringOrDate + 'T00:00:00') // fuerza hora local
                : new Date(dateStringOrDate)
            d.setHours(0, 0, 0, 0)
            return d
        }

        // Si el usuario limpia o borra las fechas, manejamos un caso base
        if (!fechaSeleccionada || !fechaSeleccionada[0] || !fechaSeleccionada[1]) {
            return;
        }

        // Buscamos si coincide exactamente con algún shortcut
        const shortcutEncontrado = shortcuts.value.find(sc => {
            const [start, end] = sc.value()

            const seleccionadoStart = resetToLocalMidnight(fechaSeleccionada[0])
            const seleccionadoEnd = resetToLocalMidnight(fechaSeleccionada[1])

            return resetToLocalMidnight(start).getTime() === seleccionadoStart.getTime() &&
                resetToLocalMidnight(end).getTime() === seleccionadoEnd.getTime()
        })

        const coincideConShortcut = !!shortcutEncontrado

        console.log('DDDD')
        rangoFechasIngresoManual.value = !coincideConShortcut
        rangoFechasShortcut.value = coincideConShortcut

        if (coincideConShortcut) {
            // Si coincidió con un shortcut, asignamos su ID correspondiente
            idRangoFechasIngresoQuery.value = shortcutEncontrado.id
        } else {
            // Si fue manual, le asignamos el ID de Custom (por ejemplo, 99)
            idRangoFechasIngresoQuery.value = 99 
        }

        // Asignamos las fechas correctamente desde el arreglo recibido (fechaSeleccionada)
        // Asegurándonos de formatearlas a 'YYYY-MM-DD' si vienen como objetos Date
        fechaIngresoInicioQuery.value = formatFechaLocal(fechaSeleccionada[0])
        fechaIngresoFinQuery.value = formatFechaLocal(fechaSeleccionada[1])

        fetchSolicitudes(false)
    }

    // Función auxiliar recomendada para formatear objetos Date a 'YYYY-MM-DD' sin problemas de zona horaria
    function formatFechaLocal(date) {
        if (!date) return '';

        // Si ya es una cadena de texto en formato YYYY-MM-DD, devuélvela directamente
        if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
            return date;
        }

        // Si por alguna razón llega un objeto Date, lo procesamos de forma segura con UTC
        const d = new Date(date);
        const year = d.getUTCFullYear();
        const month = String(d.getUTCMonth() + 1).padStart(2, '0');
        const day = String(d.getUTCDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function onDateChangeAceptacion(fechaSeleccionada) {
      function resetToLocalMidnight(dateStringOrDate) {
            const d = typeof dateStringOrDate === 'string'
                ? new Date(dateStringOrDate + 'T00:00:00') // fuerza hora local
                : new Date(dateStringOrDate)
            d.setHours(0, 0, 0, 0)
            return d
        }

        const coincideConShortcut = shortcuts.value.some(sc => {
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

   const shortcuts = computed(() => [
        {
            id: 2,
            text: `Año Calendario Actual (${new Date().getFullYear()})`,
            value: () => {
                const currentYear = new Date().getFullYear()

                fechaIngresoInicioQuery.value = `${currentYear}-01-01`
                fechaIngresoFinQuery.value = `${currentYear}-12-31`
                idRangoFechasIngresoQuery.value = 2
                idRangoFechasAceptacionQuery.value = 2
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                return [
                    new Date(currentYear, 0, 1), 
                    new Date(currentYear, 11, 31)
                ]
            }
        },
        {
            id: 3,
            text: `Año Calendario Anterior (${new Date().getFullYear() - 1})`,
            value: () => {
                const currentYear = new Date().getFullYear() - 1
                
                idRangoFechasIngresoQuery.value = 3
                idRangoFechasAceptacionQuery.value = 3
                rangoFechasShortcut.value = true
                rangoFechasIngresoManual.value = false
                rangoFechasAceptacionManual.value = false

                fechaIngresoInicioQuery.value = `${currentYear}-01-01`
                fechaIngresoFinQuery.value = `${currentYear}-12-31`

                return [
                    new Date(currentYear, 0, 1), 
                    new Date(currentYear, 11, 31)
                ]
            }
        },
        {
            id: 4,
            get text() {
                const today = new Date();
                const [baseYear, baseMonth, baseDay] = periodoActual.value.inicio.split('-').map(Number);
                
                let currentYear = today.getFullYear();
                let currentMonth = today.getMonth() + 1;
                let currentDay = today.getDate();

                if (currentMonth < baseMonth || (currentMonth === baseMonth && currentDay < baseDay)) {
                    currentYear -= 1;
                }

                const startYY = String(currentYear).slice(-2);
                const endYY = String(currentYear + 1).slice(-2);

                return `Año de Gob. Actual (Nov. '${startYY} - Oct. '${endYY})`;
            },
            value: () => {
                const today = new Date();
                const [baseYear, baseMonth, baseDay] = periodoActual.value.inicio.split('-').map(Number);
                
                let currentYear = today.getFullYear();
                let currentMonth = today.getMonth() + 1;
                let currentDay = today.getDate();

                if (currentMonth < baseMonth || (currentMonth === baseMonth && currentDay < baseDay)) {
                    currentYear -= 1;
                }

                const start = new Date(currentYear, baseMonth - 1, baseDay);
                const end = new Date(currentYear + 1, baseMonth - 1, baseDay);
                end.setDate(end.getDate() - 1); // 31 de octubre

                idRangoFechasIngresoQuery.value = 4;
                idRangoFechasAceptacionQuery.value = 4;
                rangoFechasShortcut.value = true;
                rangoFechasIngresoManual.value = false;
                rangoFechasAceptacionManual.value = false;

                const endYear = currentYear + 1;
                fechaIngresoInicioQuery.value = `${currentYear}-${String(baseMonth).padStart(2, '0')}-${String(baseDay).padStart(2, '0')}`;
                fechaIngresoFinQuery.value = `${endYear}-10-31`;

                return [start, end];
            }
        },
        {
            id: 5,
            get text() {
                const today = new Date();
                const [baseYear, baseMonth, baseDay] = periodoActual.value.inicio.split('-').map(Number);
                
                let currentYear = today.getFullYear();
                let currentMonth = today.getMonth() + 1;
                let currentDay = today.getDate();

                if (currentMonth < baseMonth || (currentMonth === baseMonth && currentDay < baseDay)) {
                    currentYear -= 1;
                }

                const prevYear = currentYear - 1;
                const startYY = String(prevYear).slice(-2);
                const endYY = String(prevYear + 1).slice(-2);

                return `Año de Gob. Anterior (Nov '${startYY} - Oct '${endYY})`;
            },
            value: () => {
                const today = new Date();
                const [baseYear, baseMonth, baseDay] = periodoActual.value.inicio.split('-').map(Number);
                
                let currentYear = today.getFullYear();
                let currentMonth = today.getMonth() + 1;
                let currentDay = today.getDate();

                if (currentMonth < baseMonth || (currentMonth === baseMonth && currentDay < baseDay)) {
                    currentYear -= 1;
                }

                const prevYear = currentYear - 1;
                const start = new Date(prevYear, baseMonth - 1, baseDay);
                const end = new Date(prevYear + 1, baseMonth - 1, baseDay);
                end.setDate(end.getDate() - 1);

                idRangoFechasIngresoQuery.value = 5;
                idRangoFechasAceptacionQuery.value = 5;
                rangoFechasShortcut.value = true;
                rangoFechasIngresoManual.value = false;
                rangoFechasAceptacionManual.value = false;

                const endYear = prevYear + 1;
                fechaIngresoInicioQuery.value = `${prevYear}-${String(baseMonth).padStart(2, '0')}-${String(baseDay).padStart(2, '0')}`;
                fechaIngresoFinQuery.value = `${endYear}-10-31`;

                return [start, end];
            }
        },
        {
            id: 6,
            text: 'Semana Actual',
            value: () => {
                const now = new Date();
                const day = now.getDay();
                // Ajuste para que la semana inicie en lunes (si es domingo, day es 0)
                const diffToMonday = now.getDate() - day + (day === 0 ? -6 : 1);
                
                const start = new Date(now.setDate(diffToMonday));
                start.setHours(0, 0, 0, 0);
                
                const end = new Date(start);
                end.setDate(start.getDate() + 6);
                end.setHours(0, 0, 0, 0);

                idRangoFechasIngresoQuery.value = 6;
                idRangoFechasAceptacionQuery.value = 6;
                rangoFechasShortcut.value = true;
                rangoFechasIngresoManual.value = false;
                rangoFechasAceptacionManual.value = false;

                const formatDateStr = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                fechaIngresoInicioQuery.value = formatDateStr(start);
                fechaIngresoFinQuery.value = formatDateStr(end);

                return [start, end];
            }
        },
        {
            id: 7,
            text: 'Semana Anterior',
            value: () => {
                const now = new Date();
                const day = now.getDay();
                const diffToMonday = now.getDate() - day + (day === 0 ? -6 : 1) - 7;
                
                const start = new Date(now.setDate(diffToMonday));
                start.setHours(0, 0, 0, 0);
                
                const end = new Date(start);
                end.setDate(start.getDate() + 6);
                end.setHours(0, 0, 0, 0);

                idRangoFechasIngresoQuery.value = 7;
                idRangoFechasAceptacionQuery.value = 7;
                rangoFechasShortcut.value = true;
                rangoFechasIngresoManual.value = false;
                rangoFechasAceptacionManual.value = false;

                const formatDateStr = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                fechaIngresoInicioQuery.value = formatDateStr(start);
                fechaIngresoFinQuery.value = formatDateStr(end);

                return [start, end];
            }
        },
        {
            id: 8,
            text: 'Mes Actual',
            value: () => {
                const now = new Date();
                const start = new Date(now.getFullYear(), now.getMonth(), 1);
                const end = new Date(now.getFullYear(), now.getMonth() + 1, 0); // Último día del mes actual

                idRangoFechasIngresoQuery.value = 8;
                idRangoFechasAceptacionQuery.value = 8;
                rangoFechasShortcut.value = true;
                rangoFechasIngresoManual.value = false;
                rangoFechasAceptacionManual.value = false;

                const formatDateStr = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                fechaIngresoInicioQuery.value = formatDateStr(start);
                fechaIngresoFinQuery.value = formatDateStr(end);

                return [start, end];
            }
        },
        {
            id: 9,
            text: 'Mes Anterior',
            value: () => {
                const now = new Date();
                const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                const end = new Date(now.getFullYear(), now.getMonth(), 0); // Último día del mes anterior

                idRangoFechasIngresoQuery.value = 9;
                idRangoFechasAceptacionQuery.value = 9;
                rangoFechasShortcut.value = true;
                rangoFechasIngresoManual.value = false;
                rangoFechasAceptacionManual.value = false;

                const formatDateStr = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                fechaIngresoInicioQuery.value = formatDateStr(start);
                fechaIngresoFinQuery.value = formatDateStr(end);

                return [start, end];
            }
        }
    ])

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

    const fechaInicio = ref(props.fechaIngresoInicioQuery);
    const fechaFin = ref(props.fechaIngresoFinQuery);

// Watch para monitorear y comparar lo que llega por props al componente hijo
watch(
    () => [props.fechaIngresoInicioQuery, props.fechaIngresoFinQuery],
    ([nuevoInicio, nuevoFin]) => {
        // Sincronizamos con las variables internas de la tabla
        fechaInicio.value = nuevoInicio;
        fechaFin.value = nuevoFin;
    },
    { immediate: true }
);

    function handlePageChange(page) {

        router.post('/solicitudes', {
            fechaIngresoInicioQuery: fechaIngresoInicioQuery.value,
            fechaIngresoFinQuery: fechaIngresoFinQuery.value,
            fechaAceptacionInicioQuery: fechaAceptacionInicioQuery.value,
            fechaAceptacionFinQuery: fechaAceptacionFinQuery.value,
            areasQuery: areasQuery.value,
            estatusQuery: estatusQuery.value,
            rangoFechasIngresoManual: rangoFechasIngresoManual.value,
            rangoFechasAceptacionManual: rangoFechasAceptacionManual.value,
            sortCriteriaQuery: sortCriteriaQuery.value,
            page: page
        }, {
            preserveState: true,
            replace: true,
            onFinish: () => {
                isLoading.value = false;
            }
        });
    }

    const CustomCalendarUp = CalendarUp; 
    const CustomCalendarDown = CalendarDown; 

    const busquedaIndexada = ref(false); // Inicialmente en false

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
        const estatusTerminales = [4]; // Ajusta según los IDs reales de tu base de datos
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

    const currentSortIcon = computed(() => {
        switch (sortCriteriaQuery.value) {
            case 'fecha_desc':
            case 'fecha_asc':
                return `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>`; // o el icono de flechas/calendario que prefieras para fechas
            case 'solicitante_asc':
            case 'solicitante_desc':
                return `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>`;
            case 'area_asc':
            case 'area_desc':
                return `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>`;
            default:
                return `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" /></svg>`;
        }
    });
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
                                    <span v-if="soloLectura" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium ring-1 ring-inset bg-color1-50 text-color1-700 dark:bg-color1-950/50 dark:text-color1-400 ring-color1-600/25">
                                        <span class="inline-flex items-center shrink-0 text-color1-700 dark:text-gray-500" title="Solo lectura">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <rect x="3" y="11" width="18" height="11" rx="2"/>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                            </svg>
                                        </span>
                                        Solo lectura
                                    </span>
                                    <!-- Badge de Estado (Captura / Edición) -->
                                    <span v-else
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium ring-1 ring-inset"
                                        :class="!solicitudSeleccionada 
                                            ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400 ring-amber-600/25' 
                                            : 'bg-color1-50 text-color1-700 dark:bg-color1-950/50 dark:text-color1-400 ring-color1-600/25'">
                                        {{ !solicitudSeleccionada ? 'Modo Captura' : 'Modo Edición' }}
                                    </span>

                                    <!-- Bloque de Folio (Solo si existe ID) -->
                                    <div 
                                        v-if="solicitudSeleccionada?.id"
                                        class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
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
                        :sortCriteriaQuery = "sortCriteriaQuery"
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
                            :sortCriteriaQuery = "sortCriteriaQuery"
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
            <div class="flex flex-col xl:flex-row items-stretch xl:items-center gap-3 w-full">
                <!-- 1. Rango de Fechas (Tamaño normal/fijo en escritorio) -->
                <div class="space-y-1 w-full xl:w-[260px] flex-shrink-0 z-10">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 pl-1">Periodo de Solicitudes</span>
                    <el-date-picker
                        v-model="rangoFechasIngresoQuery"
                        type="daterange"
                        unlink-panels
                        range-separator="-"
                        start-placeholder="-"
                        end-placeholder="-"
                        :shortcuts="shortcuts"
                        class="custom-date-picker !w-full"
                        value-format="YYYY-MM-DD"
                        format="DD/MM/YYYY"
                        @change="onDateChange"
                        :prefix-icon="CustomCalendarUp"
                        :disabled="busquedaIndexada"
                        :clearable="false"
                        style="border-radius: 16px!important"
                        popper-class="custom-date-range-popper"
                    />
                </div>

                <!-- 2. Buscador de Texto Libre (Ocupa el doble de espacio: flex-[2]) -->
                <form @submit.prevent="estatusQuery = 'todas'; searchQueryConfirmado = searchQuery; realizarBusqueda();" class="space-y-1 w-full xl:flex-[2]">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 pl-1">Búsqueda rápida</span>
                    <div class="relative w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            type="search" 
                            v-model="searchQuery"
                            :disabled="solicitudes.length === 0 && (!searchQuery || searchQuery?.length === 0)"
                            @search="searchQueryConfirmado = searchQuery; estatusQuery = 'todas'; realizarBusqueda();"
                            placeholder="Buscar por folio, solicitante o petición..."
                            style="border-radius: 16px!important; min-height: 38px !important;"
                            class="block w-full pl-10 pr-3 py-2 text-xs sm:text-sm rounded-lg border border-gray-300 shadow-sm dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-color1 focus:border-transparent transition-all disabled:cursor-not-allowed disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:focus:ring-0 disabled:focus:border-gray-300 dark:disabled:focus:border-gray-700"
                            :class="{ 'sin-solicitudes': (solicitudes.length === 0 && (!searchQuery || searchQuery?.length === 0)) }"
                        />
                    </div>
                </form>

                <!-- 3. Selector de Áreas (Tamaño normal/fijo en escritorio) -->
                <div class="space-y-1 w-full xl:w-[360px] flex-shrink-0 z-10">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 pl-1">
                        Áreas
                    </span>
                    <el-select
                        v-model="areasQuery"
                        class="custom-select-gray !w-full"
                        :class="{ 'sin-solicitudes': (solicitudes.length === 0 && (!areasQuery || areasQuery?.length === 0)) }"
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

            <div class="pt-2 pb-4 sm:pb-0 pr-1 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sm:gap-6 overflow-x-visible sm:overflow-x-hidden">
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
                        ]">
                        TODAS
                        <span
                            :class="
                                estatusQuery === 'todas'
                                    ? 'bg-black/20 text-white dark:bg-black/30'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'
                            "
                            class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full font-semibold">
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
                        ]">
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
                        ]">
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
                        ]">
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
                        ]">
                        <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: estatusQuery === 'canceladas' ? '#ffffff' : getStatusColor('danger') }"></span>
                        CANCELADAS
                        <span :class="estatusQuery === 'canceladas' ? 'bg-black/20 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'" class="text-[10px] ml-1 px-1.5 py-0.5 rounded-full font-semibold">
                            {{ totalesEstatusQuery.canceladas }}
                        </span>
                    </button>
                
                </div>
                <div class="flex items-center pt-2 gap-2 px-0 sm:px-0 shrink-0">
                    <el-select 
                        v-model="sortCriteriaQuery" 
                        placeholder="Ordenar por"
                        class="custom-select-gray w-full sm:w-[280px]" style="width: 355px;"
                        @change="realizarBusqueda()"
                        :class="{ 'sin-solicitudes': solicitudes.length === 0 }">
                        <!-- Slot prefix dinámico -->
                        <template #prefix>
                            <div class="flex items-center ml-1 mr-2 text-gray-600 dark:text-gray-400" v-html="currentSortIcon"></div>
                        </template>

                        <!-- Tus el-option con sus respectivos svg (igual que antes) -->
                        <el-option value="fecha_desc" label="De las más recientes a las más antiguas">
                            <div class="flex items-center">
                                <span class="w-4 h-4 mr-2 text-gray-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                </span>
                                <span class="text-xs">De las más recientes a las más antiguas</span>
                            </div>
                        </el-option>

                        <el-option value="fecha_asc" label="De las más antiguas a las más recientes">
                            <div class="flex items-center">
                                <span class="w-4 h-4 mr-2 text-gray-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                    </svg>
                                </span>
                                <span class="text-xs">De las más antiguas a las más recientes</span>
                            </div>
                        </el-option>

                        <el-option value="solicitante_asc" label="Solicitante (A - Z)">
                            <div class="flex items-center">
                                <span class="w-4 h-4 mr-2 text-gray-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                                <span class="text-xs">Solicitante (A - Z)</span>
                            </div>
                        </el-option>

                        <el-option value="solicitante_desc" label="Solicitante (Z - A)">
                            <div class="flex items-center">
                                <span class="w-4 h-4 mr-2 text-gray-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                                <span class="text-xs">Solicitante (Z - A)</span>
                            </div>
                        </el-option>

                        <el-option value="area_asc" label="Área (A - Z)">
                            <div class="flex items-center">
                                <span class="w-4 h-4 mr-2 text-gray-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </span>
                                <span class="text-xs">Área (A - Z)</span>
                            </div>
                        </el-option>

                        <el-option value="area_desc" label="Área (Z - A)">
                            <div class="flex items-center">
                                <span class="w-4 h-4 mr-2 text-gray-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </span>
                                <span class="text-xs">Área (Z - A)</span>
                            </div>
                        </el-option>
                    </el-select>
                </div>             
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
                    }">
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
                                    <span v-if="solicitud.localidad_beneficiada?.id" class="truncate">
                                        {{ solicitud.localidad_beneficiada?.nombre }}
                                    </span>
                                    <span v-if="solicitud.localidad_beneficiada?.id && solicitud.solicitante.numero_telefonico" class="text-gray-300 dark:text-gray-700">
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
                                <span :class="{ 'opacity-50 grayscale-[30%] line-through': Number(solicitud.id_estatus) === 4 }" class="text-gray-500 dark:text-gray-400 font-medium">Estatus</span>
                                <span
                                    class="no-underline inline-flex items-center gap-1.5 rounded-md px-2.5 py-0.5 text-[11px] font-semibold tracking-wide"
                                    :style="{
                                        color: getStatusColor(solicitud.estatus.color),
                                        backgroundColor: `${getStatusColor(solicitud.estatus.color)}15`,
                                        borderColor: `${getStatusColor(solicitud.estatus.color)}30`,
                                        opacity: '1 !important'
                                    }"
                                    style="border-width: 1px;">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :style="{ backgroundColor: getStatusColor(solicitud.estatus.color) }"></span>
                                    {{ solicitud.estatus.nombre }}
                                </span>
                            </div>

                            <!-- Monto aprobado -->
                            <div v-if="solicitud.cantidad_aprobada" class="flex items-center justify-between gap-3 pt-1.5 border-t border-gray-200/60 dark:border-gray-800"
                            :class="{ 'opacity-50 grayscale-[30%] line-through': Number(solicitud.id_estatus) === 4 }">
                                <span class="text-gray-600 dark:text-gray-300 font-semibold">
                                    <template v-if="Number(solicitud.id_tipo_apoyo) === 2">
                                        {{ Number(solicitud.id_estatus) === 3 ? 'Inversión realizada' : 'Inversión estimada' }}
                                    </template>
                                    <template v-else>
                                        {{ Number(solicitud.id_estatus) === 3 ? 'Monto ejercido' : 'Monto aprobado' }}
                                    </template>
                                </span>
                                <span class="font-bold tracking-tight text-sm"
                                :class="Number(solicitud.id_tipo_apoyo) === 2 
                                    ? 'text-color4-600 dark:text-color4-300' 
                                    : 'text-color2-600 dark:text-color2-300'">
                                    $ {{ Number(solicitud.cantidad_aprobada).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} 
                                    <span class="text-[10px] font-normal opacity-75 relative inline-block">
                                        MXN
                                        <span v-if="Number(solicitud.id_estatus) === 4" class="absolute inset-x-0 top-1/2 h-[1px] bg-current -translate-y-1/2"></span>
                                    </span>
                                </span>
                            </div>

                            <!-- Tipo de apoyo (Misma estructura que Fecha y Área) -->
                            <div class="flex items-center justify-between gap-3"
                                :class="{ 'opacity-50 grayscale-[30%] line-through': Number(solicitud.id_estatus) === 4 }">
                                <span class="text-gray-500 dark:text-gray-400 font-medium">Tipo de apoyo</span>
                                <span class="inline-flex items-center gap-1.5 font-semibold text-gray-700 dark:text-gray-200 uppercase text-xs">
                                    <!-- Ícono Billete (Efectivo) -->
                                    <svg v-if="Number(solicitud.id_tipo_apoyo) === 1" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-color2-600 dark:text-emerald-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/>
                                    </svg>
                                    <!-- Ícono Caja (En especie) -->
                                    <svg v-else-if="Number(solicitud.id_tipo_apoyo) === 2" xmlns="http://www.w3.org/2000/svg" class="w-3 h-3.5 text-color4-600 dark:text-amber-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
                                    </svg>
                                    {{ Number(solicitud.id_tipo_apoyo) === 2
                                        ? 'En especie'
                                        : Number(solicitud.id_tipo_apoyo) === 1
                                            ? 'Efectivo'
                                            : 'Sin especificar'
                                    }}
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
                    {{ totalSolicitudes ? 
                      (!searchQuery && !areasQuery) ? 'Prueba con otras fechas' :
                    'Intenta con otros términos o limpia los filtros.' : 'Las solicitudes que vayas agregando aparecerán aquí.' }}
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

<style scoped>
:deep(.custom-select-gray .el-select__selected-item),
:deep(.custom-select-gray .el-select__selected-item span) {
    color: #33373d !important;
    font-weight: 100 !important;
}
</style>

<style>
/* ==========================================================================
   CONFIGURACIÓN DEFINITIVA PARA EL POPPER DEL RANGO DE FECHAS (SIDEBAR + CALENDARIO)
   ========================================================================== */

/* 1. Ancho total holgado del popper flotante para alojar sidebar y los dos meses */
.custom-date-range-popper {
  width: 720px !important;
  max-width: 100vw !important;
}

/* 1. Creamos y estilizamos el título/guía en la parte superior del sidebar */
.custom-date-range-popper .el-picker-panel__sidebar::before {
  content: "Selecciona un periodo:" !important; /* Puedes cambiar el texto aquí */
  display: block !important;
  font-size: 10.5px !important;
  font-weight: bold !important;
  color: #606266 !important;
  padding: 8px 10px 4px 10px !important;
  border-bottom: 1px solid var(--el-border-color-light, #e4e7ed) !important;
  margin-bottom: 4px !important;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* 2. Ajustamos ligeramente el padding superior del sidebar para que el título respire bien */
.custom-date-range-popper .el-picker-panel__sidebar {
  padding-top: 0 !important;
}

/* 2. Forzamos al contenedor interno de Element Plus a comportarse en fila horizontal */
.custom-date-range-popper .el-picker-panel__body-wrapper,
.custom-date-range-popper .el-picker-panel__body {
  display: flex !important;
  flex-direction: row !important;
  width: 100% !important;
  margin-left: 0 !important;
}

/* 3. Ancho fijo y controlado para el panel lateral de los shortcuts */
.custom-date-range-popper .el-picker-panel__sidebar {
  width: 185px !important;
  min-width: 185px !important;
  max-width: 185px !important;
  position: static !important;
  float: none !important;
  border-right: 1px solid var(--el-border-color-light, #e4e7ed);
  box-sizing: border-box;
  flex-shrink: 0 !important;
  padding: 4px 0 !important;
  background-color: white !important;
  z-index: 10;
  box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
}

/* 4. Estilo ultra compacto: interlineado menor y tipografía reducida para los atajos */
.custom-date-range-popper .el-picker-panel__sidebar .el-picker-panel__shortcut {
  white-space: normal !important;
  word-break: break-word !important;
  line-height: 1.1 !important;          /* Interlineado reducido */
  padding: 5px 8px !important;          /* Espaciado interno cómodo */
  margin-bottom: 2px !important;
  text-align: left !important;
  font-size: 12px !important;           /* Tamaño de texto compacto */
  width: 100% !important;
  background-color: transparent !important;
  color: #606266;
  border-radius: 4px;
}

/* 5. Hover limpio para los atajos con tus variables */
.custom-date-range-popper .el-picker-panel__sidebar .el-picker-panel__shortcut:hover {
  background-color: rgb(238, 239, 240) !important;
  color: var(--color-1-1, #409eff) !important;
  cursor: pointer;
}

.custom-date-range-popper .el-picker-panel {
  width: 100% !important;
  box-sizing: border-box !important;
}

/* 6. El cuerpo con los calendarios ocupa el espacio restante a la derecha de forma limpia */
.custom-date-range-popper .el-picker-panel__body {
  display: flex !important;
  flex-direction: row !important;
  flex-wrap: nowrap !important;
  width: 100% !important;
  overflow: hidden !important;
}

.custom-date-range-popper .el-date-range-picker__content {
  padding: 12px 16px !important;
  box-sizing: border-box !important;
}

/* 1. Reducir ligeramente el ancho de las celdas de la tabla para que los días quepan holgados */
.custom-date-range-popper .el-date-table th,
.custom-date-range-popper .el-date-table td {
  width: 28px !important;
  padding: 2px 0 !important;
}

/* 2. Asegurar que los textos de los días (lun, mar, mié, dom) no se partan jamás */
.custom-date-range-popper .el-date-table th {
  white-space: nowrap !important;
  font-size: 11px !important;
}

/* 3. Ajustar el espaciado interior de cada mes para ganar un par de pixeles libres */
.custom-date-range-popper .el-date-range-picker__content {
  padding: 10px 12px !important;
  box-sizing: border-box !important;
}
</style>


