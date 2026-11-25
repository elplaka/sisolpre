<template>
  <div class="bg-white dark:bg-gray-800 px-3 pt-3 pb-0 m-0 flex flex-col justify-between h-full min-h-[130px]">

    <!-- 1. Cabecera -->
    <div class="flex items-center justify-between gap-2 mb-0 px-1">
      <span
        class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 truncate transition-colors duration-300"
        title="Calculado sobre solicitudes concluidas con fecha de resolución">
        Tiempo Promedio de Entrega de Apoyos
      </span>

      <!-- Icono de Reloj / Cronómetro -->
      <div
        class="p-1.5 shrink-0 bg-emerald-50 dark:bg-color1-950/50 text-emerald-600 dark:text-color1-400 rounded-lg"
        title="Métrica de tiempo de resolución">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
    </div>

    <!-- 2. Cuerpo Principal (KPI Destacado) - SOLO SI HAY DATOS -->
    <div v-if="tieneDatos" class="flex flex-col justify-center my-auto py-2 px-1">
      <div class="flex items-baseline gap-2">
        <!-- Número de Días Grande -->
        <span class="text-4xl sm:text-[2.1rem] font-extrabold text-gray-800 dark:text-gray-100 tracking-tight tabular-nums">
          {{ panelData.promedioDias ?? 0 }}
        </span>

        <!-- Unidad alineada a la base del número -->
        <div class="flex flex-col pb-1">
          <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            {{ (panelData.promedioDias ?? 0) === 1 ? 'Día' : 'Días' }}
          </span>
        </div>
      </div>

      <!-- Badge de Diagnóstico de Eficiencia -->
      <div class="mt-3">
        <span
          title="Criterios: ≤ 5 días Respuesta Rápida · ≤ 15 días En Tiempo · > 15 días Requiere Atención"
          :class="[
            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[0.65rem] font-bold cursor-help',
            (panelData.promedioDias <= 5)
              ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/50'
              : (panelData.promedioDias <= 15)
                ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/50'
                : 'bg-red-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/50'
          ]">
          <span
            class="h-1.5 w-1.5 rounded-full"
            :class="[
              (panelData.promedioDias <= 5)
                ? 'bg-emerald-500'
                : (panelData.promedioDias <= 15)
                  ? 'bg-amber-500'
                  : 'bg-red-500'
            ]"></span>
          {{
            (panelData.promedioDias <= 5)
              ? 'Respuesta Rápida'
              : (panelData.promedioDias <= 15)
                ? 'En Tiempo'
                : 'Requiere Atención'
          }}
        </span>
      </div>
    </div>

    <!-- 🌟 ESTADO VACÍO MEJORADO (UX Amigable) -->
    <div v-else class="flex flex-col items-center justify-center my-auto py-4 text-center px-2">
      <div class="p-2 bg-gray-50 dark:bg-gray-900/50 text-gray-400 dark:text-gray-500 rounded-full mb-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
      </div>
      <p class="text-xs text-gray-500 italic dark:text-gray-400">Sin registros en este periodo</p>
      <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">
        No hay apoyos concluidos en el rango seleccionado.
      </span>
    </div>

    <!-- 3. Pie de Card (Contexto) - SOLO SI HAY DATOS -->
    <div
      v-if="tieneDatos"
      class="pt-3 border-t border-gray-100 mt-2 dark:border-gray-700/60 flex items-center justify-between gap-2 text-[11px] text-gray-500 dark:text-gray-400 px-1">
      <span class="flex items-center gap-1 flex-wrap">
        <span class="text-gray-500 dark:text-gray-300">Máximo:</span>
        <strong class="text-gray-700 dark:text-rose-400 font-bold">
          {{ panelData?.maxDias || 0 }} {{ (panelData?.maxDias || 0) === 1 ? 'día' : 'días' }}
        </strong>
        <span v-if="panelData?.maxSolicitudFolio" class="text-gray-400">
          <template v-if="panelData.totalEmpatados > 1">
            ({{ panelData.totalEmpatados }} solicitudes)
          </template>
          <span v-else>
            (Folio: {{ folioFormateado }})
          </span>
        </span>
      </span>
    <span
        v-if="panelData?.totalConcluidas"
        class="inline-flex items-center gap-1.5 text-[10px] text-gray-500 dark:text-gray-400 tabular-nums shrink-0"
        title="Total de solicitudes concluidas consideradas">
          <span
            class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400 shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                    <path d="M11 12h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 14"></path>
                    <path d="m7 18 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"></path>
                    <path d="m2 13 6 6"></path>
                </svg>
          </span>
          <span>
            {{ panelData.totalConcluidas }} {{ panelData.totalConcluidas === 1 ? 'entrega' : 'entregas' }}
          </span>
      </span>
    </div>

    <!-- Pie de Card Ficticio para mantener la misma altura cuando esté vacío -->
    <div v-else class="pt-3 border-t border-gray-100 dark:border-gray-700/60 text-[0.65rem] text-transparent select-none">
      Placeholder
    </div>

  </div>
</template>

<script setup>
import { defineProps, computed } from 'vue';

const props = defineProps({
  panelData: {
    type: Object,
    default: () => ({ promedio_dias: 0, total_concluidas: 0 })
  },
  userAuth: Object
});

defineEmits(['ver-folio']);

const tieneDatos = computed(
  () => Boolean(props.panelData && (props.panelData.promedioDias > 0 || props.panelData.maxDias > 0))
);

const folioFormateado = computed(() => {
  if (!props.panelData?.maxSolicitudFolio) return '';
  return String(props.panelData.maxSolicitudFolio).slice(-4).padStart(4, '0');
});
</script>