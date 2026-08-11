<script setup>
    import { ref, computed, onMounted, watch, nextTick } from 'vue';
    import { router } from '@inertiajs/vue3'
    import { debounce } from 'lodash'; // Necesitas instalar lodash o importarlo si está globalmente disponible
    import FormularioConstanciaNumeroOficial from './FormularioConstanciaNumeroOficial.vue';
    import Pagination from '@/Components/Pagination.vue'


    const rangoFechasQuery = ref([]);
    const fechaInicioQuery = ref('');
    const fechaFinQuery = ref('');
    const estatusQuery = ref([]);

    const selectEstatusRef = ref(null);
    
    const isLoading = ref(false);
    const lateralAbierto = ref(false);

    const props = defineProps({
        tramites: Object,
        pagination: Object,
        todosEstatus: Object
    });

    const formatDate = (dateString) => {
        // 1. Validación básica
        if (!dateString) return 'N/A';

        // 2. Limpieza de formato ISO o Timestamp
        // Usamos un split que corte ya sea por espacio " " o por la "T" de ISO 8601
        const dateOnly = dateString.includes('T') 
            ? dateString.split('T')[0] 
            : dateString.split(' ')[0];

        // 3. Verificación de seguridad
        if (!dateOnly || !dateOnly.includes('-')) return 'N/A';

        // 4. Proceso de formateo (YYYY-MM-DD)
        const [year, month, day] = dateOnly.split('-');

        // 5. Retorno limpio
        return `${day}/${month}/${year}`;
    }

    // Procesa solo el nombre de la colonia con tus reglas
    const formatColonia = (nombre) => {
        if (!nombre) return '';
        const nom = nombre.trim().toUpperCase();
        // Si ya empieza con COL o INF, lo dejamos tal cual, si no, agregamos COL.
        return (nom.startsWith('COL') || nom.startsWith('INF')) ? nom : `COL. ${nom}`;
    };

    const getStatusStyle = (color) => {
        if (!color) return { backgroundColor: '#f9fafb', color: '#6b7280', borderColor: '#e5e7eb' };
        
        // Si tus colores son nombres (violet, tomato), el navegador los entiende.
        // Pero para el fondo, usamos transparencia.
        return {
            color: color,
            borderColor: color,
            backgroundColor: 'transparent', // O puedes usar un color fijo muy tenue
            borderWidth: '1px',
            fontSize: '11px',
            fontWeight: '700',
            letterSpacing: '0.05em',
            textTransform: 'uppercase',
            boxShadow: `inset 0 0 0 1px ${color}20` // Un brillo interno sutil
        };
    };
    // Estados
    const isDrawerOpen = ref(false);
    const selectedTramite = ref(null);
    const documentoData = ref(null)

    // Funciones
    const openDetails = (tram) => {
        isLoading.value = true;
        selectedTramite.value = tram;
        isDrawerOpen.value = true;
        // Evitar que la página principal haga scroll cuando el drawer está abierto
        document.body.style.overflow = 'hidden';
        isLoading.value = false;
    };

    const closeDrawer = () => {
        isDrawerOpen.value = false;
        document.body.style.overflow = 'auto';
    };

    const menuAbierto = ref(null); // Guardamos el ID del trámite con menú abierto

    const toggleMenu = (id) => {
        if (menuAbierto.value === id) {
            menuAbierto.value = null; // Si ya está abierto, lo cerramos
        } else {
            menuAbierto.value = id;   // Abrimos el de este trámite
        }
    };

    // Cerrar si hacen clic afuera
    window.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) menuAbierto.value = null;
    });

    const direccionFormateada = computed(() => {
        const p = selectedTramite.value?.propiedad;
        if (!p) return "";

        let partes = [];

        // 1. Calle y Número
        let calleNum = p.calle || "";
        if (p.numero) {
            calleNum += ` N° ${p.numero}`;
        }
        partes.push(calleNum);

        // 2. Colonia con validación de prefijos
        if (p.colonia?.nombre) {
            const nombreCol = p.colonia.nombre.trim().toUpperCase();
            // Si NO empieza con COL o INF, añadimos el prefijo COL.
            if (!nombreCol.startsWith("COL") && !nombreCol.startsWith("INF")) {
            partes.push(`COL. ${p.colonia.nombre}`);
            } else {
            partes.push(p.colonia.nombre);
            }
        }
        
        if (p.codigo_postal){
            partes.push(`C.P. ${p.codigo_postal}`)
        }

        // 3. Localidad
        if (p.localidad?.nombre) {
            partes.push(p.localidad.nombre);
        }

        // Unimos todo con comas para que se vea ordenado
        return partes.filter(part => part !== "").join(", ");
    });

    const modalConstanciaNumeroOficialAbierto = ref(false);
    

    const editTramite = (tram) => {
        selectedTramite.value = tram;
        lateralAbierto.value = false;
        // selectedTramite.value = JSON.parse(JSON.stringify(tram));

        isLoading.value = true;
        if (tram.id_tramite == 4)
        {
            modalConstanciaNumeroOficialAbierto.value = true;
        }

        isLoading.value = false;

        documentoData.value = null

        if (tram.documento_generado)
        {
            documentoData.value = tram.documento_generado
        }
    };

    const destinatarios = computed(() => {
        if (!selectedTramite.value) return [];

        const tram = selectedTramite.value;

        // Función auxiliar para determinar género basándose en CURP
        const obtenerGenero = (curp, tipoBase) => {
            if (!curp || curp.length < 10) return tipoBase; // Si no hay CURP, devolvemos el tipo original
            const letraGenero = curp.charAt(10).toUpperCase(); // Posición 10 de la CURP

            if (tipoBase === 'SOLICITANTE') {
                return letraGenero === 'M' ? 'SOLICITANTE' : 'SOLICITANTE'; // Usualmente se deja neutro o SOLICITANTE/A
            }
            
            if (tipoBase === 'PROPIETARIO') {
                return letraGenero === 'M' ? 'PROPIETARIA' : 'PROPIETARIO';
            }
            
            return tipoBase;
        };

        // 1. Creamos la lista base con los datos
        const listaBase = [
            { 
                tipo: obtenerGenero(tram.solicitud?.contacto?.persona?.curp, 'SOLICITANTE'), 
                nombre: `${tram.solicitud?.contacto?.persona?.nombre || ''} ${tram.solicitud?.contacto?.persona?.apellidos || ''}`.trim() 
            },
            { 
                tipo: obtenerGenero(tram.propiedad?.contacto?.persona?.curp, 'PROPIETARIO'), 
                nombre: `${tram.propiedad?.contacto?.persona?.nombre || ''} ${tram.propiedad?.contacto?.persona?.apellidos || ''}`.trim() 
            },
            { 
                tipo: 'RAZÓN SOCIAL', 
                nombre: tram.solicitud?.razon_social?.nombre || '' 
            }
        ].filter(item => item.nombre && item.nombre !== '' && item.nombre !== 'undefined undefined');

        // 2. Quitamos duplicados priorizando PROPIETARIO/A
        const unicos = {};
        
        listaBase.forEach(item => {
            // Incluimos tanto 'PROPIETARIO' como 'PROPIETARIA' en la prioridad de sobreescritura
            const esPropietario = item.tipo === 'PROPIETARIO' || item.tipo === 'PROPIETARIA';
            
            if (!unicos[item.nombre] || esPropietario) {
                unicos[item.nombre] = item;
            }
        });

        return Object.values(unicos);
    });

    const handleFechaChange = (val) => {
        if (val && val.length === 2) {
            // Si hay fechas seleccionadas, las asignamos
            fechaInicioQuery.value = val;
            fechaFinQuery.value = val;
        } else {
            // Si el usuario limpia el selector, reseteamos las variables
            fechaInicioQuery.value = '';
            fechaFinQuery.value = '';
        }
        
        // Aquí puedes llamar a tu función de búsqueda si la tienes
        debounceFetchTramites(); 
    };

    const imprimirTiempo = () => {
        const ahora = new Date();
        
        const min = String(ahora.getMinutes()).padStart(2, '0');
        const seg = String(ahora.getSeconds()).padStart(2, '0');
        // Obtenemos los milisegundos y los recortamos a 2 dígitos para que parezcan "centésimas"
        const ms = String(ahora.getMilliseconds()).padStart(3, '0').slice(0, 2);
    };  

   const fetchTramites = () => {
        if (!rangoFechasQuery.value || rangoFechasQuery.value.length < 2) return;

        // Forzamos la extracción de los strings para evitar que mande el Proxy/Array
        const inicio = String(rangoFechasQuery.value[0]);
        const fin = String(rangoFechasQuery.value[1]);

        const data = {
            fechaInicioQuery: inicio,
            fechaFinQuery: fin,
            estatusQuery: estatusQuery.value
        };

        imprimirTiempo();

        router.post(route('tramites'), data, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => { imprimirTiempo(); }
        });
    };

    const debounceFetchTramites = debounce(() => {
        fetchTramites(); 
    }, 1);

    watch(rangoFechasQuery, (nuevoValor) => {
        if (nuevoValor) 
        {
            debounceFetchTramites();
        }
    }, { deep: true });

    onMounted(() => {
        inicializarFiltros();
    });

    const inicializarFiltros = async () => {
    try {
        // 1. Inicializamos fechas (tu función existente)
        inicializaFechas();

        // 2. Limpiamos el array de estatus
        estatusQuery.value = [];

        // 3. Importante: nextTick asegura que Element Plus procese el cambio
        await nextTick();
        
        // Si el componente tiene valores residuales en el DOM, forzamos un reset
        if (selectEstatusRef.value) {
            selectEstatusRef.value.clearVisibleValue?.(); // Método interno de limpieza visual
        }

    } catch (error) {
        console.error("Error al inicializar filtros de trámites:", error);
    }
};

    function inicializaFechas() {
        const hoy = new Date();
        const hace1Mes = new Date();
        hace1Mes.setDate(hoy.getDate() - 30);

        // Creamos los strings en formato DD-MM-YYYY
        const hoyFormateado = formatoManual(hoy);
        const hace1MesFormateado = formatoManual(hace1Mes);

        // Asignamos los strings, no los objetos Date
        fechaInicioQuery.value = hace1MesFormateado;
        fechaFinQuery.value = hoyFormateado;

        rangoFechasQuery.value = [
            hace1MesFormateado, 
            hoyFormateado
        ];        
    }

    // Función auxiliar para formatear (puedes usar la tuya si ya la tienes)
    function formatoManual(fecha) {
        const d = String(fecha.getDate()).padStart(2, '0');
        const m = String(fecha.getMonth() + 1).padStart(2, '0'); // Enero es 0
        const y = fecha.getFullYear();
        return `${d}-${m}-${y}`;
    }

     const handleEstatusChange = () => {      
        if (selectEstatusRef.value) {
            // En este punto, el watcher ya ha disparado la búsqueda, solo se cierra el menú.
            selectEstatusRef.value.toggleMenu();
            debounceFetchTramites();
        }
    };

    const estatusSeleccionadosParaDisplay = computed(() => {
        if (!estatusQuery.value || estatusQuery.value.length === 0) {
            return [];
        }
        
        return props.todosEstatus.filter(estatus => 
            estatusQuery.value.includes(estatus.id)
        );
    });

    function handlePageChange(page) {
        const inicio = String(rangoFechasQuery.value[0]);
        const fin = String(rangoFechasQuery.value[1]);

        const data = {
            fechaInicioQuery: inicio,
            fechaFinQuery: fin,
            estatusQuery: estatusQuery.value,
            page: page
        };

        imprimirTiempo();

        router.post(route('tramites'), data, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => { imprimirTiempo(); }
        });
    }

</script>
<template>
    <section class="bg-white dark:bg-gray-900 p-3 sm:p-5">
        <div class="mx-auto max-w-screen-xl lg:px-0 w-[100%] sm:w-[100%] md:w-[100%] lg:w-[100%]">
            <div class="flex flex-col md:flex-row items-start md:items-center md:mb-5 md:mr-2 justify-between">
                <h1 class="text-2xl font-bold ml-2">Trámites </h1>
            </div>
            <div v-if="!isLoading" class="relative flex flex-wrap items-center justify-begin gap-2 md:gap-3 mb-2">
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
            </div>
            <div class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg"> 
                <div>                    
                    <table v-if="!isLoading" class="w-full text-sm text-left text-gray-500 dark:text-gray-400 table-fixed border-collapse">
                        <colgroup>
                            <col class="w-[8%]">
                            <col class="w-[9%]">
                            <col class="w-[15%]">
                            <col class="w-[20%]">
                            <col class="w-[6%]">
                            <col class="w-[22%]"> 
                            <col class="w-[5%]">
                            <col class="w-[8%]">
                        </colgroup>
                        <thead class="text-base text-gray-700 bg-white dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-center">Folio Sol.</th>
                                <th scope="col" class="px-4 py-3 text-center">Num. Oficio</th>
                                <th scope="col" class="px-4 py-3">Solicitante</th>
                                <th scope="col" class="px-4 py-3">Trámite</th>
                                <th scope="col" class="px-4 py-3 text-center">Fecha</th>
                                <th scope="col" class="px-4 py-3">Propiedad</th>
                                <th scope="col" class="px-4 py-3">Estatus</th>
                                <th scope="col" class="px-4 py-3"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="tram in tramites" :key="tram.id" 
                                class="text-xs border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <td class="px-4 py-2 text-center truncate" :title="tram.solicitud?.folio">
                                    {{ tram.solicitud?.folio ? String(tram.solicitud.folio).slice(-4).padStart(4, '0') : '0000' }}
                                </td>
                                <td class="px-4 py-2 text-center truncate" :title="tram.solicitud?.folio">
                                    {{ tram.documento_generado?.ano_oficio ? tram.documento_generado.ano_oficio + '/' + String(tram.documento_generado.consecutivo_oficio).slice(-4).padStart(4, '0') : '-' }}
                                </td>
                                <td class="px-4 py-2 truncate" 
                                    :title="`${tram.contacto?.persona?.nombre} ${tram.contacto?.persona?.apellidos}`">
                                    {{ tram.contacto?.persona?.nombre }} {{ tram.contacto?.persona?.apellidos }}
                                </td>
                                <td class="px-4 py-2">
                                    <div class="inline-block px-4 py-2 truncate rounded-lg font-bold leading-none uppercase"
                                        :style="{
                                            background: `linear-gradient(90deg, var(--color1) 0%, #4a0023 100%)`,
                                            color: 'white',
                                            boxShadow: '2px 2px 5px rgba(0,0,0,0.1)'
                                        }">
                                        {{ tram.tipo_tramite?.nombre }}
                                    </div>
                                </td>
                                <td class="px-4 py-2">{{ formatDate(tram.fecha_inicio) }}</td>
                                <td class="px-4 py-2 max-w-0 truncate">
                                    <div class="flex items-start w-full min-w-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1 flex-shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        <span class="leading-tight truncate" :title="tram.propiedad?.clave_catastral">
                                            <span>{{ tram.propiedad?.calle }}</span>
                                            <span v-if="tram.propiedad?.numero"> N° {{ tram.propiedad.numero }}</span>
                                            <span v-else>
                                                <span class="text-color1-800 font-bold" v-if="tram.id_tramite == 4 && (tram.id_estatus == 2 || tram.id_estatus == 99) && tram.constancia_numero_oficial?.propiedad?.numero"> N° {{ tram.constancia_numero_oficial?.propiedad?.numero }}</span>
                                            </span>
                                            <span v-if="tram.propiedad?.colonia" class="text-gray-500 font-normal">
                                                • {{ formatColonia(tram.propiedad.colonia.nombre) }}
                                            </span>
                                            <span v-if="tram.propiedad?.codigo_postal" class="text-gray-500 font-normal">
                                                • C.P. {{ tram.propiedad.codigo_postal }}
                                            </span>
                                        </span>
                                    </div>

                                    <div v-if="tram.propiedad?.localidad" class="flex items-center truncate">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="truncate">
                                            {{ tram.propiedad.localidad.nombre }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span 
                                        class="inline-flex truncate items-center px-2.5 py-1 rounded-lg border"
                                        :style="getStatusStyle(tram.estatus?.color)">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2" :style="{ fill: tram.estatus?.color }" viewBox="0 0 8 8">
                                            <circle cx="5" cy="5" r="3" />
                                        </svg>
                                        {{ tram.estatus?.nombre }}
                                    </span>
                                </td>
                               <td class="px-4 py-2 text-right">
                                    <div class="relative inline-block text-left">
                                        <button @click.stop="toggleMenu(tram.id)" 
                                                class="p-2 hover:bg-gray-100 rounded-full transition-colors color1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                            </svg>
                                        </button>

                                        <div v-if="menuAbierto === tram.id" 
                                            class="absolute right-0 z-50 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-100 ring-1 ring-black ring-opacity-5 shadow-2xl">
                                            <div class="py-1">
                                                <template v-if="tram.documento_generado">
                                                    <button v-if="tram.id_estatus != 99" @click="editTramite(tram); menuAbierto = null" 
                                                    class="group relative w-full flex items-center px-4 py-2.5 text-sm text-gray-700 transition-all duration-300 hover:bg-color1-40 hover:text-color1-700 overflow-hidden"> 
                                                        <div class="absolute left-0 top-0 h-full w-1.5 bg-[var(--color1)] opacity-0 -translate-x-full group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 ease-out rounded-r-full"></div>
                                                        <svg 
                                                        class="mr-3 h-4 w-4 text-color1-500 transition-all duration-300 transform group-hover:scale-110" 
                                                        fill="none" 
                                                        stroke="currentColor" 
                                                        viewBox="0 0 24 24">
                                                        <path 
                                                            stroke-linecap="round" 
                                                            stroke-linejoin="round" 
                                                            stroke-width="2" 
                                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                        </svg>
                                                        <span class="font-bold transition-all duration-300 group-hover:translate-x-1">
                                                            Editar
                                                        </span>
                                                    </button>
                                                    <button v-else @click="editTramite(tram); menuAbierto = null" 
                                                            class="group relative w-full flex items-center px-4 py-3 text-sm text-gray-700 transition-all duration-300 hover:bg-gray-50">
                                                        <div class="absolute left-0 top-0 h-full w-1.5 bg-color1-600 opacity-0 -translate-x-full group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 rounded-r-full"></div>
                                                        <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-color1-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        <span class="font-bold">Ver Expediente</span>
                                                    </button>
                                                </template>
                                                <template v-else>
                                                    <button @click="editTramite(tram); menuAbierto = null" 
                                                        class="group relative w-full flex items-center px-4 py-2.5 text-sm text-gray-700 transition-all duration-300 hover:bg-green-50 hover:text-green-700 overflow-hidden">
                                                        <div class="absolute left-0 top-0 h-full w-1.5 bg-green-500 opacity-0 -translate-x-full group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 ease-out rounded-r-full"></div>
                                                        <svg class="mr-3 h-4 w-4 text-green-500 transition-all duration-300 transform group-hover:scale-110" 
                                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span class="font-bold transition-all duration-300 group-hover:translate-x-1">
                                                            Completar
                                                        </span>
                                                    </button>
                                                </template>
                                               <button v-if="!tram.documento_generado" @click="openDetails(tram); menuAbierto = null" 
                                                class="group relative w-full flex items-center px-4 py-2.5 text-sm text-gray-600 transition-all duration-300 hover:bg-gray-50 hover:text-gray-900 overflow-hidden">
                                                    <div class="absolute left-0 top-0 h-full w-1.5 bg-[var(--color1)] opacity-0 -translate-x-full group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 ease-out rounded-r-full"></div>
                                                    <svg class="mr-3 h-4 w-4 color1 transition-all duration-300 transform group-hover:scale-110 group-hover:translate-x-1" 
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    <span class="transition-all duration-300 group-hover:translate-x-1">
                                                        Ver Detalles
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
                <div v-if="tramites.length === 0 && !isLoading" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No hay trámites registrados
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
    <div v-if="modalConstanciaNumeroOficialAbierto" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50">
        <!-- <div class="bg-white rounded-2xl w-full max-w-4xl overflow-hidden shadow-2xl"> -->
        <div :class="[
            'bg-white rounded-2xl w-full transition-all duration-500 overflow-hidden shadow-2xl',
            lateralAbierto ? 'max-w-[68rem]' : 'max-w-[56rem]' 
        ]">
            <FormularioConstanciaNumeroOficial 
                :initialData="selectedTramite"
                :documentoData="documentoData"
                :destinatarios="destinatarios"
                :todosEstatus="todosEstatus"
                @cancel="modalConstanciaNumeroOficialAbierto = false" 
                @save="modalConstanciaNumeroOficialAbierto = false"
                @toggle-doc="(val) => lateralAbierto = val"
            />
        </div>        
    </div>
    <div v-if="isDrawerOpen" 
        @click="closeDrawer"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[60] transition-opacity">
    </div>

    <aside 
        :class="isDrawerOpen ? 'translate-x-0' : 'translate-x-full'"
        class="fixed top-0 right-0 h-full w-full max-w-md bg-white shadow-2xl z-[70] transform transition-transform duration-300 ease-in-out border-l border-gray-100">
        
        <div class="px-6 py-4 text-white" 
            :style="{ background: `linear-gradient(135deg, var(--color1) 0%, #4a0023 100%)` }">
            <div class="flex justify-between items-start mb-2">
                <button @click="closeDrawer" class="p-1 hover:bg-white/20 rounded-full transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-[10px] uppercase font-bold tracking-widest">
                    Expediente Digital
                </span>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black uppercase tracking-widest opacity-70">
                    Folio de Solicitud
                </span>
                
                <h2 class="text-2xl font-bold leading-none">
                    # {{ String(selectedTramite?.solicitud?.folio ?? '').padStart(4, '0') }}
                </h2>
            </div>
            <p class="text-sm opacity-80 font-bold">{{ selectedTramite?.tipo_tramite?.nombre }}</p>
            <p class="text-md opacity-90 truncate font-bold flex items-center">
                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="truncate">
                    {{ selectedTramite?.contacto?.persona?.nombre }} {{ selectedTramite?.contacto?.persona?.apellidos }}
                </span>
            </p>
        </div>

        <div class="p-6 overflow-y-auto h-[calc(100vh-160px)]">            
            <div class="mb-8 overflow-hidden rounded-2xl border transition-all duration-500"
                :style="{ 
                    borderColor: `${selectedTramite?.estatus?.color}30`, 
                    backgroundColor: `${selectedTramite?.estatus?.color}08` 
                }">
                <div class="flex items-center justify-between p-4">
                    <div class="flex flex-col">
                        <p class="text-[10px] uppercase tracking-[0.15em] text-gray-400 font-black mb-1">
                            Estatus del Trámite
                        </p>
                        <h4 class="text-lg font-bold leading-tight" 
                            :style="{ color: selectedTramite?.estatus?.color }">
                            {{ selectedTramite?.estatus?.nombre }}
                        </h4>
                    </div>
                    
                    <div class="relative flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-20"
                            :style="{ backgroundColor: selectedTramite?.estatus?.color }"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" :style="{ color: selectedTramite?.estatus?.color }">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                
                <div class="h-1.5 w-full bg-gray-100">
                    <div class="h-full transition-all duration-1000 ease-out"
                        :style="{ 
                            width: '100%', 
                            backgroundColor: selectedTramite?.estatus?.color,
                            opacity: 0.6
                        }">
                    </div>
                </div>
            </div>
            <div class="mb-6">
                <div class="flex items-center gap-3">                    
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <svg class="w-4 h-4 text-color1-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="text-sm font-bold text-color1-800 uppercase">
                            Cve. Catastral
                        </h3>
                    </div>

                    <div class="h-4 w-px bg-gray-300"></div>

                    <div class="flex-1">
                        <p class="text-sm font-bold text-gray-800 tracking-wider">
                            {{ selectedTramite?.propiedad?.clave_catastral }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="flex items-center text-sm font-bold mb-2 text-color1-800 uppercase tracking-wider">
                    <svg class="w-4 h-4 mr-2 text-color1-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Ubicación de la Propiedad
                </h3>
               <div class="relative w-full h-40 mb-3 overflow-hidden rounded-xl border border-gray-100 bg-gray-50 flex items-center justify-center">
                    <img v-if="selectedTramite?.propiedad?.img_croquis" 
                        :key="selectedTramite.propiedad.img_croquis"
                        :src="`/storage/croquis/${selectedTramite.propiedad.img_croquis}`"
                        alt="Vista de la propiedad"
                        class="w-full h-full object-cover transition-transform duration-500 hover:scale-110" />

                    <div v-else class="flex flex-col items-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-[10px] uppercase tracking-widest font-medium">Sin vista previa disponible</span>
                    </div>
                </div>
                <div class="space-y-1 bg-gray-50 p-3 rounded-r-lg border-l-4" :style="{ borderColor: 'var(--color1)' }">
                    <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider mb-1 block">
                        Domicilio
                    </span>
                    <p class="text-sm text-gray-800 leading-relaxed">
                        {{ direccionFormateada }}
                    </p>
                </div>
            </div>
        </div>

        <div class="absolute bottom-0 w-full p-4 bg-gray-50 border-t flex gap-2">
            <button v-if="selectedTramite?.id_estatus == 2 || selectedTramite?.id_estatus == 99" class="flex-1 py-2 bg-gray-200 text-gray-700 rounded-lg font-bold text-sm hover:bg-gray-300 transition-colors">
                Descargar PDF
            </button>
            <button @click="isDrawerOpen = false; editTramite(selectedTramite);" class="flex-1 py-2 text-white rounded-full font-bold text-sm hover:opacity-90 transition-opacity"
                    :style="{ background: 'var(--color1)' }">
                Completar Trámite
            </button>
        </div>
    </aside>
</template>