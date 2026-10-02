<style>
    .solicitante-bloqueado {
        background-color: #f9fafc !important;
        color: rgba(17, 24, 39, 0.7) !important; /* Tu color deseado */
        cursor: not-allowed !important;
        /* Usamos position relative para poner una capa invisible que bloquee los clics pero conserve los estilos */
    }

    /* Truco definitivo: Ponemos un pseudo-elemento transparente encima que absorba los clics (bloquea el select) pero deje ver los estilos y el cursor */
    .solicitante-bloqueado {
        position: relative;
    }

    /* Creamos una capa invisible que bloquea el clic */
    .solicitante-bloqueado::after {
        content: '';
        position: absolute;
        inset: 0;
        cursor: not-allowed !important;
        z-index: 10;
    }

    /* Soporte para modo oscuro */
    .dark .solicitante-bloqueado {
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
import { useForm, router, usePage } from '@inertiajs/vue3';
import { computed, watch, ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2'; // Asegúrate de tenerlo importado si es necesario

    const emit = defineEmits(['update:solicitud', 'saved', 'close']);

    const ahora = new Date();
    const year = ahora.getFullYear();
    const month = String(ahora.getMonth() + 1).padStart(2, '0'); // Los meses van de 0 a 11
    const day = String(ahora.getDate()).padStart(2, '0');

    const abrirNuevaSolicitud = () => {
        Swal.fire({
            title: '¿Deseas capturar una nueva solicitud?',
            text: 'Se limpiará el formulario para una nueva captura.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // O un color acorde a tu app (ej. el primario)
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, quiero una nueva solicitud',
            cancelButtonText: 'No, permanecer en la solicitud actual',
            didOpen: (toast) => {
                toast.style.zIndex = '99999';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                router.visit(route('solicitudes.nueva'));
            }
        });
    };

      const confirmarLimpiarFormulario = () => {
        Swal.fire({
            title: '¿Limpiar formulario?',
            text: 'Se perderán todos los datos capturados y la búsqueda actual.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // O un color acorde a tu app (ej. el primario)
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'No, conservar datos',
            didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container')
                    if (swalContainer) {
                        swalContainer.style.setProperty('z-index', '99999', 'important')
                    }
                }
        }).then((result) => {
            if (result.isConfirmed) {
                // Ejecutamos la limpieza si el usuario confirma
                form.reset();
                form.fecha = `${year}-${month}-${day}`;
                form.id_area = ''
                form.peticion = ''
                form.cantidad_aprobada = ''
                formBusquedaSolicitante.value = { 
                    nombre: '', 
                    apellidos: '', 
                    curp: '', 
                    numero_telefonico: '', 
                    id_localidad: 1, 
                    localidad: 'CONCORDIA' 
                }; 
                mostrarObservaciones.value = false;
            }
        });
    };


    // 1. PRIMERO declaramos los props para que existan antes de ser usados
    const props = defineProps({
        isPage: {
            type: Boolean,
            default: true // Por defecto actúa como página normal
        },
        nuevaSolicitud: Boolean,
        solicitud: Object,
        localidadesList: Array,
        areasList: Array,
        estatusList: Array,
        fechaIngresoInicioQuery: String,
        fechaIngresoFinQuery: String,
        searchQuery: String,
        estatusQuery: String,
        areasQuery: Array,
        page: Number
    });

    // 2. Declaramos las variables reactivas locales del componente
    const nuevaSolicitud = ref(true);
    const solicitanteSeleccionado = ref(false);
    const solicitanteCargadoSolicitudes = ref(false);

    // Función para aplicar el formato de moneda/cantidad a un valor existente
   const aplicarFormatoInicial = (valor) => {
        if (valor === null || valor === undefined || valor === '') return '';
        
        let valorStr = String(valor).replace(/[^\d.]/g, '');
        let [entero, decimal] = valorStr.split('.');
        
        if (!entero) entero = '0';
        entero = entero.replace(/^0+(?=\d)/, '');
        entero = entero.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        if (decimal !== undefined) {
            return `${entero}.${decimal.slice(0, 2)}`;
        }
        
        return entero;
    };

    // 3. Inicializamos el formulario (aquí puedes aprovechar de una vez los props si quieres)
    const form = useForm({
        id_solicitud: props.solicitud?.id || '',
        fecha: props.solicitud?.fecha || `${year}-${month}-${day}`,
        solicitante_curp: props.solicitud?.solicitante?.curp || '', 
        solicitante_nombre: props.solicitud?.solicitante?.nombre || '',
        solicitante_apellidos: props.solicitud?.solicitante?.apellidos || '',
        solicitante_numero_telefonico: props.solicitud?.solicitante?.numero_telefonico || '',
        solicitante_localidad: props.solicitud?.solicitante?.localidad?.nombre || 'CONCORDIA',
        solicitante_id_localidad: props.solicitud?.solicitante?.id_localidad || 1,
        id_area: props.solicitud?.id_area || '',
        id_estatus: props.solicitud?.id_estatus || '1', 
        fecha_resolucion: null,
        peticion: props.solicitud?.peticion || '',
        cantidad_aprobada: props.solicitud?.cantidad_aprobada || '',
        observaciones: props.solicitud?.observaciones || '',
        searchQuery: new URLSearchParams(window.location.search).get('busqueda') || '',
        fechaIngresoInicioQuery: new URLSearchParams(window.location.search).get('fechaIngresoInicioQuery') || null,
        fechaIngresoFinQuery: new URLSearchParams(window.location.search).get('fechaIngresoFinQuery') || null,
    });

    // Listas de áreas (puedes usar props.areasList si te las manda Laravel, o dejar esta estática)

    const formBusquedaSolicitante = ref({
        nombre: '',
        apellidos: '',
        curp: '',
        numero_telefonico: '',
        id_localidad: 1,
        localidad: 'CONCORDIA'
    });

    watch(() => props.solicitud, (newVal) => {
    if (newVal) {
        // Rellenar formulario principal
        form.id_solicitud = newVal.id || '';
        form.fecha = newVal.fecha || `${year}-${month}-${day}`;
        form.id_area = newVal.id_area || '';
        form.id_estatus = newVal.id_estatus || '1';
        form.peticion = newVal.peticion || '';
        form.cantidad_aprobada = newVal.cantidad_aprobada || '';
        form.observaciones = newVal.observaciones || '';

        // Rellenar datos del solicitante en ambos lados
        form.solicitante_curp = newVal.solicitante?.curp || '';
        form.solicitante_nombre = newVal.solicitante?.nombre || '';
        form.solicitante_apellidos = newVal.solicitante?.apellidos || '';
        form.solicitante_numero_telefonico = newVal.solicitante?.numero_telefonico || '';
        form.solicitante_localidad = newVal.solicitante?.localidad?.nombre || 'CONCORDIA';
        form.solicitante_id_localidad = newVal.solicitante?.id_localidad || 1;

        solicitanteSeleccionado.value = true
        solicitanteCargadoSolicitudes.value = true

        // Rellenar también el objeto de búsqueda/visualización del solicitante
        formBusquedaSolicitante.value = {
            curp: newVal.solicitante?.curp || '',
            nombre: newVal.solicitante?.nombre || '',
            apellidos: newVal.solicitante?.apellidos || '',
            numero_telefonico: newVal.solicitante?.numero_telefonico || '',
            id_localidad: newVal.solicitante?.id_localidad || 1,
            localidad: newVal.solicitante?.localidad?.nombre || 'CONCORDIA'
        };
    } else {
        // Limpiar si es nueva solicitud
        form.reset();
        formBusquedaSolicitante.value = {
            nombre: '',
            apellidos: '',
            curp: '',
            numero_telefonico: '',
            id_localidad: 1,
            localidad: 'CONCORDIA'
        };
    }
}, { immediate: true, deep: true });

    // 4. AL FINAL colocamos el onMounted para procesar lógica complementaria al cargar
    onMounted(() => {
        nuevaSolicitud.value = props.nuevaSolicitud;
        
        let solicitud = props.solicitud;
        if (solicitud) {
            formBusquedaSolicitante.curp = solicitud.solicitante.curp;
            formBusquedaSolicitante.nombre = solicitud.solicitante.nombre;
            formBusquedaSolicitante.apellidos = solicitud.solicitante.apellidos;
            formBusquedaSolicitante.numero_telefonico = solicitud.solicitante.numero_telefonico;
            formBusquedaSolicitante.id_localidad = solicitud.solicitante.id_localidad;    
        }
        if (form.cantidad_aprobada) {
            form.cantidad_aprobada = aplicarFormatoInicial(form.cantidad_aprobada);
        }

    });

    const curpRegex = /^([A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d])(\d)$/;
    const curpValidada = ref(false)

    // Valida solo si ya escribió los 18 caracteres para no frustrarlo mientras escribe
    const esCurpInvalida = computed(() => {
        const curp = formBusquedaSolicitante.value.curp
            ? formBusquedaSolicitante.value.curp.trim().toUpperCase()
            : '';

        if (curp.length === 0) {
            return false;
        }

        // Mientras está escribiendo, no marcar error
        if (!curpValidada.value && curp.length < 18) {
            return false;
        }

        // Si salió del campo y tiene menos de 18 caracteres
        if (curp.length < 18) {
            return true;
        }

        // Si tiene 18, validar estructura de CURP
        return !curpRegex.test(curp);
    });

    const validarCurp = () => {
        curpValidada.value = true;
    };

    const mostrarObservaciones = ref(false);
    const resultadosBusquedaSolicitante = ref([]);
    const mostrarResultadosSolicitante = ref(false);
    const editandoSolicitante = ref(false);
    const solicitanteSeleccionadoId = ref(null);
    const nuevoSolicitanteId = ref(null);

    // Variable reactiva para el estado de carga
    const cargandoSolicitantes = ref(false);


    let ultimaBusquedaSinResultados = '';
    let ultimaLocalidadUsada = '';
    let debounceTimer = null;
    let cancelTokenSource = null;

    function buscarSolicitante() {
        if (editandoSolicitante.value) return;

        const { nombre, apellidos, curp, numero_telefonico, id_localidad, localidad } = formBusquedaSolicitante.value;

        // 1. Contamos cuántos de los 4 campos principales tienen valor
        const camposPrincipales = [nombre, apellidos, curp, numero_telefonico];
        const camposConValorCount = camposPrincipales.filter(campo => campo && campo.trim().length > 0).length;

        // 2. Evaluamos la regla: si 3 o más tienen valor, se incluye id_localidad en la búsqueda
        const usarLocalidad = camposConValorCount >= 3;
        const idLocalidadAEnviar = usarLocalidad ? (id_localidad || '') : '';

        const algunCampoLleno = camposConValorCount > 0 || (usarLocalidad && id_localidad);

        solicitanteSeleccionado.value = false;
        solicitanteCargadoSolicitudes.value = false

        if (debounceTimer) clearTimeout(debounceTimer);

        if (!algunCampoLleno) {
            if (cancelTokenSource) cancelTokenSource.cancel();
            resultadosBusquedaSolicitante.value = [];
            mostrarResultadosSolicitante.value = false;
            cargandoSolicitantes.value = false;
            ultimaBusquedaSinResultados = '';
            ultimaLocalidadUsada = '';
            return;
        }

        // Si cambia el estado de la localidad (si se activó porque ya son 3 campos o cambió de ID), limpiamos la memoria de error
        if (ultimaLocalidadUsada !== idLocalidadAEnviar) {
            ultimaBusquedaSinResultados = '';
            ultimaLocalidadUsada = idLocalidadAEnviar;
        }

        // Texto actual formado EXCLUSIVAMENTE con los campos principales de la persona
        const textoActual = `${nombre || ''} ${apellidos || ''} ${curp || ''} ${numero_telefonico || ''}`
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();

        // Si el usuario borró texto principal
        if (ultimaBusquedaSinResultados && !textoActual.startsWith(ultimaBusquedaSinResultados)) {
            ultimaBusquedaSinResultados = '';
        }

        // OPTIMIZACIÓN: Bloquear si ya sabíamos que no hay resultados para esta combinación
        if (ultimaBusquedaSinResultados && textoActual.startsWith(ultimaBusquedaSinResultados)) {
            if (cancelTokenSource) cancelTokenSource.cancel();
            resultadosBusquedaSolicitante.value = [];
            mostrarResultadosSolicitante.value = false;
            cargandoSolicitantes.value = false;
            return; 
        }

        cargandoSolicitantes.value = true;
        mostrarResultadosSolicitante.value = true; 

        const textoBusquedaEnEsteMomento = textoActual;

        debounceTimer = setTimeout(() => {
            if (cancelTokenSource) {
                cancelTokenSource.cancel('Operación cancelada por nueva búsqueda.');
            }
            cancelTokenSource = axios.CancelToken.source();

            axios.get(route('solicitudes.buscar.solicitante'), { 
                params: { 
                    nombre: nombre || '', 
                    apellidos: apellidos || '', 
                    curp: curp || '', 
                    numero_telefonico: numero_telefonico || '', 
                    // Aquí se envía id_localidad únicamente si se cumple la regla de los 3 campos
                    id_localidad: idLocalidadAEnviar, 
                    localidad: localidad || '' 
                },
                cancelToken: cancelTokenSource.token
            })
            .then(response => {
                resultadosBusquedaSolicitante.value = response.data;
                mostrarResultadosSolicitante.value = response.data.length > 0;

                if (response.data.length === 0) {
                    ultimaBusquedaSinResultados = textoBusquedaEnEsteMomento;
                } else {
                    ultimaBusquedaSinResultados = '';
                }
            })
            .catch(error => {
                if (!axios.isCancel(error)) {
                    console.error("Error al buscar solicitante:", error);
                    resultadosBusquedaSolicitante.value = [];
                    mostrarResultadosSolicitante.value = false;
                }
            })
            .finally(() => {
                cargandoSolicitantes.value = false;
            });
        }, 300);
    }


    function cerrarResultadosSolicitante() {
        if (cancelTokenSource) cancelTokenSource.cancel();
        mostrarResultadosSolicitante.value = false;
        resultadosBusquedaSolicitante.value = [];
        solicitanteSeleccionado.value = false; // Permite que vuelva a evaluar "Solicitante Nuevo" si se escribe después
    }

    const solicitanteOriginal = ref(null);

    function seleccionarSolicitante(solicitante) {
        formBusquedaSolicitante.value.curp = solicitante.curp || '';
        formBusquedaSolicitante.value.nombre = solicitante.nombre || '';
        formBusquedaSolicitante.value.apellidos = solicitante.apellidos || '';
        formBusquedaSolicitante.value.numero_telefonico = solicitante.numero_telefonico || '';

        form.solicitante_id = solicitante.id;
        nuevoSolicitanteId.value = null;

        if (solicitante.id_localidad) {
            formBusquedaSolicitante.value.id_localidad = solicitante.id_localidad;
            formBusquedaSolicitante.value.localidad =
                solicitante.localidad?.nombre || '';
        }

        mostrarResultadosSolicitante.value = false;
        resultadosBusquedaSolicitante.value = [];

        solicitanteSeleccionado.value = true;
        solicitanteSeleccionadoId.value = solicitante.id;
        solicitanteCargadoSolicitudes.value = false

        // IMPORTANTE:
        // Al seleccionar un existente, comienza bloqueado.
        editandoSolicitante.value = false;

        // Guardamos una copia del estado original
        solicitanteOriginal.value = {
            ...solicitante,
            id_localidad: solicitante.id_localidad,
            localidad: solicitante.localidad?.nombre || ''
        };
    }

    function iniciarEdicionSolicitante() {
        editandoSolicitante.value = true;
    }

    function cancelarEdicionSolicitante() {
        if (solicitanteOriginal.value) {
            formBusquedaSolicitante.value.curp =
                solicitanteOriginal.value.curp || '';

            formBusquedaSolicitante.value.nombre =
                solicitanteOriginal.value.nombre || '';

            formBusquedaSolicitante.value.apellidos =
                solicitanteOriginal.value.apellidos || '';

            formBusquedaSolicitante.value.numero_telefonico =
                solicitanteOriginal.value.numero_telefonico || '';

            formBusquedaSolicitante.value.id_localidad =
                solicitanteOriginal.value.id_localidad || 1;

            formBusquedaSolicitante.value.localidad =
                solicitanteOriginal.value.localidad || 'CONCORDIA';
        }

        editandoSolicitante.value = false;
    }

    const solicitanteBloqueado = computed(() => {
        return solicitanteSeleccionado.value && !editandoSolicitante.value;
    });

    const esSolicitanteNuevo = computed(() => {
        const cargando = cargandoSolicitantes.value;
        const resultados = resultadosBusquedaSolicitante.value || [];
        const sinResultados = resultados.length === 0;

        // REGLA CLAVE: Si hay resultados de búsqueda, por definición NO es un solicitante nuevo
        if (!cargando && !sinResultados) {
            return false;
        }
        
        const curpTexto = formBusquedaSolicitante.value?.curp || form?.solicitante_curp || '';
        const nombreTexto = formBusquedaSolicitante.value?.nombre || form?.solicitante_nombre || '';
        
        const cumpleCurp = curpTexto.length > 4;
        const cumpleNombre = nombreTexto.length > 2;

        const esNuevoPorBusqueda = !cargando && sinResultados && (cumpleCurp || cumpleNombre) && !solicitanteSeleccionado.value;

        let fueModificado = false;

        // console.log('solicitanteSeleccionado', solicitanteSeleccionado.value)
        // console.log('solicitanteOriginal', solicitanteOriginal.value)

        // Solo evaluamos si fue modificado si previamente se seleccionó un registro y existe un original
        if (solicitanteSeleccionado.value && solicitanteOriginal.value) {
            const nombreActual = formBusquedaSolicitante.value?.nombre || '';
            const apellidosActual = formBusquedaSolicitante.value?.apellidos || '';
            const numeroTelefonicoActual = formBusquedaSolicitante.value?.numero_telefonico || '';
            // const idLocalidadActual = formBusquedaSolicitante.value?.id_localidad || '';

            const originalNombre = solicitanteOriginal.value?.nombre || '';
            const originalApellidos = solicitanteOriginal.value?.apellidos || '';
            const originalNumeroTelefonico = solicitanteOriginal.value?.numero_telefonico || '';
            // const originalIdLocalidad = solicitanteOriginal.value?.id_localidad || '';

            const normNombreActual = nombreActual.trim().toUpperCase();
            const normOriginalNombre = originalNombre.trim().toUpperCase();
            
            const normApellidosActual = apellidosActual.trim().toUpperCase();
            const normOriginalApellidos = originalApellidos.trim().toUpperCase();

            const normNumeroTelefonicoActual = numeroTelefonicoActual.replace(/\D/g, '');
            const normOriginalNumeroTelefonico = originalNumeroTelefonico.replace(/\D/g, '');

            fueModificado = (normNombreActual !== normOriginalNombre) 
                || (normApellidosActual !== normOriginalApellidos)
                || (normNumeroTelefonicoActual !== normOriginalNumeroTelefonico);
        }


        return esNuevoPorBusqueda || fueModificado;
    });

    function limpiarSolicitante() {
        curpValidada.value = false;
        telefonoValidado.value = false;

        // 1. Limpiar el objeto reactivo de búsqueda
        formBusquedaSolicitante.value = {
            curp: '',
            nombre: '',
            apellidos: '',
            numero_telefonico: '',
            id_localidad: '1',
            localidad: 'CONCORDIA'
        };

        // 2. Limpiar las propiedades del formulario principal relacionadas al solicitante
        if (typeof form !== 'undefined') {
            form.solicitante_id = null;
            form.solicitante_curp = '';
            form.solicitante_nombre = '';
            form.solicitante_apellidos = '';
            form.solicitante_numero_telefonico = '';
            form.solicitante_id_localidad = 1; 
            form.peticion = ''          
        }

        // 3. Resetear resultados, vistas y la bandera de selección
        resultadosBusquedaSolicitante.value = [];
        mostrarResultadosSolicitante.value = false;
        solicitanteSeleccionado.value = false; // Permite que vuelva a evaluar "Solicitante Nuevo" si se escribe después
        solicitanteOriginal.value = null;
    }

    
    function actualizarLocalidad() {
        const idLocalidad = formBusquedaSolicitante.value.id_localidad;

        form.solicitante_id_localidad = idLocalidad;

        const encontrada = props.localidadesList?.find(
            loc => loc.id == idLocalidad
        );

        if (encontrada) {
            formBusquedaSolicitante.value.localidad = encontrada.nombre;
        }

        buscarSolicitante();
    }

    // Forzar mayúsculas automáticamente en los campos de texto
    watch(() => form.solicitante_curp, (val) => {
        if (val) form.solicitante_curp = val.toUpperCase();
    });
    watch(() => form.solicitante_nombre, (val) => {
        if (val) form.solicitante_nombre = val.toUpperCase();
    });
    watch(() => form.solicitante_apellidos, (val) => {
        if (val) form.solicitante_apellidos = val.toUpperCase();
    });
    watch(() => form.solicitante_localidad, (val) => {
        if (val) form.solicitante_localidad = val.toUpperCase();
    });
    watch(() => form.peticion, (val) => {
        if (val) form.peticion = val.toUpperCase();
    });

    const estatusRequiereFechaResolucion = computed(() => {
        const estatus = props.estatusList?.find(
            item => Number(item.id) === Number(form.id_estatus)
        );

        if (!estatus) {
            return false;
        }

        return ['APOYO ENTREGADO', 'CANCELADA'].includes(
                estatus.nombre?.trim().toUpperCase()
            );
        });

        const solicitarFechaResolucion = async () => {
            const hoy = `${year}-${month}-${day}`;
            const estatusSeleccionado = props.estatusList?.find(
        item => String(item.id) === String(form.id_estatus)
    );

    const nombreEstatus = estatusSeleccionado?.nombre?.trim().toUpperCase() ?? '';

    const esApoyoEntregado = nombreEstatus === 'APOYO ENTREGADO';

    const config = esApoyoEntregado
        ? {
            titulo: 'Confirmar entrega del apoyo',
            icono: '✓',
            encabezadoClase: 'bg-emerald-50 border-emerald-200',
            iconoClase: 'bg-emerald-100 text-emerald-700',
            tituloClase: 'text-emerald-800',
            descripcionClase: 'text-emerald-700',
            estado: 'APOYO ENTREGADO',
            descripcion: 'La solicitud será marcada como concluida.',
            textoFecha: 'Indica la fecha en que el apoyo fue entregado.',
            botonConfirmar: 'Confirmar y cerrar solicitud'
        }
        : {
            titulo: 'Confirmar cancelación',
            icono: '✕',
            encabezadoClase: 'bg-red-50 border-red-200',
            iconoClase: 'bg-red-100 text-red-700',
            tituloClase: 'text-red-800',
            descripcionClase: 'text-red-700',
            estado: 'CANCELADA',
            descripcion: 'La solicitud será marcada como cancelada.',
            textoFecha: 'Indica la fecha en que se determinó la cancelación.',
            botonConfirmar: 'Confirmar y cancelar solicitud'
        };


    const resultado = await Swal.fire({

        title: config.titulo,

        html: `
            <div class="text-left">

                <!-- Estado seleccionado -->
                <div class="flex items-start gap-3 mb-4 p-3 rounded-xl ${config.encabezadoClase}">

                    <div class="flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-full ${config.iconoClase}">
                        ${config.icono}
                    </div>

                    <div>
                        <p class="text-sm font-bold ${config.tituloClase}">
                            ${config.estado}
                        </p>

                        <p class="mt-0.5 text-xs leading-relaxed ${config.descripcionClase}">
                            ${config.descripcion}
                        </p>
                    </div>

                </div>


                <!-- Advertencia -->
                <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-3">

                    <div class="flex items-start gap-2.5">

                        <span class="flex-shrink-0 mt-0.5 text-amber-600 text-sm">
                            ⚠
                        </span>

                        <p class="text-xs leading-relaxed text-amber-800">
                            <strong>Esta acción concluirá la solicitud.</strong>
                            Una vez guardada, no podrás modificar sus datos.
                        </p>

                    </div>

                </div>


                <!-- Fecha de resolución -->
                <div>

                    <label
                        for="swal-fecha-resolucion"
                        class="block mb-1.5 text-sm font-semibold text-gray-700"
                    >
                        Fecha de resolución
                    </label>

                    <p class="mb-2 text-xs text-gray-500">
                        ${config.textoFecha}
                    </p>

                    <input
                        id="swal-fecha-resolucion"
                        type="date"
                        value="${hoy}"
                        max="${hoy}"
                        class="swal2-input !m-0 !w-full !h-10 !text-sm !rounded-2xl !border-gray-300 !shadow-sm"
                    >
                </div>

            </div>
        `,

        showCancelButton: true,

        confirmButtonText: config.botonConfirmar,
        cancelButtonText: 'Regresar',

        reverseButtons: true,
        focusConfirm: false,

        didOpen: () => {
            const container = Swal.getContainer();

            if (container) {
                container.style.zIndex = '99999';
            }

            const input = document.getElementById('swal-fecha-resolucion');

            if (input) {

                const aplicarFocusColor1 = () => {

                    input.style.setProperty(
                        'box-shadow',
                        'inset 0 0 0 1px rgb(123 0 58)',
                        'important'
                    );

                    input.style.setProperty(
                        'border-color',
                        'rgb(123 0 58)',
                        'important'
                    );

                    input.style.setProperty(
                        'outline',
                        'none',
                        'important'
                    );
                };

                const quitarFocusColor1 = () => {

                    input.style.removeProperty('box-shadow');
                    input.style.removeProperty('border-color');
                    input.style.removeProperty('outline');

                };

                input.addEventListener('focus', aplicarFocusColor1);
                input.addEventListener('blur', quitarFocusColor1);

                // Si SweetAlert2 abre el input ya enfocado,
                // aplicar inmediatamente nuestro estilo.
                if (document.activeElement === input) {
                    aplicarFocusColor1();
                }
            }
        },
        preConfirm: () => {

            const input = document.getElementById(
                'swal-fecha-resolucion'
            );

            const fecha = input?.value;

            if (!fecha) {

                Swal.showValidationMessage(
                    'Debe indicar la fecha de resolución.'
                );

                return false;
            }

            return fecha;
        }
    });
       

        return resultado.isConfirmed
            ? resultado.value
            : null;
    };

    const enviando = ref(false);

    const handleSubmit = (esGuardarContinuar, esActualizar) => {
        if (enviando.value) return; // Si ya se está enviando, no hace nada
        
        enviando.value = true;

        // Llamas a tu función submit original
        submit(esGuardarContinuar, esActualizar);

        // Opcional: si la petición falla o quieres resetearlo tras un tiempo de seguridad
        // (Inertia suele resetear el form, pero por seguridad puedes manejar un finally)
    };

    const submit = async(continuar = false, actualizar = false) => {
        form.solicitante_curp = formBusquedaSolicitante.value.curp
        form.solicitante_nombre = formBusquedaSolicitante.value.nombre
        form.solicitante_apellidos = formBusquedaSolicitante.value.apellidos
        form.solicitante_numero_telefonico = formBusquedaSolicitante.value.numero_telefonico
        form.solicitante_id_localidad = formBusquedaSolicitante.value.id_localidad

        const page = usePage();

        // 2. Leemos la página actual (asegúrate de que el backend la esté enviando en el render del index)
        const paginaActual = page.props.page || 1;

        if (actualizar && estatusRequiereFechaResolucion.value && !form.fecha_resolucion) 
        {
            const fechaResolucion = await solicitarFechaResolucion();

            // El usuario canceló el SweetAlert
            if (!fechaResolucion) {
                return;
            }

            form.fecha_resolucion = fechaResolucion;
        }

        if (actualizar) {
            form.transform((data) => ({
                ...data,
                cantidad_aprobada: data.cantidad_aprobada
                ? String(data.cantidad_aprobada).replace(/,/g, '')
                : data.cantidad_aprobada,
                searchQuery: props.searchQuery || null,
                fechaIngresoInicioQuery: props.fechaIngresoInicioQuery || null,
                fechaIngresoFinQuery: props.fechaIngresoFinQuery || null,
                paginaActual: paginaActual,
                estatusQuery: props.estatusQuery || null,
                areasQuery: props.areasQuery || null 
            })).post(route('solicitudes.update', form.id_solicitud), {
                preserveScroll: true,
                onSuccess: (page) => {
                    const nuevaSoli = page.props.flash.solicitud;
                    form.id_solicitud = nuevaSoli.id;

                    // 1. Actualizamos los datos en el componente padre de inmediato
                    emit('update:solicitud', nuevaSoli);
                    emit('close');

                    // 2. Lanzamos primero la notificación visual (Swal toast) para dar feedback inmediato
                    Swal.fire({
                        toast: true,
                        position: 'bottom-end',
                        icon: 'success',
                        title: '¡Solicitud actualizada!',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);
                        }
                    });
                }
            });
        }
        else
        {
            form.transform((data) => ({
                ...data,
                cantidad_aprobada: data.cantidad_aprobada
                ? String(data.cantidad_aprobada).replace(/,/g, '')
                : data.cantidad_aprobada,
                searchQuery: props.searchQuery || null,
                fechaIngresoInicioQuery: props.fechaIngresoInicioQuery || null,
                fechaIngresoFinQuery: props.fechaIngresoFinQuery || null,
                paginaActual: paginaActual,
                estatusQuery: props.estatusQuery || null,
                areasQuery: props.areasQuery || null 
            })).post(route('solicitudes.store'), {
                preserveScroll: true,
                onSuccess: (page) => {
                    form.id_solicitud = page.props.flash.solicitud.id;
                    const nuevaSoli = page.props.flash.solicitud; // o como te devuelva el backend la solicitud

                    emit('update:solicitud', nuevaSoli);
                    Swal.fire({
                    toast: true,
                    position: 'bottom-end',
                    icon: 'success',
                    title: '¡Solicitud guardada!',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
                    if (continuar)
                    {
                        nuevaSolicitud.value = false
                        if (props.isPage)
                        {
                            form.put(route('solicitudes.editar'), {
                                preserveScroll: true,
                                onSuccess: () => {
                                    // Opcional: acciones tras completarse el put exitosamente
                                },
                                onError: (errors) => {
                                    // Si detecta un error de sesión expirada o token CSRF desincronizado
                                    if (errors.session || errors.message?.includes('CSRF') || !page.props.auth?.user) {
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Sesión expirada',
                                            text: 'Tu sesión ha caducado. La página se recargará para que vuelvas a ingresar.',
                                            confirmButtonText: 'Aceptar'
                                        }).then(() => {
                                            window.location.reload(); // Recarga limpia para pedir nueva sesión
                                        });
                                    }
                                }
                            });
                        }
                        else
                        {
                            nuevaSolicitud.value = false
                        }
                    }
                    else
                    {
                        if (props.isPage)
                        {
                            router.get('solicitudes')
                        }
                        else
                        {
                            setTimeout(() => {
                            emit('close');
                            }, 100);
                        }
                      
                    }
                }
            });
        }
       
    };

    // Limpia lo que se escribe en tiempo real, eliminando letras/símbolos y limitando a 10 dígitos
    function formatearTelefono(event) {
        let valorLimpio = event.target.value.replace(/\D/g, ''); // Solo números
        if (valorLimpio.length > 10) {
            valorLimpio = valorLimpio.slice(0, 10); // Corta a los primeros 10 si excede
        }
        formBusquedaSolicitante.value.numero_telefonico = valorLimpio;
    }

    // Intercepta cuando el usuario copia y pega para limpiar y recortar de inmediato a 10 dígitos
    function handlePasteTelefono(event) {
        event.preventDefault(); // Evita el pegado sucio por defecto
        const textoPegado = (event.clipboardData || window.clipboardData).getData('text');
        let valorLimpio = textoPegado.replace(/\D/g, '').slice(0, 10); // Solo números y máximo 10
        
        formBusquedaSolicitante.value.numero_telefonico = valorLimpio;
    }

    // Validación centralizada que revisa ambos objetos
    const camposFaltantes = computed(() => {
        let faltantes = [];
        
        // Validaciones del solicitante (viven en formBusquedaSolicitante)
        if (!formBusquedaSolicitante.value.nombre || formBusquedaSolicitante.value.nombre.trim() === '') faltantes.push('Nombre (Solicitante)');
        if (!formBusquedaSolicitante.value.apellidos || formBusquedaSolicitante.value.apellidos.trim() === '') faltantes.push('Apellidos (Solicitante)');
        if (!formBusquedaSolicitante.value.id_localidad) faltantes.push('Localidad (Solicitante)');
        
        // Validación de la petición (vive en tu form principal - cambia 'peticion' por el nombre real de tu campo)
        if (!form.peticion || form.peticion.trim() === '') faltantes.push('Petición');
        
        return faltantes;
    });

    const estaIncompleto = computed(() => camposFaltantes.value.length > 0);

    const fechaValida = computed(() => {
        const fecha = form.fecha;

        if (!fecha) {
            return false;
        }

        // Verificar que tenga formato YYYY-MM-DD
        if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
            return false;
        }

        const [anio, mes, dia] = fecha.split('-').map(Number);

        const fechaObj = new Date(anio, mes - 1, dia);

        // Validar que la fecha realmente exista
        return (
            fechaObj.getFullYear() === anio &&
            fechaObj.getMonth() === mes - 1 &&
            fechaObj.getDate() === dia
        );
    });

    const cantidadErrores = computed(() => {
        return (
            Object.keys(form.errors).length +
            (esCurpInvalida.value ? 1 : 0) +
            (esTelefonoInvalido.value ? 1 : 0) +
            (esCantidadAprobadaInvalida.value ? 1 : 0) +
            (form.fecha && !fechaValida.value ? 1 : 0)
        );
    });

    const telefonoValidado = ref(false);

    const esTelefonoInvalido = computed(() => {
    const valorOriginal = formBusquedaSolicitante.value.numero_telefonico 
        ? formBusquedaSolicitante.value.numero_telefonico.trim() 
        : '';

    if (valorOriginal.length === 0) {
        return false;
    }

    // Si el usuario metió letras o símbolos, desde ahí ya es inválido
    // (Verificamos si el texto original tiene algo que no sea un número)
    const contieneLetrasOsimbolos = /\D/.test(valorOriginal);
    if (contieneLetrasOsimbolos) {
        // Si ya perdió el foco o tiene contenido no numérico, lo marcamos como error
        if (telefonoValidado.value) return true;
    }

    // Limpiamos los dígitos para medir la longitud numérica
    const telefono = valorOriginal.replace(/\D/g, '');

    // Mientras está escribiendo y tiene menos de 10 dígitos (números limpios)
    if (!telefonoValidado.value && telefono.length < 10) {
        return false;
    }

    // Si ya salió del campo y tiene menos de 10 dígitos o contiene letras
    if (telefono.length < 10 || contieneLetrasOsimbolos) {
        return true;
    }

    // Validación final de exactamente 10 dígitos numéricos
    return !/^\d{10}$/.test(telefono);
});

    const validarTelefono = () => {
        telefonoValidado.value = true;
    };

    const alEscribirCurp = () => {
        curpValidada.value = false;
        buscarSolicitante();
    };

    const cantidadAprobadaValidada = ref(false);
    const MAX_CANTIDAD_APROBADA = 99999.99;

    const formatearCantidadAprobada = (event) => {
        const input = event.target;

        const valorOriginal = input.value;
        const cursorOriginal = input.selectionStart;

        // Contar cuántos caracteres válidos había antes del cursor
        // ignorando las comas
        const caracteresAntesDelCursor = valorOriginal
            .slice(0, cursorOriginal)
            .replace(/,/g, '')
            .length;

        // Quitar todo excepto números y punto
        let valor = valorOriginal.replace(/[^\d.]/g, '');

        // Permitir solamente un punto decimal
        const partes = valor.split('.');

        if (partes.length > 2) {
            valor = partes[0] + '.' + partes.slice(1).join('');
        }

        // Máximo 2 decimales
        const partesFinales = valor.split('.');

        if (partesFinales.length === 2) {
            valor =
                partesFinales[0] +
                '.' +
                partesFinales[1].slice(0, 2);
        }

        // Actualizar el formulario
        form.cantidad_aprobada = valor;

        input.value = valor;

        cantidadAprobadaValidada.value = false;

        // ---------------------------------------------------------
        // Restaurar el cursor tomando como referencia los
        // caracteres reales, no las comas que fueron eliminadas.
        // ---------------------------------------------------------

        let nuevaPosicion = 0;
        let caracteresContados = 0;

        while (
            nuevaPosicion < valor.length &&
            caracteresContados < caracteresAntesDelCursor
        ) {
            nuevaPosicion++;
            caracteresContados++;
        }

        try {
            input.setSelectionRange(nuevaPosicion, nuevaPosicion);
        } catch (e) {}
    };

    const validarCantidadAprobada = () => {
        let valor = form.cantidad_aprobada;

        if (valor === null || valor === undefined || valor === '') {
            cantidadAprobadaValidada.value = true;
            return;
        }

        // Quitar punto final si lo dejó colgado (ej: 125.)
        if (String(valor).endsWith('.')) {
            valor = String(valor).slice(0, -1);
        }

        // AQUÍ ES DONDE APLICAMOS LAS COMAS DE MILES (al salir del input / blur)
        if (valor !== '') {
            let [entero, decimal] = String(valor).replace(/,/g, '').split('.');
            entero = entero.replace(/^0+(?=\d)/, ''); // Quitar ceros a la izquierda innecesarios
            entero = entero.replace(/\B(?=(\d{3})+(?!\d))/g, ','); // Agregar comas de miles
            
            form.cantidad_aprobada = decimal !== undefined ? `${entero}.${decimal}` : entero;
        }

        cantidadAprobadaValidada.value = true;
    };

    const esCantidadAprobadaInvalida = computed(() => {
        const valor = form.cantidad_aprobada;

        if (valor === null || valor === undefined || valor === '') {
            return false;
        }

        if (!cantidadAprobadaValidada.value) {
            return false;
        }

        // Quitar separadores de miles para validar
        const texto = String(valor).replace(/,/g, '');

        if (!/^\d+(\.\d{1,2})?$/.test(texto)) {
            return true;
        }

        const numero = Number(texto);

        if (numero <= 0) {
            return true;
        }

        if (numero > MAX_CANTIDAD_APROBADA) {
            return true;
        }

        return false;
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
</script>

<template>
        <div v-if="form.processing" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-600/60 backdrop-blur-xs transition-all duration-300">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-2xl max-w-sm w-full mx-4 text-center space-y-4 border border-gray-100 dark:border-gray-700 animate-in fade-in zoom-in duration-200">
                <!-- Estado 1: Procesando -->
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-full bg-color1-50 dark:bg-color1-950/50 text-color1 flex items-center justify-center mx-auto">
                        <svg class="animate-spin h-6 w-6 text-color1-600 dark:text-color1-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ nuevaSolicitud ? 'Guardando solicitud...': 'Actualizando solicitud...' }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Por favor espera un momento mientras registramos la información.</p>
                </div>
            </div>
        </div>

        <div :class="isPage ? 'max-w-8xl mx-auto py-0 px-2 sm:px-4 lg:px-6' : 'w-full px-0 pt-1'">
            <!-- SaaS Header / Breadcrumbs Style -->
            <div v-if="isPage" class="mb-4 sm:items-center sm:justify-between gap-4">
                <div class="pl-2 pt-3 pr-2">
                    <!-- Breadcrumb -->
                    <div class="flex items-center flex-wrap gap-1.5 text-[12px] font-semibold uppercase tracking-wider text-color1-700 dark:text-color1-400 mb-0.5">
                            <span>Módulo de Gestión</span>
                            <span class="text-gray-400">/</span>
                            <span>Solicitudes</span>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5">
                        <!-- Lado Izquierdo: Título Limpio -->
                        <div>
                            <h2 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white leading-tight">
                                {{ !nuevaSolicitud ? 'Editar Solicitud' : 'Nueva Solicitud' }}
                            </h2>
                        </div>

                        <!-- Lado Derecho: Badge de Estado + Folio Juntos -->
                        <div class="flex items-center gap-3 flex-wrap self-start sm:self-center">
                            
                            <!-- Badge de Estado (Captura / Edición) -->
                            <span 
                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium ring-1 ring-inset"
                                :class="nuevaSolicitud 
                                    ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400 ring-amber-600/25' 
                                    : 'bg-color1-50 text-color1-700 dark:bg-color1-950/50 dark:text-color1-400 ring-color1-600/25'"
                            >
                                {{ nuevaSolicitud ? 'Modo Captura' : 'Modo Edición' }}
                            </span>

                            <!-- Bloque de Folio (Solo si existe ID) -->
                            <div 
                                v-if="!nuevaSolicitud"
                                class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400"
                            >
                                <span class="text-gray-400 dark:text-gray-500 uppercase tracking-wider font-medium">
                                    Folio:
                                </span>
                                <span class="font-medium text-color1-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-md px-2.5 py-1 ring-1 ring-inset ring-gray-200 dark:ring-gray-700">
                                    #{{ String(form?.id_solicitud).slice(-5).padStart(6, '0') }}
                                </span>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
                

            <!-- SaaS High-Density Card Form -->
          <form @submit.prevent="submit" class="space-y-6">
                <div class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl overflow-hidden">
                    <div class="p-6 md:px-8 md:py-6 space-y-6">              
                        <!-- Sección 1: Datos Generales / Metadatos de la Solicitud -->
                        <div class="mb-6">
                            <!-- Encabezado de la sección -->
                            <div class="mb-4">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-4 h-4 text-color1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2m-6 9l2 2 4-4" />
                                    </svg>
                                    Datos Generales de la Solicitud
                                </h3>
                                <p v-if="nuevaSolicitud" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Define la fecha de registro y el área responsable.
                                </p>
                                 <p v-else class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Define la fecha de registro, el área responsable y el estatus operativo.
                                </p>
                            </div>

                            <!-- Fila de Campos (Fecha, Área y Estatus) -->
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-6">
                                
                                <!-- Fecha -->
                               <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">
                                        Fecha *
                                    </label>

                                   <input
                                        type="date"
                                        v-model="form.fecha"
                                        class="block w-full py-2 px-3 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 shadow-sm transition-all duration-200 sm:text-sm custom-native"
                                        :class="{
                                            'custom-date-error': form.fecha && !fechaValida,
                                        }"
                                        style="border-radius: 9999px !important;"
                                    />

                                    <div
                                        v-if="form.fecha && !fechaValida"
                                        class="text-red-500 text-xs mt-1">
                                        La fecha no es válida.
                                    </div>

                                    <div
                                        v-if="form.errors.fecha"
                                        class="text-red-500 text-xs mt-1">
                                        {{ form.errors.fecha }}
                                    </div>
                                </div>

                                <!-- Área ID (Combo) -->
                                <div class="sm:col-span-4">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">Área Destino</label>
                                       <el-select
                                        v-model="form.id_area"
                                        class="custom-select-gray w-full"
                                        collapse-tags
                                        collapse-tags-tooltip
                                        :max-collapse-tags="1"
                                        placeholder="Selecciona un área"
                                        value-key="value"
                                        >                        
                                        <el-option 
                                            v-for="area in areasList" 
                                            :key="area.id" 
                                            :label="area.nombre"
                                            :value="area.id">                            
                                            <div class="flex items-center">
                                                <span class="w-4 h-5 mr-2">
                                                </span>
                                                <span class="text-xs">{{ area.nombre }}</span>
                                            </div>                            
                                        </el-option>
                                    </el-select>
                                    <div v-if="form.errors.id_area" class="text-red-500 text-xs mt-1">{{ form.errors.id_area }}</div>
                                </div>

                                <!-- Estatus Inicial -->
                                <div v-if="!nuevaSolicitud" class="sm:col-span-6">
                                    <label class="block mb-1.5 text-xs font-semibold tracking-wider text-gray-600 uppercase dark:text-gray-400">
                                        Estatus *
                                    </label>

                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                        <label
                                            v-for="estatus in estatusList"
                                            :key="estatus.id"
                                            class="relative grid grid-cols-[1.5rem_1fr_1.5rem] items-center h-9 border rounded-full cursor-pointer overflow-hidden px-3 group select-none transition-all duration-200 active:scale-95"
                                            :title="estatus.nombre"
                                            :style="{
                                                '--status-color': getStatusColor(estatus.color),
                                                backgroundColor: Number(form.id_estatus) === Number(estatus.id) ? getStatusColor(estatus.color) + '15' : undefined
                                            }"
                                            :class="
                                                Number(form.id_estatus) === Number(estatus.id)
                                                    ? 'border-[var(--status-color)] ring-1 ring-[var(--status-color)] shadow-sm'
                                                    : 'border-gray-300 bg-gray-100 hover:border-[var(--status-color)] dark:border-gray-700 dark:bg-gray-800 dark:hover:border-[var(--status-color)]'
                                            "
                                            @mouseenter="$event.currentTarget.style.backgroundColor = getStatusColor(estatus.color) + '15'"
                                            @mouseleave="$event.currentTarget.style.backgroundColor = Number(form.id_estatus) === Number(estatus.id) ? getStatusColor(estatus.color) + '15' : ''">

                                            <!-- Radio -->
                                            <input
                                                type="radio"
                                                v-model="form.id_estatus"
                                                :value="estatus.id"
                                                class="sr-only"
                                            />

                                            <!-- 1. COLUMNA IZQUIERDA: Check -->
                                            <div class="flex items-center justify-start">
                                                <span
                                                    v-if="Number(form.id_estatus) === Number(estatus.id)"
                                                    class="flex items-center justify-center w-4 h-4 bg-[var(--status-color)] text-white rounded-full text-[9px] font-bold shadow-xs">
                                                    ✓
                                                </span>
                                            </div>

                                            <!-- 2. COLUMNA CENTRAL: Nombre -->
                                            <span class="text-[13px] font-bold leading-tight tracking-wide text-gray-600 uppercase dark:text-gray-100 text-center truncate">
                                                {{ estatus.nombre }}
                                            </span>

                                            <!-- 3. COLUMNA DERECHA -->
                                            <div></div>

                                        </label>
                                    </div>

                                    <!-- Descripción -->
                                    <p
                                        v-if="estatusList?.find(estatus => Number(estatus.id) === Number(form.id_estatus))?.descripcion"
                                        class="mt-1 text-[11px] text-gray-500 truncate dark:text-gray-400">
                                        {{ estatusList?.find(estatus => Number(estatus.id) === Number(form.id_estatus))?.descripcion }}
                                    </p>

                                    <!-- Error -->
                                    <div v-if="form.errors.id_estatus" class="mt-1 text-xs text-red-500">
                                        {{ form.errors.id_estatus }}
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Bloque: Información del Solicitante -->
                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <!-- Encabezado de la sección -->
                           <div class="mb-4 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                <!-- Columna izquierda: Título, Botón y Descripción -->
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white flex items-center gap-2">
                                            <svg class="w-4 h-4 text-color1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Información del Solicitante
                                        </h3>

                                        <span
                                            v-if="solicitanteSeleccionado && solicitanteSeleccionadoId"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 text-[8pt] font-semibold
                                                text-color4-700 bg-color4-50 rounded-md
                                                dark:bg-color4-900/40 dark:text-color4-300">
                                            <i class="fas fa-user-check"></i>
                                            SOLICITANTE REGISTRADO #{{ String(solicitanteSeleccionadoId).padStart(5, '0') }}
                                        </span>
                                            <!-- Estado -->
                                            <span
                                                v-if="solicitanteSeleccionado && !editandoSolicitante && !solicitanteCargadoSolicitudes"
                                                class="inline-flex items-center px-3 py-1 text-[8pt] font-semibold
                                                    text-color2-700 bg-color2-100 rounded-md
                                                    dark:bg-color2-900/40 dark:text-color2-300"
                                            >
                                                SOLICITANTE EXISTENTE
                                            </span>

                                            <span
                                                v-if="editandoSolicitante"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md
                                                    text-[11px] font-semibold
                                                    bg-color1-50 text-color1-700
                                                    dark:bg-color1-900/30 dark:text-color1-400">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                </svg>

                                                EDITANDO DATOS
                                            </span>
                                            <div class="flex flex-wrap items-center gap-2">

                                                <!-- EDITAR -->
                                                <button
                                                    v-if="solicitanteSeleccionado && !editandoSolicitante"
                                                    type="button"
                                                    @click="iniciarEdicionSolicitante"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1
                                                        text-xs font-semibold
                                                        text-color1-40 dark:text-color1-300
                                                        bg-color1-700 dark:bg-color1-900/20
                                                        border border-color1-800 dark:border-color1-800
                                                        rounded-full
                                                        hover:bg-color1-500 hover:border-color1-600 dark:hover:bg-color1-900/40
                                                        transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                    </svg>

                                                    Editar
                                                </button>

                                                <!-- CANCELAR EDICIÓN -->
                                                <button
                                                    v-if="editandoSolicitante"
                                                    type="button"
                                                    @click="cancelarEdicionSolicitante"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1
                                                        text-xs
                                                        text-white dark:text-gray-300
                                                        bg-gray-400 dark:bg-gray-800
                                                        border border-gray-400 dark:border-gray-600
                                                        rounded-full
                                                        hover:bg-gray-500 dark:hover:bg-gray-700
                                                        transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg>

                                                    Cancelar edición
                                                </button>

                                                <!-- LIMPIAR -->
                                                <button
                                                    v-if="!editandoSolicitante"
                                                    type="button"
                                                    @click="limpiarSolicitante"
                                                    class="group inline-flex items-center px-2 py-1
                                                        text-xs font-medium
                                                        bg-gray-500
                                                        text-white
                                                        border border-gray-600 rounded-full shadow-sm
                                                        hover:bg-gray-400 hover:border-gray-500
                                                        dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600
                                                        dark:hover:bg-gray-700
                                                        transition-colors">
                                                    <svg class="w-3.5 h-3.5 mr-1 mx-0.5 text-white"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    Limpiar
                                                </button>
                                            </div>
                                    </div>

                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Proporciona los datos de contacto y generales de la persona que realiza la petición.
                                    </p>
                                </div>

                                <!-- Columna derecha: Indicativo de NUEVO (Color Verde) -->
                                <div v-if="esSolicitanteNuevo && !editandoSolicitante && !solicitanteSeleccionado" class="flex items-center self-start sm:self-auto">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 shadow-sm animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        NUEVO SOLICITANTE
                                    </span>
                                </div>
                            </div>

                            <!-- Fila 2: CURP, Nombre y Apellidos (1 Renglón) -->
                           <div class="relative sm:col-span-9">
                                <!-- Fila de Inputs (CURP, Nombre, Apellidos) -->
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-9 pt-0.5">
                                    <!-- CURP -->
                                    <div class="sm:col-span-2">
                                        <!-- Etiqueta con el contenedor flexible para alinear el título a la izquierda y el check a la derecha -->
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">CURP</label>
                                            
                                            <!-- SVG de Éxito (A un lado del label) -->
                                            <div v-if="!esCurpInvalida && formBusquedaSolicitante.curp?.length === 18 && !solicitanteBloqueado" class="flex items-center text-color2-600 dark:text-color2-400">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        
                                        <!-- Contenedor relativo de los inputs (ya sin el pr-10 forzado porque el icono ya no va dentro) -->
                                        <div class="relative">
                                            <!-- Input Móvil -->
                                            <input 
                                                type="text" 
                                                v-model="formBusquedaSolicitante.curp" 
                                                @input="alEscribirCurp"
                                                @blur="validarCurp"
                                                :disabled="solicitanteBloqueado"
                                                maxlength="18"
                                                placeholder="Ej. ABCD010101..."
                                                class="block placeholder:text-gray-400 placeholder:opacity-80 dark:placeholder:text-gray-500 w-full sm:hidden placeholder:normal-case rounded-lg border-0 py-2 px-3 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-inset focus:ring-color1 sm:text-sm uppercase custom-native"
                                                :class="{
                                                    'opacity-70 cursor-not-allowed bg-gray-100 dark:bg-gray-800/70': solicitanteBloqueado,
                                                    'ring-red-500 focus:ring-red-500': esCurpInvalida,
                                                }"
                                                style="border-radius: 9999px!important"
                                            />

                                            <!-- Input para Escritorio y Tablets -->
                                            <input
                                                type="text"
                                                v-model="formBusquedaSolicitante.curp"
                                                @input="alEscribirCurp"
                                                @blur="validarCurp"
                                                :disabled="solicitanteBloqueado"
                                                maxlength="18"
                                                :placeholder="solicitanteSeleccionado && !editandoSolicitante ? '' : 'Ej. ABCD010101HMC1111'"
                                                class="hidden custom-native sm:block placeholder:text-gray-400 placeholder:opacity-80 dark:placeholder:text-gray-500 placeholder:normal-case rounded-lg border-0 py-2 px-3 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-inset focus:ring-color1 sm:text-sm uppercase w-full"
                                                :class="{
                                                    'opacity-70 cursor-not-allowed bg-gray-100 dark:bg-gray-800/70': solicitanteBloqueado,
                                                    'ring-red-500 focus:ring-red-500': esCurpInvalida,
                                                }"
                                                style="border-radius: 16px!important"
                                            />
                                        </div>
                                        
                                        <div v-if="!form.errors.solicitante_curp && esCurpInvalida" class="text-red-500 text-xs mt-1">
                                            La estructura de la CURP no es válida.
                                        </div>

                                        <span v-if="solicitanteBloqueado && !formBusquedaSolicitante.curp" class="text-xs text-gray-400 dark:text-gray-500 italic mt-1 block">
                                            Sin CURP registrada
                                        </span>

                                        <!-- Mensaje de Error del Backend -->
                                        <div v-if="form.errors.solicitante_curp" class="text-red-500 text-xs mt-1">
                                            {{ form.errors.solicitante_curp }}
                                        </div>
                                    </div>
                                    <!-- Nombre(s) -->
                                    <div class="sm:col-span-3">
                                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">Nombre *</label>
                                        <input 
                                            type="text" 
                                            v-model="formBusquedaSolicitante.nombre" 
                                            @input="buscarSolicitante"
                                            :disabled="solicitanteBloqueado"
                                            maxlength="35"
                                            placeholder="Ej. JUAN"
                                            class="block custom-native placeholder:text-gray-400 placeholder:opacity-80 dark:placeholder:text-gray-500 w-full placeholder:normal-case rounded-lg border-0 py-2 px-3 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-inset focus:ring-color1 sm:text-sm uppercase",
                                            :class="{
                                                'opacity-70 cursor-not-allowed bg-gray-100 dark:bg-gray-800/70':
                                                    solicitanteBloqueado
                                            }"
                                            style="border-radius: 16px!important"
                                        />
                                        <div v-if="form.errors.solicitante_nombre" class="text-red-500 text-xs mt-1">{{ form.errors.solicitante_nombre }}</div>
                                    </div>

                                    <!-- Apellidos -->
                                    <div class="sm:col-span-4">
                                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">Apellido(s) *</label>
                                        <input 
                                            type="text" 
                                            v-model="formBusquedaSolicitante.apellidos" 
                                            @input="buscarSolicitante"
                                            :disabled="solicitanteBloqueado"
                                            maxlength="60"
                                            placeholder="Ej. PÉREZ"
                                            class="block custom-native placeholder:text-gray-400 placeholder:opacity-80 dark:placeholder:text-gray-500 w-full placeholder:normal-case rounded-lg border-0 py-2 px-3 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-inset focus:ring-color1 sm:text-sm uppercase"
                                            :class="{
                                                'opacity-70 cursor-not-allowed bg-gray-100 dark:bg-gray-800/70':
                                                    solicitanteBloqueado
                                            }"
                                            style="border-radius: 16px!important"
                                            />
                                        <div v-if="form.errors.solicitante_apellidos" class="text-red-500 text-xs mt-1">{{ form.errors.solicitante_apellidos }}</div>
                                    </div>
                                </div>

                                <!-- Panel Flotante de Sugerencias de Búsqueda -->
                                <div 
                                    v-if="mostrarResultadosSolicitante && (cargandoSolicitantes || resultadosBusquedaSolicitante.length > 0)" 
                                    class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto transition-opacity duration-200"
                                    :class="{ 'opacity-80 pointer-events-none': cargandoSolicitantes }">
                                    <!-- 1. ESTADO DE CARGA (Spinner) -->
                                    <div v-if="cargandoSolicitantes" class="p-6 text-center flex items-center justify-center gap-2 text-gray-500 dark:text-gray-400 text-xs">
                                        <svg class="animate-spin h-4 w-4 text-color1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Buscando solicitantes...</span>
                                    </div>

                                    <!-- 2. RESULTADOS ENCONTRADOS -->
                                    <template v-else-if="resultadosBusquedaSolicitante.length > 0">
                                        <div class="p-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2 bg-gray-50 dark:bg-gray-800">
                                            <span class="truncate">
                                                <span v-if="resultadosBusquedaSolicitante.length > 1">
                                                    {{ resultadosBusquedaSolicitante.length }} solicitantes encontrados
                                                </span>
                                                <span v-else>
                                                    1 solicitante encontrado
                                                </span>
                                            </span>

                                            <button 
                                                type="button" 
                                                @click="cerrarResultadosSolicitante"
                                                class="inline-flex items-center gap-1 text-[11px] text-gray-400 hover:text-color1-600 dark:hover:text-color1-400 bg-white dark:bg-gray-700 px-2 py-0.5 rounded-full border border-gray-200 dark:border-gray-600 transition-colors normal-case font-normal"
                                                title="Cerrar lista">
                                                <span>Cerrar</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <ul>
                                            <li 
                                                v-for="s in resultadosBusquedaSolicitante" 
                                                :key="s.id"
                                                @click="seleccionarSolicitante(s)"
                                                class="px-3 sm:px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700/50 cursor-pointer text-xs text-gray-700 dark:text-gray-200 border-b border-gray-50 dark:border-gray-700/50 last:border-none flex justify-between items-center gap-2 sm:gap-4">
                                                
                                                <div class="space-y-1 min-w-0 flex-1">
                                                    <div class="font-bold text-gray-900 dark:text-white uppercase text-sm truncate">
                                                        {{ s.nombre }} {{ s.apellidos }}
                                                    </div>
                                                    
                                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-gray-500 dark:text-gray-400 text-xs min-w-0">
                                                        
                                                        <!-- CURP -->
                                                        <span class="inline-flex items-center gap-1 min-w-0">
                                                            <svg class="w-3.5 h-3.5 text-color1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                                            </svg>
                                                            <strong class="text-gray-700 dark:text-gray-300 font-medium truncate">{{ s.curp || 'S/N' }}</strong>
                                                        </span>

                                                        <!-- TELÉFONO -->
                                                        <span class="inline-flex items-center gap-1 min-w-0">
                                                            <svg class="w-3.5 h-3.5 text-color1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                            </svg>
                                                            <strong class="text-gray-700 dark:text-gray-300 font-medium truncate">{{ s.numero_telefonico || 'S/N' }}</strong>
                                                        </span>

                                                        <!-- LOCALIDAD -->
                                                        <span class="inline-flex items-center gap-1 min-w-0">
                                                            <svg class="w-3.5 h-3.5 text-color1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            </svg>
                                                            <strong class="text-gray-700 dark:text-gray-300 font-medium truncate uppercase">{{ s.localidad?.nombre || 'S/N' }}</strong>
                                                        </span>

                                                    </div>
                                                </div>

                                                <div class="hidden sm:block shrink-0">
                                                    <span class="text-[10px] bg-color1-50 text-color1 px-2.5 py-1.5 rounded-full font-medium uppercase">
                                                        Seleccionar
                                                    </span>
                                                </div>
                                            </li>
                                        </ul>
                                    </template>
                                </div>
                            </div>

                            <!-- Fila 3: Teléfono y Localidad (Siguiente Renglón) -->
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-4 pt-4 mt-2 dark:border-gray-800">
                                
                                <div class="sm:col-span-1">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">
                                        Núm. Telefónico
                                    </label>
                                    <input 
                                        type="text" 
                                        v-model="formBusquedaSolicitante.numero_telefonico" 
                                        @input="formatearTelefono; telefonoValidado = false"
                                        @blur="validarTelefono"
                                        :disabled="solicitanteBloqueado"
                                        @paste="handlePasteTelefono"
                                        maxlength="10"
                                        :placeholder="solicitanteSeleccionado && !editandoSolicitante ? '' : 'Ej. 6690000000'"
                                        class="block custom-native placeholder:text-gray-400 placeholder:opacity-80 dark:placeholder:text-gray-500 w-full placeholder:normal-case rounded-lg border-0 py-2 px-3 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-inset focus:ring-color1 sm:text-sm"
                                        :class="{
                                            'opacity-70 cursor-not-allowed bg-gray-100 dark:bg-gray-800/70':
                                                solicitanteBloqueado,

                                            'ring-red-500 focus:ring-red-500':
                                                esTelefonoInvalido && telefonoValidado,                                            
                                        }"
                                        style="border-radius: 16px!important"
                                    />
                                    <div 
                                        v-if="esTelefonoInvalido" class="text-red-500 text-xs mt-1">
                                        El núm. telefónico debe tener 10 dígitos.
                                    </div>

                                    <span v-if="solicitanteBloqueado && !formBusquedaSolicitante.numero_telefonico" class="text-xs text-gray-400 dark:text-gray-500 italic mt-1 block">
                                            Sin número registrado
                                    </span>

                                    <div 
                                        v-if="form.errors.solicitante_numero_telefonico" 
                                        class="text-red-500 text-xs mt-1">
                                        {{ form.errors.solicitante_numero_telefonico }}
                                    </div>
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">
                                        Localidad *  
                                    </label>

                                    <!-- <select
                                        v-model="formBusquedaSolicitante.id_localidad"
                                        @mousedown="(e) => { if (solicitanteBloqueado) e.preventDefault(); }"
                                        @change="solicitanteBloqueado ? null : actualizarLocalidad($event)"
                                        class="block w-full rounded-lg border-0 py-2 px-3 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-inset focus:ring-color1 sm:text-sm"
                                        :class="{
                                            'solicitante-bloqueado': solicitanteBloqueado
                                        }"
                                        style="border-radius: 9999px!important">
                                        <option value="" disabled>
                                            SELECCIONA UNA LOCALIDAD...
                                        </option>

                                        <option
                                            v-for="localidad in localidadesList"
                                            :key="localidad.id"
                                            :value="localidad.id">
                                            {{ localidad.nombre }}
                                        </option>
                                    </select> -->
                                    <el-select
                                        v-model="formBusquedaSolicitante.id_localidad"
                                        placeholder="Selecciona una localidad..."
                                        class="custom-select-gray w-full"
                                        :class="{ 'solicitante-bloqueado': solicitanteBloqueado }"
                                        @mousedown="(e) => { if (solicitanteBloqueado) e.preventDefault(); }"
                                        @change="solicitanteBloqueado ? null : actualizarLocalidad($event)"
                                        style="border-radius: 16px !important;"
                                    >
                                        <el-option
                                            v-for="localidad in localidadesList"
                                            :key="localidad.id"
                                            :label="localidad.nombre"
                                            :value="localidad.id"
                                        />
                                    </el-select>

                                    <div
                                        v-if="form.errors.id_solicitante_localidad"
                                        class="text-red-500 text-xs mt-1">
                                        {{ form.errors.id_solicitante_localidad }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Detalle de la Petición y Asignación -->
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <!-- Encabezado de la sección -->
                            <div class="mb-4">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-4 h-4 text-color1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Detalles de la Petición y Asignación
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Especifica la descripción de la solicitud ciudadana y el monto o recurso autorizado.
                                </p>
                            </div>

                            <!-- Contenido (Textarea + Cantidad Aprobada) -->
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 pt-2">
                                
                                <!-- Petición (Textarea extendido) -->
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">Petición / Descripción detallada *</label>
                                    <textarea 
                                        v-model="form.peticion" 
                                        rows="4"
                                        maxlength="255"
                                        placeholder="ESCRIBE LA PETICIÓN..."
                                        class="block placeholder:text-gray-400 placeholder:opacity-80 dark:placeholder:text-gray-500 w-full rounded-xl border-0 py-2 px-3 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-inset focus:ring-color1 sm:text-sm uppercase"
                                    ></textarea>
                                      <div class="flex justify-between items-center mt-1">
                                        <div v-if="form.errors.peticion" class="text-red-500 text-xs">{{ form.errors.peticion }}</div>
                                        <span class="text-[10px] text-gray-400 ml-auto">{{ form.peticion ? form.peticion.length : 0 }}/255</span>
                                    </div>
                                </div>

                                 <!-- ANDO AGREGANDO ESTA VALIDACIÓN AL BUTTON DE GUARDAR Y SU MENSAJE DE LADO -->
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1.5">Cantidad Aprobada</label>
                                    <div class="relative rounded-lg shadow-sm">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="text-gray-500 sm:text-sm">$</span>
                                        </div>
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            :value="form.cantidad_aprobada"
                                            @input="formatearCantidadAprobada"
                                            @blur="validarCantidadAprobada"
                                            placeholder="0.00"
                                            class="block custom-native w-full rounded-lg border-0 py-2 pl-7 pr-3 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-inset focus:ring-color1 sm:text-sm"
                                            :class="{
                                                'ring-red-500 focus:ring-red-500': esCantidadAprobadaInvalida
                                            }"
                                            style="border-radius: 16px!important"
                                        />
                                    </div>
                                    <div v-if="form.errors.cantidad_aprobada" class="text-red-500 text-xs mt-1">{{ form.errors.cantidad_aprobada }}</div>
                                    <div 
                                        v-if="esCantidadAprobadaInvalida" 
                                        class="text-red-500 text-xs mt-1">
                                        <template v-if="Number(String(form.cantidad_aprobada).replace(/,/g, '')) > MAX_CANTIDAD_APROBADA">
                                            La cantidad aprobada no puede ser mayor a $99,999,999.99.
                                        </template>

                                        <template v-else-if="Number(String(form.cantidad_aprobada).replace(/,/g, '')) <= 0">
                                            La cantidad aprobada debe ser mayor a $0.00.
                                        </template>

                                        <template v-else>
                                            La cantidad aprobada no tiene un formato válido.
                                        </template>
                                    </div>
                                    <p class="text-[11px] text-gray-400 mt-1">Monto total en moneda nacional (MXN)</p>
                                </div>
                            </div>

                            <!-- SECCIÓN RETRÁCTIL DE OBSERVACIONES -->
                            <div class="mt-6">
                                <!-- Botón para desplegar / Ocultar (Solo se muestra si está cerrado y no hay texto escrito) -->
                                <div v-if="!mostrarObservaciones && !form.observaciones">
                                    <button 
                                        type="button"
                                        @click="mostrarObservaciones = true"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-color1 dark:text-gray-400 dark:hover:text-color1 transition-colors group"
                                    >
                                        <span class="flex items-center justify-center w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-800 group-hover:bg-color1-50 dark:group-hover:bg-gray-700 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </span>
                                        Añadir observaciones adicionales
                                    </button>
                                </div>

                                <!-- Contenedor desplegable del Textarea de Observaciones -->
                                <div v-show="mostrarObservaciones || form.observaciones" class="transition-all duration-300 ease-in-out">
                                    <div class="flex justify-between items-center mb-1.5">
                                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                            Observaciones
                                        </label>
                                        <!-- Botón sutil para cerrar/ocultar si el usuario se arrepiente y el campo está vacío -->
                                        <button 
                                            v-if="!form.observaciones"
                                            type="button" 
                                            @click="mostrarObservaciones = false"
                                            class="text-[11px] text-gray-400 hover:text-red-500 transition-colors">
                                            Ocultar campo
                                        </button>
                                    </div>
                                    
                                    <textarea 
                                        v-model="form.observaciones" 
                                        rows="2" 
                                        maxlength="255"
                                        placeholder="Escribe alguna nota aclaratoria u observación relevante..."
                                        class="block w-full rounded-xl border-0 py-2 px-3 text-gray-900 dark:text-white 
                                        bg-gray-50 dark:bg-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 
                                        focus:ring-2 focus:ring-inset focus:ring-color1 sm:text-sm uppercase
                                        placeholder:text-gray-400 dark:placeholder:text-gray-500"
                                    ></textarea>
                                    
                                    <div class="flex justify-between items-center mt-1">
                                        <div v-if="form.errors.observaciones" class="text-red-500 text-xs">{{ form.errors.observaciones }}</div>
                                        <span class="text-[10px] text-gray-400 ml-auto">{{ form.observaciones ? form.observaciones.length : 0 }}/255</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-0 border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <!-- Lado Izquierdo: Estado textual limpio -->
                    <div class="flex items-center gap-2.5 px-3.5 py-2 rounded-lg text-sm transition-colors bg-gray-100 dark:bg-gray-800 border"
                        :class="{
                            'border-red-200 dark:border-red-900/50': esCurpInvalida || esTelefonoInvalido || (form.fecha && !fechaValida) || esCantidadAprobadaInvalida || Object.keys(form.errors).length > 0,
                            'border-amber-200 dark:border-amber-900/50': (esCurpInvalida || esTelefonoInvalido || (form.fecha && !fechaValida) || esCantidadAprobadaInvalida || Object.keys(form.errors).length > 0) && estaIncompleto,
                            'border-emerald-200 dark:border-emerald-900/50': !estaIncompleto && !esCurpInvalida && !esCurpInvalida && !esTelefonoInvalido && !(form.fecha && !fechaValida) && !esCantidadAprobadaInvalida && Object.keys(form.errors).length === 0
                        }">
                        
                        <!-- Ícono dinámico -->
                        <svg v-if="esCurpInvalida || esTelefonoInvalido || (form.fecha && !fechaValida) || esCantidadAprobadaInvalida || Object.keys(form.errors).length > 0" class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <svg v-else-if="estaIncompleto" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>

                        <!-- Texto combinado (Muestra faltantes y errores de forma simultánea si ambos aplican) -->
                        <span class="font-medium text-gray-700 dark:text-gray-300">
                            <!-- Caso A: Faltan datos Y ADEMÁS hay errores -->
                            <template v-if="estaIncompleto && (esCurpInvalida || esTelefonoInvalido || (form.fecha && !fechaValida) || esCantidadAprobadaInvalida || Object.keys(form.errors).length > 0 || (form.fecha && !fechaValida))">

                                <span class="text-amber-600 dark:text-amber-400">
                                    {{ camposFaltantes.length === 1 ? 'Falta 1 dato obligatorio por capturar' : `Faltan ${camposFaltantes.length} datos obligatorios por capturar` }}
                                </span>
                                y
                                <span class="text-red-600 dark:text-red-400 font-semibold">
                                    {{ cantidadErrores === 1
                                        ? 'hay 1 error por corregir.'
                                        : `hay ${cantidadErrores} errores por corregir.` }}
                                </span>
                            </template>

                            <!-- Caso B: Solo hay errores -->
                            <template v-else-if="esTelefonoInvalido || (form.fecha && !fechaValida) || esCantidadAprobadaInvalida || Object.keys(form.errors).length > 0 || (form.fecha && !fechaValida)">
                                <span class="text-red-600 dark:text-red-400 font-semibold">
                                    Atención:
                                </span>

                                {{ cantidadErrores === 1
                                    ? 'Hay 1 error que corregir.'
                                    : `Hay ${cantidadErrores} errores que corregir.` }}

                            </template>

                            <!-- Caso C: Solo faltan datos -->
                            <template v-else-if="estaIncompleto">
                                {{ camposFaltantes.length === 1 ? 'Falta' : 'Faltan' }}

                                <strong class="text-amber-600 dark:text-amber-400 font-semibold">
                                    {{ camposFaltantes.length }}
                                    {{ camposFaltantes.length === 1 ? 'dato obligatorio' : 'datos obligatorios' }}
                                </strong>
                                por capturar.
                            </template>

                            <!-- Caso D: Todo correcto -->
                            <template v-else>
                                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">
                                    Listo:
                                </span>
                               Puedes {{ nuevaSolicitud ? 'guadar': 'actualizar' }} la solicitud.
                            </template>
                        </span>
                    </div>

                    <!-- Lado Derecho: Botones de Acción -->
                    <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center gap-3">
                        <!-- Botón Limpiar -->
                        <button
                            v-if="nuevaSolicitud" 
                            type="button"
                            @click="confirmarLimpiarFormulario"
                            class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-full shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition text-center">
                            Limpiar Formulario
                        </button>

                        <button
                            v-if="!nuevaSolicitud && isPage"
                            @click="abrirNuevaSolicitud"
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

                        <!-- Botón Guardar con Tooltip / Popover integrado al hacer hover si está incompleto -->
                        <div class="relative group">
                            <!-- Panel flotante con sección de faltantes y sección de inválidos/errores -->
                            <div v-if="estaIncompleto || Object.keys(form.errors).length > 0 || esCurpInvalida || (form.fecha && !fechaValida) || esTelefonoInvalido || esCantidadAprobadaInvalida" 
                                class="absolute bottom-full right-0 sm:right-0 mb-2 hidden group-hover:block z-20 w-[90vw] max-w-xs sm:w-72 p-3 bg-gray-900 dark:bg-gray-800 text-white text-xs rounded-xl shadow-xl border border-gray-700 left-1/2 -translate-x-1/2 sm:left-auto sm:translate-x-0">
                                
                                <!-- Sección 1: Campos Faltantes -->
                                <div v-if="camposFaltantes.length > 0">
                                    <div class="flex items-center gap-2 mb-2 pb-2 border-b border-gray-800 dark:border-gray-700/60">
                                        <div class="w-5 h-5 rounded-full bg-amber-500/20 flex items-center justify-center shrink-0">
                                            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-amber-400 tracking-wide uppercase text-[10px]">Falta capturar:</span>
                                    </div>
                                    <ul class="list-disc list-inside space-y-0.5 text-gray-300 mb-2">
                                        <li v-for="campo in camposFaltantes" :key="campo">{{ campo }}</li>
                                    </ul>
                                </div>

                                <!-- Sección 2: Campos Inválidos o con Error -->
                                <div v-if="Object.keys(form.errors).length > 0 || esCurpInvalida || (form.fecha && !fechaValida) || esTelefonoInvalido || esCantidadAprobadaInvalida">
                                    <div class="flex items-center gap-2 mb-2 pb-2 border-b border-gray-800 dark:border-gray-700/60" :class="{'pt-2': camposFaltantes.length > 0}">
                                        <div class="w-5 h-5 rounded-full bg-red-500/20 flex items-center justify-center shrink-0">
                                            <svg class="w-3.5 h-3.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </div>
                                        
                                        <span v-if="(
                                                    Object.keys(form.errors).length +
                                                    (esCurpInvalida ? 1 : 0) +
                                                    (esTelefonoInvalido ? 1 : 0) +
                                                    (esCantidadAprobadaInvalida ? 1 : 0) +
                                                    (form.fecha && !fechaValida ? 1 : 0)
                                                ) === 1" class="font-semibold text-red-400 tracking-wide uppercase text-[10px]">
                                            Corrige el siguiente error
                                        </span>
                                        <span v-else class="font-semibold text-red-400 tracking-wide uppercase text-[10px]">
                                            Corrige los siguientes errores
                                        </span>
                                    </div>
                                    <ul class="list-disc list-inside space-y-0.5 text-gray-300">
                                        <li v-if="esCurpInvalida">CURP (Estructura o formato no válido)</li>
                                        <li v-if="esTelefonoInvalido">Num. Telefónico (Debe contener 10 dígitos)</li>
                                        <li v-if="esCantidadAprobadaInvalida">Cantidad Aprobada (Formato o cantidad no válidos)</li>
                                        <li v-if="form.fecha && !fechaValida">
                                            Fecha (Formato o fecha no válida)
                                        </li>
                                        <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Botón de Envío -->
                            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">

                                <!-- Botón 1: Guardar y Continuar (Solo si es nueva solicitud) -->
                                <button 
                                    v-if="nuevaSolicitud"
                                    type="button"
                                    @click.prevent="handleSubmit(true, false)"
                                    :disabled="enviando || form.processing || (form.fecha && !fechaValida) || estaIncompleto || form.hasErrors || esCurpInvalida || esTelefonoInvalido || esCantidadAprobadaInvalida"
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-white bg-color2-700 hover:bg-color2-800 rounded-full shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed text-center"
                                >
                                    Guardar y Continuar
                                </button>

                                <!-- Botón 2: Guardar y Salir / Actualizar -->
                                <button
                                    v-if="nuevaSolicitud"
                                    type="button"
                                    @click.prevent="handleSubmit(false, false)" 
                                    :disabled="enviando || form.processing || (form.fecha && !fechaValida) || estaIncompleto || form.hasErrors || esCurpInvalida || esTelefonoInvalido || esCantidadAprobadaInvalida"
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-white bg-color1-700 hover:bg-color1-800 rounded-full shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed text-center"
                                >
                                    Guardar y Salir
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    @click.prevent="handleSubmit(false, true)" 
                                    :disabled="enviando || form.processing || (form.fecha && !fechaValida) || estaIncompleto || form.hasErrors || esCurpInvalida || esTelefonoInvalido || esCantidadAprobadaInvalida"
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 text-sm font-semibold text-white bg-color1-700 hover:bg-color1-800 rounded-full shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed text-center"
                                >
                                    Actualizar Solicitud
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
          </form>
        </div>
</template>