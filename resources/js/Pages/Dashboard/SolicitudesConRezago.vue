<template>
  <div class="bg-white dark:bg-gray-800 px-3 pt-3 pb-0 m-0 flex flex-col justify-between h-full min-h-[130px]">
    
    <!-- 1. Cabecera -->
    <div class="flex items-center justify-between gap-2 mb-0 px-1">
      <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 truncate transition-colors duration-300">
        Solicitudes con Rezago (&gt;15 Días)
      </span>

      <!-- Ícono de Alerta / Rezago con color dinámico -->
      <div
        class="p-1.5 shrink-0 rounded-lg transition-colors duration-300"
        :class="[
          (panelData?.total_rezago === 0)
            ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400'
            : (panelData?.total_rezago <= 5)
              ? 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400'
              : 'bg-red-50 text-red-600 dark:bg-rose-950/50 dark:text-rose-400'
        ]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
    </div>
    <!-- 2. Cuerpo Principal (KPI Destacado) - SOLO SI HAY DATOS -->
    <div v-if="panelData && (panelData.total_rezago > 0 || panelData.total_rezago > 0)" class="flex flex-col justify-center mt-2 mb-auto py-0">
      <div class="flex items-baseline justify-between gap-2">
        <div class="flex items-baseline gap-2">
          <!-- Número Total de Trámites Retrasados con color dinámico -->
          <span 
            class="text-4xl sm:text-[2.1rem] font-extrabold text-gray-800 dark:text-gray-100 tracking-tight transition-colors duration-300">
            {{ panelData.total_rezago ?? 0 }}
          </span>
          
          <div class="flex flex-col">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Folios</span>
          </div>
        </div>

        <!-- Estatus Operativos alineados a la derecha -->
        <div class="ml-auto flex items-center gap-1.5 font-medium tracking-tight flex-wrap justify-end">
          <!-- En Trámite (Ámbar - Reloj de arena) -->
          <span
            class="inline-flex items-center gap-1 px-1.5 py-1 rounded-md text-[10px] font-semibold bg-amber-50 text-amber-500 dark:bg-amber-950/40 dark:text-amber-400 shrink-0"
            title="Solicitudes en trámite con más de 15 días de espera">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-2.5 h-2.5">
              <path d="M5 22h14"></path>
              <path d="M5 2h14"></path>
              <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"></path>
              <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"></path>
            </svg>
            <span>En Trámite</span>
          </span>

          <!-- Aprobadas (Índigo - Check) -->
          <span
            class="inline-flex items-center gap-1 px-1.5 py-1 rounded-md text-[10px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 shrink-0"
            title="Solicitudes aprobadas pendientes de entrega">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span>Aprobadas</span>
          </span>
        </div>
      </div>
      <!-- Badge de Alerta con Semáforo Tricolor -->
      <div class="mt-3">
        <span 
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[0.65rem] font-bold transition-colors duration-300"
          :class="[
            (panelData.total_rezago === 0)
              ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/50'
              : (panelData.total_rezago <= 5)
                ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/50'
                : 'bg-red-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/50'
          ]">
          <span 
            class="h-1.5 w-1.5 rounded-full"
            :class="[
              (panelData.total_rezago === 0)
                ? 'bg-emerald-500'
                : (panelData.total_rezago <= 5)
                  ? 'bg-amber-500 animate-pulse'
                  : 'bg-red-500 animate-pulse'
            ]"></span>
          {{ 
            (panelData.total_rezago === 0)
              ? 'Sin Rezago'
              : (panelData.total_rezago <= 5)
                ? 'Atención Moderada'
                : 'Atención Crítica'
          }}
        </span>
      </div>
      
    </div>

    <!-- 🌟 ESTADO VACÍO (Sin SVG y con mensaje orientado a UX) -->
    <div v-else class="flex flex-col items-center justify-center my-auto py-5 text-center">
      <p class="text-xs italic text-gray-400 dark:text-gray-400">Sin rezagos detectados en este periodo</p>
      <!-- <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Todas las solicitudes operan en tiempo óptimo.</span> -->
    </div>

    <!-- 3. Pie de Card (Contexto) - SOLO SI HAY DATOS -->
    <div v-if="panelData  && (panelData.total_rezago > 0 || panelData.total_rezago > 0)" class="pt-3 border-t border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 sm:gap-2 text-[0.65rem] text-gray-500 dark:text-gray-400">
      <!-- Caso más antiguo -->
      <div class="flex text-gray-400 items-center gap-1 shrink-0">
        <span class="text-gray-600">Caso más antiguo:</span>
          <strong class="text-gray-700 dark:text-gray-200 tabular-nums">{{ panelData.max_dias_espera ?? 0 }} días </strong>
          <template v-if="panelData.total_empatados > 1">
            ({{ panelData.total_empatados }} solicitudes)
          </template>
          <template v-else>
            (Folio: {{ folioFormateado }})
          </template>
      </div>
      <span
        v-if="panelData.total_abiertas > 0"
        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-semibold tabular-nums shrink-0 cursor-help transition-colors duration-300"
        :class="[
          (panelData.porcentaje_rezago <= 15)
            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400'
            : (panelData.porcentaje_rezago <= 35)
              ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400'
              : 'bg-red-50 text-red-700 dark:bg-rose-950/40 dark:text-rose-400'
        ]"
        :title="`${panelData.total_rezago} de ${panelData.total_abiertas} solicitudes abiertas (En Trámite y Aprobadas)`"
      >
        <span
          class="h-1.5 w-1.5 rounded-full shrink-0"
          :class="[
            (panelData.porcentaje_rezago <= 15)
              ? 'bg-emerald-500'
              : (panelData.porcentaje_rezago <= 35)
                ? 'bg-amber-500'
                : 'bg-red-500'
          ]"
        ></span>
        <span>{{ panelData.porcentaje_rezago }}% de abiertas</span>
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
    default: () => ({ total_rezago: 0, max_dias_espera: 0 })
  },
  userAuth: Object
});

const folioFormateado = computed(() => {
  if (!props.panelData?.folio_max_espera) return '';
  return String(props.panelData.folio_max_espera).slice(-4).padStart(4, '0');
});
</script>