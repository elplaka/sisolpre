<script setup>
    import { ref, onUnmounted, onMounted, watch, nextTick } from 'vue';
    import { router } from '@inertiajs/vue3'
    import { debounce } from 'lodash'; // Necesitas instalar lodash o importarlo si está globalmente disponible
    import Pagination from '@/Components/Pagination.vue'
    import Formulario from './Formulario.vue';

    const isLoading = ref(false);

    const props = defineProps({
        propiedades: Object,
        pagination: Object,
        tiposPropiedad: Object,
    });

    const modalPropiedadAbierta = ref(false);
    const selectedPropiedad = ref(null)
    const currentPage = ref(1);

    function handlePageChange(page) {
        currentPage.value = page;
        debounceFetchPropiedades(page)
    }

   const fetchPropiedades = (page = 1) => {
        router.post(route('propiedades'), {
            page: page,
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {  }
        });
    };

    const debounceFetchPropiedades = debounce((page) => {
        fetchPropiedades(page); 
    }, 1);

    onMounted(() => {
        inicializarFiltros();
    });

    const inicializarFiltros = async () => {
        try {


        } catch (error) {
            console.error("Error al inicializar filtros de trámites:", error);
        }
    };

   const formatearDireccionObjetos = (p) => {
        if (!p) return { principal: 'Sin datos', localidad: '' };

        try {
            // Bloque 1: Calle, Número, Col, CP
            const partesCalle = [];
            if (p.calle?.trim()) partesCalle.push(p.calle.trim());
            
            // 2. Manejo de Número (Lógica solicitada)
            const numRaw = p.numero?.toString().trim().toLowerCase() || '';
            
            // Definimos qué valores consideramos como "sin número"
            const esNumeroInvalido = 
                numRaw === '' || 
                numRaw === '0' || 
                numRaw === 's/n' || 
                numRaw.replace(/\s/g, '') === 's/n'; // maneja 's / n'

            if (!esNumeroInvalido) {
                partesCalle.push(`N° ${p.numero.trim()}`);
            }
            
            if (p.colonia?.nombre) {
                const nom = p.colonia.nombre.trim();
                const upper = nom.toUpperCase();
                const palabrasExcluidas = ["COL", "COLONIA", "COL.",
                "INFONAVIT", "INF.", "INFO.", "INFO", "INF", 
                "FRACCIONAMIENTO", "FRACC", "FRACC.", "FRAC", "FRAC."];
                
                const debeExcluir = palabrasExcluidas.some(palabra => upper.startsWith(palabra));
                
                partesCalle.push(!debeExcluir ? `COL. ${nom}` : nom);
            }
            if (p.codigo_postal) partesCalle.push(`C.P. ${p.codigo_postal}`);

            // Bloque 2: Localidad
            const localidad = p.localidad?.nombre ? p.localidad.nombre.trim() : '';

            return {
                principal: partesCalle.join(', ') || '—',
                localidad: localidad
            };
        } catch (e) {
            return { principal: 'Error en formato', localidad: '' };
        }
    };

    const obtenerNombreCompleto = (contacto) => {
    try {
        // Validación: Si no existe el contacto o la relación persona
        if (!contacto || !contacto.persona) {
            return 'Sin contacto asignado';
        }

        const p = contacto.persona;

        // Filtramos solo los valores que existen y no están vacíos
        // Esto maneja correctamente si el apellido materno es opcional en MySQL
        const partes = [
            p.nombre,
            p.apellidos,
        ].filter(v => v && v.trim() !== '');

        // Unimos con un espacio y transformamos a mayúsculas para consistencia (opcional)
        return partes.length > 0 ? partes.join(' ').toUpperCase() : 'Persona sin nombre';

        } catch (error) {
            console.error("Error al procesar el nombre del contacto:", error);
            return 'Error en datos';
        }
    };

    // Estados reactivos
    const menuAbierto = ref(null);
    const modalHistorial = ref(false);
    const loadingHistorial = ref(false);
    const listaHistorial = ref([]);
    const propiedadSeleccionada = ref(null);

    // Acciones
    const toggleMenu = (id) => {
        menuAbierto.value = menuAbierto.value === id ? null : id;
    };

    const etiquetasCampos = {
        // Propiedad
        'clave_catastral': 'Clave Catastral',
        'calle': 'Calle',
        'numero': 'Número',
        'superficie': 'Superficie',
        'superficie_construccion': 'Superficie de Construcción',
        'codigo_postal': 'Código Postal',
        'referencias_ubicacion': 'Referencias de Ubicación',
        'coordenada_utm_x': 'Coordenada X',
        'coordenada_utm_y': 'Coordenada Y',
        'fecha_aceptacion': 'Fecha de Aceptación',
        'id_colonia' : 'Colonia',
        'activa': 'Estatus Activo',
        
        // Contacto y Persona (los prefijos que pusimos en el backend)
        'Contacto: telefono': 'Teléfono del Contacto',
        'Contacto: email': 'Correo Electrónico',
        'Titular: nombre': 'Nombre del Propietario',
        'Titular: apellidos': 'Apellidos del Propietario',
        'Titular: curp': 'CURP del Propietario'
    };

    const verHistorial = async (propiedad) => {
        // Usamos la clave catastral como pidió tu backend
        if (!propiedad.clave_catastral) return; 
        
        propiedadSeleccionada.value = propiedad;
        modalHistorial.value = true;
        loadingHistorial.value = true;

        try {
            const response = await fetch(`/propiedades/get-historial-propiedad/${encodeURIComponent(propiedad.clave_catastral)}`, {
                // Agrega las comillas
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            })

            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor')
            }

            const data = await response.json()
            listaHistorial.value = data;
        } catch (error) {
            console.error("Error al obtener historial:", error);
        } finally {
            loadingHistorial.value = false;
        }
    };

    const form = ref({
            calle: '',
            numero: '',
            colonia: '',
            ciudad: '',
            // otras propiedades...
        })

    const editPropiedad = async (propiedad) => {
        selectedPropiedad.value = { ...propiedad }
        modalPropiedadAbierta.value = true
    };


    const cerrarModal = () => {
        modalHistorial.value = false;
        propiedadSeleccionada.value = null;
    };

    const formatearClaveVisual = (clave) => {
    if (!clave) return [];
    // Divide el string en un array de grupos de 3 caracteres
    return clave.toString().match(/.{1,3}/g) || [];
    };

    const cerrarMenuAlClicarFuera = (event) => {
    // Si no hay ningún menú abierto, no hacemos nada
    if (menuAbierto.value === null) return;

    // Verificamos si el clic ocurrió dentro del contenedor del menú o del botón que lo abre
    const dentroDelMenu = event.target.closest('.dropdown-contenedor');
    const dentroDelBotonApertura = event.target.closest('.btn-dropdown'); // Asigna esta clase al botón que abre el menú

    // Si el clic fue afuera de ambos elementos, cerramos el menú
    if (!dentroDelMenu && !dentroDelBotonApertura) {
        menuAbierto.value = null;
    }
    };

    // Registrar y limpiar el evento global del DOM de forma segura
    onMounted(() => {
    document.addEventListener('click', cerrarMenuAlClicarFuera);
    });

    onUnmounted(() => {
    document.removeEventListener('click', cerrarMenuAlClicarFuera);
    });
</script>

<style>
    /* Estilos base para el badge */
  .badge-evento {
        display: inline-flex;
        align-items: center; /* Centra verticalmente el contenido */
        justify-content: center;
        height: 30px; /* Forzamos una altura exacta para todos */
        line-height: 1; /* Evita que el interlineado agregue pixeles extra */
    }

    /* 1. Estilo para REGISTRO INICIAL */
    [data-tipo*="Registro"] {
         background-color: var(--color-2-50, #eff6ff) !important; 
        border-color: var(--color-2-100, #bfdbfe) !important;
        color: var(--color-2, #1d4ed8) !important;
    }
    [data-tipo*="Registro"] .icono-tipo::before {
        content: "●"; /* Puedes usar un símbolo o una URL de imagen */
        margin-right: 4px;
    }

    /* 2. Estilo para ACTUALIZACIÓN */
    [data-tipo*="Actualiza"] {
        background-color: var(--color-1-50, #eff6ff) !important; 
        border-color: var(--color-1-100, #bfdbfe) !important;
        color: var(--color-1, #1d4ed8) !important;
    }
    [data-tipo*="Actualiza"] .icono-tipo::before {
        content: "↻";
        font-size: 14px;
    }

    /* 3. Estilo para CANCELACIONES o RECHAZOS (Opcional) */
    [data-tipo*="Cancela"], [data-tipo*="Rechazo"] {
        background-color: #fef2f2 !important; /* red-50 */
        border-color: #fecaca !important;     /* red-200 */
        color: #b91c1c !important;            /* red-700 */
    }
</style>
<template>
    <section class="bg-white dark:bg-gray-900 p-3 sm:p-5">
        <div class="mx-auto max-w-screen-xl lg:px-0 w-[100%] sm:w-[100%] md:w-[100%] lg:w-[100%]">
            <div class="flex flex-col md:flex-row items-start md:items-center md:mb-5 md:mr-2 justify-between">
                <h1 class="text-2xl font-bold ml-2">Propiedades </h1>
            </div>
            <!-- <div v-if="!isLoading" class="relative flex flex-wrap items-center justify-begin gap-2 md:gap-3 mb-2">
                <div class="flex items-center w-full xs:w-1/2 md:w-[22%]">
                    <el-date-picker
                        v-model="rangoFechasQuery"
                        type="daterange"
                        range-separator="-"
                        start-placeholder="Inicio"
                        end-placeholder="Fin"
                        format="DD-MM-YYYY"
                        value-format="DD-MM-YYYY"
                        class="custom-select-gray w-full"
                        :clearable="false"
                        @change="handleFechaChange">
                    </el-date-picker>
                </div>
                <div class="flex items-center w-full xs:w-1/2 md:w-[26%]">
                    <el-select
                        ref="selectEstatusRef"
                        v-model="estatusQuery"
                        class="custom-select-gray w-full"
                        collapse-tags
                        collapse-tags-tooltip
                        :max-collapse-tags="1"
                        filterable
                        multiple
                        placeholder="Estatus"
                        value-key="value"
                        popper-class="custom-select-tooltip"
                        @change="handleEstatusChange">
                        <template #prefix>
                            <div class="flex items-center ml-1">
                                <svg 
                                xmlns="http://www.w3.org/2000/svg" 
                                class="w-5 h-5 mr-2 text-gray-600"
                                viewBox="0 -960 960 960" 
                                fill="currentColor">
                                <path d="M856-390 570-104q-12 12-27 18t-30 6q-15 0-30-6t-27-18L103-457q-11-11-17-25.5T80-513v-287q0-33 23.5-56.5T160-880h287q16 0 31 6.5t26 17.5l352 353q12 12 17.5 27t5.5 30q0 15-5.5 29.5T856-390ZM513-160l286-286-353-354H160v286l353 354ZM260-640q25 0 42.5-17.5T320-700q0-25-17.5-42.5T260-760q-25 0-42.5 17.5T200-700q0 25 17.5 42.5T260-640Zm220 160Z"/>
                                </svg>
                            </div>
                            
                            <div class="flex items-center gap-1">
                                <span 
                                    v-for="estatus in estatusSeleccionadosParaDisplay" 
                                    :key="estatus.id"
                                    :style="{ backgroundColor: estatus.color }" 
                                    class="w-2.5 h-2.5 rounded-full inline-block shrink-0">
                                </span>
                            </div>
                        </template>
                        <el-option 
                            v-for="item in todosEstatus" 
                            :key="item.id" 
                            :label="item.nombre"
                            :value="item.id"> 
                            <div class="flex items-center">
                                <span 
                                :style="{ backgroundColor: item.color }" 
                                class="w-2.5 h-2.5 ml-1 mr-2 rounded-full inline-block shrink-0"> 
                                </span>
                                <span class="text-xs">{{ item.nombre }}</span>
                            </div> 
                        </el-option>
                    </el-select>
                </div>
            </div> -->
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg"> 
                <div>                    
                    <table v-if="!isLoading" class="w-full text-sm text-left text-gray-500 dark:text-gray-400 table-fixed border-collapse">
                        <colgroup>
                            <col class="w-[5%]">
                            <col class="w-[14%]">
                            <col class="w-[7%]">
                            <col class="w-[41%]">
                            <col class="w-[29%]">
                            <col class="w-[4%]">
                        </colgroup>
                        <thead class="text-base text-gray-700 bg-white dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-2 py-3 text-center">Id</th>
                                <th scope="col" class="px-4 py-3">Clave Catastral</th>
                                <th scope="col" class="px-4 py-3">Tipo</th>
                                <th scope="col" class="px-4 py-3">Domicilio</th>
                                <th scope="col" class="px-4 py-3">Propietario(a)</th>
                                <th scope="col" class="px-4 py-3"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="prop in propiedades" :key="prop.id" 
                                class="text-xs border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <td class="px-2 py-2 text-center font-medium">
                                    {{ String(prop.id).slice(-4).padStart(4, '0') }}
                                </td>
                                <!-- <td class="px-4 py-2 text-left">
                                    <span 
                                        class="inline-block bg-color1-500 border border-color1-600 text-color1-40 items-center px-2 py-1 rounded-lg text-[12px] font-medium whitespace-nowrap"
                                        style="letter-spacing: 0.015em;"
                                        title="Clave Catastral">
                                        {{ formatearClaveVisual(prop.clave_catastral) || 'N/A' }}
                                    </span>
                                </td> -->
                               <td class="px-2 py-2 text-left w-[200px] min-w-[200px]">
                                    <div v-if="prop.clave_catastral" 
                                        class="flex w-full justify-between bg-color1-700 border border-color1-800 text-color1-40 items-center px-3 py-1 rounded-lg text-[12px] font-bold">
                                        
                                        <span 
                                            v-for="(grupo, index) in formatearClaveVisual(prop.clave_catastral)" 
                                            :key="index"
                                            class="inline-block"
                                            title="Clave Catastral">
                                            {{ grupo }}
                                        </span>
                                    </div>
                                    
                                    <div v-else class="text-gray-400 text-[11px] px-3 py-1 text-center w-full">
                                        N/A
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-left">
                                    <span class="inline-flex min-w-full box-content justify-center text-center bg-color1-40 border border-color1-500 text-color1-700 items-center px-2 py-1 rounded-lg text-[11px] font-medium whitespace-nowrap">
                                        {{ prop.tipo?.nombre || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 align-middle h-14">
                                    <div class="flex flex-col gap-1 justify-center h-full">
                                        <!-- Línea Principal -->
                                        <div class="flex items-center gap-2">
                                            <!-- Subimos el SVG 1px con un margin-bottom negativo o translate para nivelarlo -->
                                            <svg class="w-3.5 h-3.5 flex-shrink-0 text-color1-600 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                            </svg>
                                            <span class="line-clamp-1 truncate leading-none inline-block" :title="formatearDireccionObjetos(prop).principal">
                                                {{ formatearDireccionObjetos(prop).principal }}
                                            </span>
                                        </div>

                                        <!-- Línea Localidad -->
                                        <template v-if="formatearDireccionObjetos(prop).localidad">
                                            <div class="flex items-center gap-2">
                                                <!-- Aplicamos el mismo -mt-0.5 para consistencia -->
                                                <svg class="w-3.5 h-3.5 flex-shrink-0 text-color1-600 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                <span class="uppercase leading-none inline-block">
                                                    {{ formatearDireccionObjetos(prop).localidad }}
                                                </span>
                                            </div>
                                        </template>

                                    </div>
                                </td>
                                <td class="px-4 py-2 text-left truncate font-bold">
                                    {{ obtenerNombreCompleto(prop.contacto) }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <div class="relative inline-block text-left">
                                        <button @click.stop="toggleMenu(prop.id)" 
                                                class="p-2 hover:bg-gray-100 rounded-full transition-colors color1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                            </svg>
                                        </button>

                                        <div v-if="menuAbierto === prop.id" 
                                            class="absolute right-0 z-50 mt-2 w-56 bg-white rounded-md shadow-lg border border-gray-100 ring-1 ring-black ring-opacity-5 shadow-2xl">
                                            <div class="py-1">
                                                <!-- <button @click="editPropiedad(prop); menuAbierto = null" 
                                                        class="group relative w-full flex items-center px-4 py-2.5 text-sm text-gray-700 transition-all duration-300 hover:bg-color1-40 hover:text-color1-700 overflow-hidden border-t border-gray-50">
                                                    <div class="absolute left-0 top-0 h-full w-1.5 bg-color1-500 opacity-0 -translate-x-full group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 ease-out rounded-r-full"></div>
                                                    <svg class="mr-3 h-4 w-4 text-color1-500 transition-all duration-300 transform group-hover:scale-110" 
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    <span class="font-bold transition-all duration-300 group-hover:translate-x-1">
                                                        Editar
                                                    </span>
                                                </button> -->
                                                <button @click="verHistorial(prop); menuAbierto = null" 
                                                        class="group relative w-full flex items-center px-4 py-2.5 text-sm text-gray-700 transition-all duration-300 hover:bg-color1-40 hover:text-color1-700 overflow-hidden border-t border-gray-50">
                                                    <div class="absolute left-0 top-0 h-full w-1.5 bg-color1-500 opacity-0 -translate-x-full group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 ease-out rounded-r-full"></div>
                                                    <svg class="mr-3 h-4 w-4 text-color1-500 transition-all duration-300 transform group-hover:scale-110" 
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span class="font-bold transition-all duration-300 group-hover:translate-x-1">
                                                        Ver Bitácora de Cambios
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="propiedades.length === 0 && !isLoading" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No hay propiedades registradas
                </div>
                <Pagination v-if="!isLoading" :data="pagination" @page-changed="handlePageChange" />
                <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity">
                    <div class="flex items-center">
                        <svg class="animate-spin h-8 w-8 text-color1-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="ml-2 text-gray-300">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div v-if="modalPropiedadAbierta" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50">
        <div class='bg-white rounded-2xl w-full transition-all duration-500 overflow-hidden shadow-2xl max-w-[64rem]'>
            <Formulario
                :selectedPropiedad="selectedPropiedad"
                :tiposPropiedad="props.tiposPropiedad"
                @cancel="modalPropiedadAbierta = false" 
                @save="modalPropiedadAbierta = false"/>
        </div>        
    </div>
    <div v-if="modalHistorial" 
     class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-600/70">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl flex flex-col overflow-hidden border-t-4 border-color1-600">
            <div class="px-8 py-6 border-b-2 border-gray-100 bg-white flex justify-between items-center relative overflow-hidden">
            <div  class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-color1-700" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414l-4.414-4.414A2 2 0 0011.172 2H9z" />
                    </svg>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Historial de la Propiedad</span>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Bitácora de Cambios</h2>
                
                <div class="mt-2 flex items-center gap-3">
                    <div class="inline-flex items-center border border-color1-500 rounded bg-color1-600 overflow-hidden shadow-sm">
                        <span class="bg-black/10 px-2 py-1 text-[9pt] font-bold text-white uppercase border-r border-color1-700 tracking-widest">
                            ID
                        </span>
                        <div class="px-3 py-1 font-bold text-gray-100 text-[9pt]">
                            {{ propiedadSeleccionada?.id }}
                        </div>
                    </div>
                    <div class="inline-flex items-center border border-gray-300 rounded bg-white overflow-hidden shadow-sm">
                        <span class="bg-gray-100 px-2 py-1 text-[9pt] font-black text-gray-500 uppercase border-r border-gray-300 tracking-tighter">
                            Clave Catastral
                        </span>
                        <div class="px-3 py-1 inline-flex gap-x-1 font-bold text-gray-800 text-[9pt]">
                            <span v-for="(grupo, index) in formatearClaveVisual(propiedadSeleccionada?.clave_catastral)" 
                                :key="index">
                                {{ grupo }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <button v-if="!loadingHistorial" @click="cerrarModal" class="group flex flex-col items-center gap-1">
                <div class="p-2 rounded-full group-hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </div>
            </button>
        </div>
        <div class="p-0 overflow-y-auto max-h-[65vh] bg-white">
            <div v-if="loadingHistorial" class="p-20 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-200 border-t-color1-600 mb-4"></div>
                <p class="text-sm text-gray-500">Consultando base de datos oficial...</p>
            </div>
            <table v-else class="w-full border-separate border-spacing-0">
                <thead class="sticky top-0 z-20">
                    <tr class="text-[11pt] text-gray-700">
                        <th class="px-6 py-4 w-[20%] text-left font-extrabold bg-gray-100 backdrop-blur-md">
                            <div class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Cronología
                            </div>
                            <div class="absolute right-0 top-1/4 h-1/2 w-[1px] bg-gray-300"></div>
                        </th>
                        <th class="px-6 py-4 w-[80%] text-left bg-gray-100 font-extrabold backdrop-blur-md">
                            Descripción Técnica de la Modificación
                        </th>
                    </tr>
                </thead>
               <tbody class="divide-y divide-gray-100">
                    <tr v-for="(evento, index) in listaHistorial" :key="index" class="hover:bg-color1-50/20 transition-colors">
                        <!-- Columna de Tiempo -->
                        <td class="px-6 py-6 align-top w-[18%] py-0">
                            <div class="sticky top-20">
                                <div class="text-[10px] font-black text-color1-300 uppercase tracking-[0.1em] mb-1 leading-none">Movimiento</div>
                                <div class="text-[11px] font-bold text-gray-700 uppercase">
                                    {{ 
                                        new Date(evento.created_at).getDate().toString().padStart(2, '0') + ' · ' +
                                        new Date(evento.created_at).toLocaleDateString('es-MX', { month: 'short' }).replace('.', '') + ' · ' +
                                        new Date(evento.created_at).getFullYear()
                                    }}
                                </div>
                                <div class="text-[10px] text-gray-400 font-mono flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                    {{ 
                                        new Date(evento.created_at).toLocaleTimeString('es-MX', { 
                                            hour: '2-digit', 
                                            minute: '2-digit', 
                                            second: '2-digit',
                                            hour12: true 
                                        }) 
                                    }}
                                </div>
                            </div>
                        </td>

                        <!-- Columna de Contenido -->
                        <td class="px-6 py-6 w-[82%]">
                            <!-- Badge del Evento (Usando tu estilo favorito) -->
                            <div class="flex items-center gap-3 mb-6">
                                <div :data-tipo="evento.descripcion" 
                                    class="badge-evento flex items-center gap-2 px-4 py-1.5 rounded-lg border text-[11px] font-black uppercase tracking-widest shadow-sm">
                                    <span class="icono-tipo"></span>
                                    {{ evento.descripcion }}
                                </div>
                                <div class="h-px flex-grow bg-gradient-to-r from-gray-200 to-transparent"></div>
                            </div>

                            <!-- Lista de Cambios (Uno debajo del otro, ocupando todo el ancho) -->
                            <div v-if="Object.keys(evento.cambios).length > 0" class="flex flex-col gap-4">
                                <div v-for="(valor, campo) in evento.cambios" :key="campo" 
                                    class="flex flex-col bg-white border-l-4 border-l-color1-400 border-y border-r border-gray-100 rounded-r-xl shadow-sm hover:shadow-md transition-shadow p-4">
                                    
                                    <!-- Título del Campo -->
                                    <div class="text-[11px] font-black text-gray-400 uppercase mb-3 flex justify-between items-center">
                                        <span class="text-color1-700 tracking-tight">{{ etiquetasCampos[campo] || campo }}</span>
                                        <span class="bg-color4-50 text-color4-600 px-2 py-0.5 rounded-md text-[8px]">Campo modificado</span>
                                    </div>

                                    <!-- Fila de comparación horizontal (Anterior -> Nuevo) -->
                                    <div class="flex items-center gap-4 bg-gray-50/50 p-3 rounded-lg border border-gray-50">
                                        
                                        <!-- Valor Anterior -->
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[8px] font-bold text-gray-400 uppercase mb-1">Anterior</p>
                                            <p class="text-[13px] text-gray-500 italic break-words">
                                                {{ valor.anterior || 'Nulo' }}
                                            </p>
                                        </div>
                                        
                                        <!-- Flecha Divisora con círculo -->
                                        <div class="flex-shrink-0 flex items-center justify-center bg-white w-8 h-8 rounded-full shadow-sm border border-gray-100">
                                            <svg class="w-4 h-4 text-color1-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                            </svg>
                                        </div>

                                        <!-- Valor Nuevo -->
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[8px] font-bold text-color1-600 uppercase mb-1">Nuevo</p>
                                            <p class="text-[13px] text-color1-800 font-bold break-words">
                                                {{ valor.nuevo }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="!loadingHistorial" class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-between items-center text-[11px] text-slate-500">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-color1-600"></div>
                    <span>La presente relación de movimientos refleja fielmente el historial de cambios registrados en la base de datos oficial.</span>
                </div>
                <span class="font-bold text-slate-400">MOD-{{ propiedadSeleccionada?.id }}</span>
            </div>
        </div>
    </div>
</template>