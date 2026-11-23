<template>
<div class="w-full flex flex-col justify-between h-full">
    
    <!-- Fila superior: Título con Badge + Icono SVG -->
    <div class="flex items-center justify-between gap-2 w-full">
        <div class="flex items-center gap-1.5">
            <span class="text-xs font-bold uppercase tracking-wider text-color1-800 dark:text-color1-300 transition-colors duration-300 group-hover:text-color1-700 dark:group-hover:text-color1-200">
                Total de Solicitudes  
            </span>
        </div>

        <!-- SVG minimalista -->
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-color1-600 to-color1-700 dark:from-color1-500 dark:to-color1-600 text-white shadow-xs flex items-center justify-center flex-shrink-0 transition-transform duration-300 group-hover:scale-110 group-hover:shadow-color1-500/20">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
        </div>
    </div>

    <!-- Contenido central: Barra vertical + Número destacado -->
    <div class="flex items-center gap-3 my-2">
        <div class="w-1.5 h-8 rounded-full bg-color1-600 dark:bg-color1-500 flex-shrink-0 transition-all duration-300 group-hover:h-9"></div>
        <div class="flex flex-col">
            <span class="text-3xl sm:text-[2.2rem] font-black text-gray-900 dark:text-white tracking-tight tabular-nums">
                {{ Number(panelData?.count ?? 0).toLocaleString() }}
            </span>
        </div>
    </div>

    <!-- Fila inferior: Promedio diario + Indicador -->
    <div  class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-2 min-h-[28px] border-t border-color1-50 dark:border-gray-700/60 transition-colors duration-300 group-hover:border-color1-200 dark:group-hover:border-color1-900/40">
        <div v-if="['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR'].some(role => $page.props.auth.user?.roles?.includes(role))" class="flex items-center gap-1.5">
            <span class="flex h-2 w-2 rounded-full bg-color1-600 dark:bg-color1-400 animate-pulse"></span>
            <span><strong class="text-color1-800 dark:text-color1-200 font-bold tabular-nums">{{ panelData?.promedioDiario || 0 }}</strong> solicitudes/día</span>
        </div>
        <div v-else class="flex items-center gap-1.5">
            <span class="flex h-2 w-2 rounded-full bg-color1-600 dark:bg-color1-400 animate-pulse"></span>
            <span><strong class="text-color1-800 dark:text-color1-200 font-bold tabular-nums">{{ panelData?.mesActual || 0 }}</strong> solicitudes en el mes actual</span>
        </div>

        <div v-if="['ADMINISTRADOR', 'AUDITOR', 'DIRECTOR'].some(role => $page.props.auth.user?.roles?.includes(role))" class="flex items-center gap-1 text-[11px] font-bold text-color1-700 dark:text-color1-400 opacity-80 group-hover:opacity-100 transition-opacity">
            <span class="hidden sm:inline uppercase tracking-wider text-[10px]">Ver</span>
            <svg class="w-3.5 h-3.5 transform transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
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

    const currentYear = new Date().getFullYear();
</script>

