

<script setup>
import { ref, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3'; // O import axios from 'axios';
import Swal from 'sweetalert2';

import CalendarUp from '@/Components/UI/Icons/CalendarUp.vue';
import SolicitudesMesChart from './Componentes/SolicitudesMesChart.vue';
import AtencionCiudadanaChart from './Componentes/AtencionCiudadanaChart.vue';
import RecursosPublicosChart from './Componentes/RecursosPublicosChart.vue';

// PROPS (Métrica enviada al dar clic en la card del Dashboard)
const props = defineProps({
  estadisticaSeleccionada: { type: String },
  solicitudesPorPeriodo: {
        type: [Array, Object],
        default: () => []
    },
  initialParams: { type: Object, default: () => ({}) },
  fechaInicioQuery: String,
  fechaFinQuery: String,
  kpisAtencionCiudadana: Object,
  kpisSolicitudes: Object,
  promedioDineroPersona: [String, Number],
  kpisRecursosPublicos: Object,
  distribucionDiasSemana: Array,
  rawDataFromBackend: [Array, Object],
  rawDataLocalidades: [Array, Object],
  rawDataEstatus: [Array, Object],
  rawDataAreas: [Array, Object],
  periodoActual: Object,
  idTipoApoyo: [String, Number]
});


const rangoFechasQuery = ref(
  props.fechaInicioQuery && props.fechaFinQuery 
    ? [props.fechaInicioQuery, props.fechaFinQuery] 
    : []
);

const periodoActual = ref(props.periodoActual || {})
const fechaIngresoInicioQuery = ref('')
const fechaIngresoFinQuery = ref('')

// ESTADOS REACTIVOS
const cargando = ref(false);
const estadisticaSeleccionada = ref(props.estadisticaSeleccionada || '');
const cantidadMeses = ref(8); // Por defecto 8 meses
const limiteRegistros = ref(5);
const CustomCalendarUp = CalendarUp; 

// Datos recibidos desde la API / Backend mapeados desde las props
const rawDataFromBackend = ref(props.solicitudesPorPeriodo || []);

// ATAJOS ELEMENT PLUS
const shortcuts = computed(() => {
        // Definimos todos los shortcuts disponibles base
        const todosLosShortcuts = [
            {
                id: 2,
                text: `Año Calendario Actual (${new Date().getFullYear()})`,
                value: () => {
                    const currentYear = new Date().getFullYear()

                    fechaIngresoInicioQuery.value = `${currentYear}-01-01`
                    fechaIngresoFinQuery.value = `${currentYear}-12-31`

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
                    end.setDate(end.getDate() - 1);

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
                    const diffToMonday = now.getDate() - day + (day === 0 ? -6 : 1);
                    
                    const start = new Date(now.setDate(diffToMonday));
                    start.setHours(0, 0, 0, 0);
                    
                    const end = new Date(start);
                    end.setDate(start.getDate() + 6);
                    end.setHours(0, 0, 0, 0);

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
                    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);

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
                    const end = new Date(now.getFullYear(), now.getMonth(), 0);

                    const formatDateStr = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                    fechaIngresoInicioQuery.value = formatDateStr(start);
                    fechaIngresoFinQuery.value = formatDateStr(end);

                    return [start, end];
                }
            }
        ];

        // Si la estadística seleccionada es 'solicitudes_mes', filtramos y ocultamos los IDs del 6 al 9 (semanas y meses)
        if (estadisticaSeleccionada.value === 'solicitudes_mes') {
            return todosLosShortcuts.filter(sc => sc.id < 6);
        }

        return todosLosShortcuts;
    })

    /**
     * Valida que el rango de fechas seleccionado cumpla con los requisitos mínimos para el análisis.
     * @returns {boolean} True si el rango es válido, False si no cumple.
     */
    const fechaAnteriorValida = ref([]);

    const validarRangoFechas = () => {
    if (!rangoFechasQuery.value || rangoFechasQuery.value.length < 2) {
        return true; 
    }

    const [inicioStr, finStr] = rangoFechasQuery.value;
    const fechaInicio = new Date(inicioStr + 'T00:00:00');
    const fechaFin = new Date(finStr + 'T00:00:00');
    
    // Calcular la cantidad exacta de meses entre ambas fechas
    const añosDiferencia = fechaFin.getFullYear() - fechaInicio.getFullYear();
    const mesesDiferencia = fechaFin.getMonth() - fechaInicio.getMonth();
    const totalMeses = (añosDiferencia * 12) + mesesDiferencia + 1; 

    const MINIMO_MESES = 3;

    if (totalMeses < MINIMO_MESES) {
        Swal.fire({
        icon: 'warning',
        title: 'Rango insuficiente',
        text: `El periodo seleccionado abarca ${totalMeses} ${totalMeses === 1 ? 'mes' : 'meses'}. Para una correcta evolución histórica, se requiere un mínimo de ${MINIMO_MESES} meses.`,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#800020',
        customClass: {
            popup: 'rounded-xl shadow-lg',
        }
        });
        
        // REVERSIÓN: Si no cumple, regresamos el el-date-picker al último rango válido conocido
        rangoFechasQuery.value = [...fechaAnteriorValida.value];
        
        return false;
    }

    // ÉXITO: Si pasa la validación, actualizamos el respaldo con este nuevo rango válido
    fechaAnteriorValida.value = [...rangoFechasQuery.value];
    return true;
    };

    // Función de normalización
    // const normalizarRangoMensual = () => {
    // if (!rangoFechasQuery.value || rangoFechasQuery.value.length < 2) return;

    // const [inicioStr, finStr] = rangoFechasQuery.value;
    
    // const fechaInicio = new Date(inicioStr + 'T00:00:00');
    // const fechaFin = new Date(finStr + 'T00:00:00');

    // const primerDiaMes = new Date(fechaInicio.getFullYear(), fechaInicio.getMonth(), 1);
    // const ultimoDiaMes = new Date(fechaFin.getFullYear(), fechaFin.getMonth() + 1, 0);

    // const formatDate = (date) => {
    //     const y = date.getFullYear();
    //     const m = String(date.getMonth() + 1).padStart(2, '0');
    //     const d = String(date.getDate()).padStart(2, '0');
    //     return `${y}-${m}-${d}`;
    // };

    // rangoFechasQuery.value = [formatDate(primerDiaMes), formatDate(ultimoDiaMes)];
    // };

    const alCambiarFecha = (val) => {
        // 1. Extraemos las fechas del arreglo que emite el el-date-picker (val)
        if (val && val.length === 2) {
            rangoFechasQuery.value = [val[0],val[1]];
        } else {
            rangoFechasQuery.value = ['',''];
        }

        // 3. Ejecutas tu petición al backend con los nuevos valores actualizados
        fetchDataBackend();
    };
    
    const ocultarInitialLoader = () => {
      document.getElementById('initial-loader')?.remove();
  };

// 5. LLAMADAS AL BACKEND (CONEXIÓN CON RUTAS DE LARAVEL)
const fetchDataBackend = () => {

  cargando.value = true;
  const payload = {
    estadisticaSeleccionada: estadisticaSeleccionada.value,
    months: cantidadMeses.value,
    limit: limiteRegistros.value,
    // dates: rangoFechasQuery.value,
    fechaInicioQuery: rangoFechasQuery.value ? rangoFechasQuery.value[0] : null,
    fechaFinQuery: rangoFechasQuery.value ? rangoFechasQuery.value[1] : null,
    idTipoApoyo: tipoApoyoSeleccionado.value ? tipoApoyoSeleccionado.value : null
  };

  router.get(route('estadisticas'), payload, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      rawDataFromBackend.value = page.props.solicitudes || [];
      cargando.value = false;
      ocultarInitialLoader();
    },
    onError: () => { 
      cargando.value = false;
      ocultarInitialLoader();
     },
    onFinish: () => {
          cargando.value = false;
          ocultarInitialLoader();
      }
  });
};

const alCambiarEstadistica = () => {
  fetchDataBackend();
};

onMounted(() => {
  ocultarInitialLoader();
});

const tipoApoyoSeleccionado = ref(null)
const emit = defineEmits(['change'])

const seleccionar = (valor) => {
  tipoApoyoSeleccionado.value = valor
  fetchDataBackend();
  emit('change', valor)
}
</script>

<template>
  <div class="py-4 px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
    <!-- 1. Cabecera y Parámetros Globales -->
    <div>
      <h1 class="text-lg sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white leading-tight">
        Análisis de Estadísticas
      </h1>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
        Selecciona un indicador y ajusta sus parámetros de visualización en tiempo real.
      </p>
    </div>
      
    <!-- Controles Principales: Forzados a 1 columna en celular y rejilla en pantallas grandes -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-row gap-3 lg:items-end w-full min-w-0">
         <!-- Rango de Fechas Global (Element Plus) -> Ocupa el resto del espacio -->
      <div class="space-y-1 w-full min-w-0 lg:w-[265px] lg:shrink-0">
        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
          Periodo
        </label>
        <el-date-picker
          v-model="rangoFechasQuery"
          type="daterange"
          unlink-panels
          range-separator="-"
          start-placeholder="Inicio"
          end-placeholder="Fin"
          :shortcuts="shortcuts"
          class="custom-date-picker !w-full w-full"
          value-format="YYYY-MM-DD"
          format="DD/MM/YYYY"
          @change="alCambiarFecha"
          :prefix-icon="CustomCalendarUp"
          :clearable="false"
          style="border-radius: 16px!important; width: 100% !important;"
          popper-class="custom-date-range-popper"
        />
      </div>
      <!-- Selector Principal de Estadística (Ancho controlado en PC con lg:max-w-md o lg:w-80) -->
      <div class="space-y-1 w-full min-w-0 lg:w-[350px] lg:shrink-0">
        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
          Estadística a Analizar
        </label>
        <el-select
          v-model="estadisticaSeleccionada"
          class="custom-select-gray !w-full w-full"
          placeholder="Selecciona una estadística"
          @change="alCambiarEstadistica">
          <template #prefix>
            <div class="hidden sm:flex items-center ml-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2 text-gray-700 dark:text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M3 3v18h18" />
                <path d="M7 14l4 -4l4 4l5 -5" />
              </svg>
            </div>
          </template>

          <el-option label="Solicitudes" value="solicitudes_mes">
            <div class="flex items-center"><span class="w-2 h-5 mr-2"></span><span class="text-xs">Solicitudes</span></div>
          </el-option>
           <el-option label="Atención Ciudadana" value="atencion_ciudadana">
            <div class="flex items-center"><span class="w-2 h-5 mr-2"></span><span class="text-xs">Atención Ciudadana</span></div>
          </el-option>
          <el-option label="Recursos Públicos" value="recursos_publicos">
            <div class="flex items-center"><span class="w-2 h-5 mr-2"></span><span class="text-xs">Recursos Públicos</span></div>
          </el-option>
          <el-option label="Top Localidades" value="top_localidades">
            <div class="flex items-center"><span class="w-2 h-5 mr-2"></span><span class="text-xs">Top Localidades</span></div>
          </el-option>
          <el-option label="Solicitudes por Área" value="solicitudes_area">
            <div class="flex items-center"><span class="w-2 h-5 mr-2"></span><span class="text-xs">Solicitudes por Área</span></div>
          </el-option>
          <el-option label="Tiempo Promedio de Entrega" value="tiempo_entrega">
            <div class="flex items-center"><span class="w-2 h-5 mr-2"></span><span class="text-xs">Tiempo Promedio de Entrega</span></div>
          </el-option>
      
        </el-select>
      </div>

   
      <!-- Tipo de apoyo -->
      <div v-if="estadisticaSeleccionada === 'recursos_publicos'"  class="space-y-1.5 w-full min-w-0 sm:col-span-2 lg:col-span-1 lg:w-auto lg:shrink-0 font-figtree">
        <!-- Label Ejecutivo -->
        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
          Tipo de apoyo
        </label>
        <!-- Contenedor principal -->
        <div class="flex flex-col sm:flex-row w-full sm:w-fit p-1 bg-gray-50 dark:bg-gray-800 border border-gray-300/80 dark:border-gray-700/80 rounded-2xl gap-1">
          <!-- Botón: Todos -->
          <button
            type="button"
            @click="seleccionar(null)"
            :class="[
              'flex w-full sm:w-auto sm:flex-none items-center justify-center gap-2 px-3 py-1 rounded-full transition-all duration-200 ease-out',
              'cursor-pointer select-none group whitespace-nowrap',
              'active:scale-[0.97]',
              'focus:outline-none focus-visible:ring-2 focus-visible:ring-[#8f0030]/40 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800',
              tipoApoyoSeleccionado === null
                ? 'bg-color1 text-[14px] text-white font-semibold shadow-[0_1px_3px_rgba(0,0,0,0.08)] ring-1 ring-black/[0.04] dark:bg-gray-900 dark:text-white dark:ring-white/[0.06]'
                : 'text-gray-600 text-[13px] font-medium opacity-60 hover:opacity-100 hover:text-color1-600 hover:bg-white/70 dark:text-gray-400 dark:hover:bg-gray-700/70 dark:hover:text-gray-100'
            ]">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
              :class="tipoApoyoSeleccionado === null ? 'text-white dark:text-white' : 'opacity-50'">
              <circle cx="17.5" cy="17.5" r="3.5"/>
              <circle cx="17.5" cy="6.5" r="3.5"/>
              <circle cx="6.5" cy="17.5" r="3.5"/>
              <circle cx="6.5" cy="6.5" r="3.5"/>
            </svg>
            <span>Todos</span>
          </button>

          <!-- Botón: Efectivo -->
          <button
            type="button"
            @click="seleccionar(1)"
            :class="[
              'flex w-full sm:w-auto sm:flex-none items-center justify-center gap-2 px-3 py-1 rounded-full transition-all duration-200 ease-out',
              'cursor-pointer select-none group whitespace-nowrap',
              'active:scale-[0.97]',
              'focus:outline-none focus-visible:ring-2 focus-visible:ring-[#8f0030]/40 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800',
              tipoApoyoSeleccionado === 1
                ? 'bg-color2 text-[14px] text-white font-semibold shadow-[0_1px_3px_rgba(0,0,0,0.08)] ring-1 ring-black/[0.04] dark:bg-gray-900 dark:text-white dark:ring-white/[0.06]'
                : 'text-gray-600 text-[13px] font-medium opacity-60 hover:opacity-100 hover:text-color2-600 hover:bg-white/70 dark:text-gray-400 dark:hover:bg-gray-700/70 dark:hover:text-gray-100'
            ]">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
              :class="tipoApoyoSeleccionado === 1 ? 'text-white dark:text-white' : 'opacity-50'"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round">
              <rect width="20" height="12" x="2" y="6" rx="2"/>
              <circle cx="12" cy="12" r="2"/>
              <path d="M6 12h.01M18 12h.01"/>
            </svg>
            <span>Efectivo</span>
          </button>

          <!-- Botón: En especie -->
          <button
            type="button"
            @click="seleccionar(2)"
            :class="[
              'flex w-full sm:w-auto sm:flex-none items-center justify-center gap-2 px-3 py-1 rounded-full transition-all duration-200 ease-out',
              'cursor-pointer select-none group whitespace-nowrap',
              'active:scale-[0.97]',
              'focus:outline-none focus-visible:ring-2 focus-visible:ring-[#8f0030]/40 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800',
              tipoApoyoSeleccionado === 2
                ? 'bg-color4-700 text-[14px] text-white font-semibold shadow-[0_1px_3px_rgba(0,0,0,0.08)] ring-1 ring-black/[0.04] dark:bg-gray-900 dark:text-white dark:ring-white/[0.06]'
                : 'text-gray-600 text-[13px] font-medium opacity-60 hover:opacity-100 hover:text-color4-600 hover:bg-white/70 dark:text-gray-400 dark:hover:bg-gray-700/70 dark:hover:text-gray-100'
            ]">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-3.5 h-3.5 shrink-0 transition-transform duration-200 group-hover:scale-110"
              :class="tipoApoyoSeleccionado === 2 ? 'text-white dark:text-white' : 'opacity-70'"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round">
              <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
              <path d="m3.3 7 8.7 5 8.7-5"/>
              <path d="M12 22V12"/>
            </svg>
            <span>En especie</span>
          </button>

        </div>
      </div>
    </div>

    <!-- 3. ÁREA DE VISUALIZACIÓN / GRÁFICA -->
    <div class="bg-white dark:bg-gray-800 py-2 px-6 rounded-xl border border-gray-200/80 dark:border-gray-700 shadow-xs space-y-4">
      <div v-if="!estadisticaSeleccionada" class="py-12 sm:py-16 text-center space-y-3">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-color1-40 text-color1-600 dark:bg-color1-950/40 dark:text-color1-400 mb-1">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M3 3v18h18" />
            <path d="M7 14l4 -4l4 4l5 -5" />
          </svg>
        </div>
        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">
          Comienza seleccionando una estadística
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto px-4">
          Utiliza el selector superior para elegir el indicador analítico que deseas visualizar.
        </p>
      </div>
      <div v-else>
        <div>
          <!-- 1. Si selecciona Solicitudes por Mes, carga el componente hijo -->
          <SolicitudesMesChart 
            v-if="estadisticaSeleccionada === 'solicitudes_mes'"
            :rawDataFromBackend="rawDataFromBackend"
            :rangoFechasQuery="rangoFechasQuery"
            :periodoActual="periodoActual"
            :kpisSolicitudes="kpisSolicitudes"
            :cargando="cargando"
          />
          <AtencionCiudadanaChart 
            v-else-if="estadisticaSeleccionada === 'atencion_ciudadana'"
            :kpisAtencionCiudadana="kpisAtencionCiudadana"
            :distribucionDiasSemana="distribucionDiasSemana"
            :rawDataFromBackend="props.rawDataFromBackend"
            :rangoFechasQuery="rangoFechasQuery"
            :cargando="cargando"
          />
          <RecursosPublicosChart 
            v-else-if ="estadisticaSeleccionada === 'recursos_publicos'"
            :rawDataFromBackend="rawDataFromBackend"
            :rawDataLocalidades="rawDataLocalidades"
            :rawDataAreas="rawDataAreas"
            :rawDataEstatus="rawDataEstatus"
            :rangoFechasQuery="rangoFechasQuery"
            :periodoActual="periodoActual"
            :promedioDineroPersona="promedioDineroPersona"
            :kpisRecursosPublicos="kpisRecursosPublicos"
            :idTipoApoyo="tipoApoyoSeleccionado"
            :cargando="cargando"
          />
          <!-- 2. Espacios reservados para las demás estadísticas -->
          <div v-else class="bg-white dark:bg-gray-800 p-12 text-center rounded-xl border border-gray-200 dark:border-gray-700 text-gray-400 text-xs">
            [ Módulo de gráfica para <span class="font-bold uppercase">{{ estadisticaSeleccionada }}</span> en desarrollo ]
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style>
/* ==========================================================================
   CONFIGURACIÓN DEFINITIVA PARA EL POPPER DEL RANGO DE FECHAS (SIDEBAR + CALENDARIO)
   ========================================================================== */
.custom-date-picker.el-input__wrapper,
.custom-date-picker .el-input__wrapper {
  border-radius: 16px !important;
  box-shadow: 0 0 0 1px #d1d5db inset !important; /* <-- cambia aquí el color del borde */
}

.custom-date-picker.el-input__wrapper:hover,
.custom-date-picker .el-input__wrapper:hover {
  box-shadow: 0 0 0 1px #d1d5db inset !important; /* <-- color hover */
}

.custom-date-picker.el-input__wrapper.is-focus,
.custom-date-picker.el-input__wrapper.is-active,
.custom-date-picker .el-input__wrapper.is-focus,
.custom-date-picker .el-input__wrapper.is-active {
  box-shadow: 0 0 0 1.5px #9ca3af inset !important; /* <-- color al enfocar / abrir */
}

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

/* Sobreescritura elegante para Element Plus Radio Group */
.gov-segmented-control {
  display: inline-flex;
  padding: 3px;
  background-color: #f1f5f9; /* Slate 100 sutil */
  border: 1px solid #e2e8f0;  /* Slate 200 */
  border-radius: 0.5rem;      /* rounded-lg */
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.03);
}

/* Modo oscuro soporte */
.dark .gov-segmented-control {
  background-color: #1e293b; /* Slate 800 */
  border-color: #334155;      /* Slate 700 */
}

/* Reset de botones de Element Plus */
.gov-segmented-control :deep(.el-radio-button__inner) {
  border: none !important;
  background: transparent !important;
  color: #64748b; /* Slate 500 */
  font-size: 0.75rem; /* text-xs */
  font-weight: 600;
  padding: 6px 14px;
  border-radius: 0.375rem !important; /* rounded-md interior */
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: none !important;
}

/* Estado Hover (No activo) */
.gov-segmented-control :deep(.el-radio-button:not(.is-active) .el-radio-button__inner:hover) {
  color: #1e293b; /* Slate 800 */
  background-color: rgba(255, 255, 255, 0.5) !important;
}

.dark .gov-segmented-control :deep(.el-radio-button:not(.is-active) .el-radio-button__inner:hover) {
  color: #f8fafc;
  background-color: rgba(255, 255, 255, 0.05) !important;
}

/* ESTADO ACTIVO: Eleva la opción seleccionada con sombra flotante neutra y detalle Guinda */
.gov-segmented-control :deep(.el-radio-button.is-active .el-radio-button__inner) {
  background-color: #ffffff !important;
  color: #7a1c30 !important; /* Guinda institucional sobrio */
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px -1px rgba(0, 0, 0, 0.08) !important;
  font-weight: 700;
}

/* Estado activo en Modo Oscuro */
.dark .gov-segmented-control :deep(.el-radio-button.is-active .el-radio-button__inner) {
  background-color: #0f172a !important; /* Slate 900 */
  color: #f1f5f9 !important;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3) !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
}
</style>