<template>
<div class="group relative isolate bg-color1-50 dark:bg-gray-800 p-3.5 sm:px-6 sm:py-5 
rounded-lg flex flex-col justify-between h-full min-h-[160px] overflow-hidden 
dark:ring-gray-700 shadow-sm cursor-pointer 
transition-all duration-300 hover:shadow-md hover:bg-color1-50 dark:hover:bg-gray-700 
hover:ring-color1-400 dark:hover:ring-color1-500">
    <!-- Decoración institucional -->
    <div class="absolute z-0 -right-5 -bottom-6 w-28 h-28 bg-color1-300/30 dark:bg-color1-500/10 rounded-full blur-2xl pointer-events-none transition-all duration-500 group-hover:scale-125"></div>
    <div class="absolute z-0 -left-8 -top-8 w-20 h-20 bg-color1-200/40 dark:bg-color1-500/10 rounded-full blur-xl pointer-events-none"></div>
    <!-- CABECERA -->
    <div class="flex items-center justify-between relative z-10">
        <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-color1-700 dark:text-color1-300">
            Recursos Públicos
        </span>
        <!-- ICONO -->
        <div class="p-1.5 sm:p-2 rounded-lg bg-color1-100/80 dark:bg-color1-900/50 text-color1-700 dark:text-color1-300 shadow-xs ring-1 ring-color1-200 dark:ring-color1-700 transition-all duration-100 group-hover:bg-color1-200/60 group-hover:text-color1-900 dark:group-hover:bg-color1-800 dark:group-hover:text-color1-200">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-coins preview-icon h-4 w-4"><path d="M13.744 17.736a6 6 0 1 1-7.48-7.48"/><path d="M15 6h1v4"/><path d="m6.134 14.768.866-.5 2 3.464"/><circle cx="16" cy="8" r="6"/></svg>
        </div>
    </div>
    <!-- CONTENIDO -->
    <div v-if="panelData" class="my-auto py-1 relative z-10 transition-transform duration-300 group-hover:-translate-y-0.5">
        <!-- MONTO -->
        <span :class="[
            'font-black text-color1-900 dark:text-white tracking-tight tabular-nums block drop-shadow-xs',
            {
                'text-xl sm:text-2xl': Number(panelData.monto_aprobado ?? 0) > 999999,
                'text-2xl sm:text-3xl': Number(panelData.monto_aprobado ?? 0) > 99999 && Number(panelData.monto_aprobado ?? 0) <= 999999,
                'text-3xl sm:text-4xl': Number(panelData.monto_aprobado ?? 0) <= 99999
            }]">
            ${{ Number(panelData.monto_aprobado ?? 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
        </span>
        <!-- ETIQUETA PRINCIPAL -->
        <span class="text-xs font-bold text-color1-800 dark:text-color1-300 mt-1 block">
            Monto global solicitado
        </span>
        <!-- DESCRIPCIÓN -->
        <span class="text-[0.65rem] text-color1-600 dark:text-color1-400 block">
            Recursos en proceso y entregados
        </span>
    </div>
    <!-- SIN REGISTROS -->
    <div v-else class="text-center text-color1-500 dark:text-color1-400 py-4 text-xs italic my-auto relative z-10">
        Sin registros en este periodo.
    </div>
    <!-- PIE DE TARJETA -->
    <div class="mt-3 pt-2.5 border-t border-color1-100 dark:border-color1-700 group-hover:border-color1-200/80 dark:group-hover:border-color1-600 flex items-center justify-between text-xs relative z-10 transition-colors duration-300">
        <span class="text-[11px] text-color1-600 dark:text-color1-400 italic group-hover:text-color1-800 dark:group-hover:text-color1-200 transition-colors">
            Reporte de datos financieros
        </span>
        <div class="flex items-center gap-1 text-[11px] font-semibold text-color1-700 dark:text-color1-300 group-hover:text-color1-900 dark:group-hover:text-white transition-colors">
            <span class="hidden sm:inline uppercase tracking-wider text-[10px]">
                Ver
            </span>
            <svg class="w-3.5 h-3.5 transform transition-transform duration-300 group-hover:translate-x-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </div>
    </div>
</div>
</template>

<script setup>
    defineProps({ 
        panelData: {
            type: [Object, Number, String],
            default: () => ({ count: 0, promedioDiario: 0 })
        }
    });

</script>